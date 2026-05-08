<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\ReconciliationMatch;
use App\Models\SapGlLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReconciliationMatchingService
{
    public function __construct(protected OpenRouterService $openRouter) {}

    public function autoMatch(BankReconciliation $reconciliation): int
    {
        return DB::transaction(function () use ($reconciliation): int {
            $this->clearAutoMatches($reconciliation);

            $bankLines = $reconciliation->bankStatementLines()
                ->where('matched_status', BankStatementLine::MATCH_UNMATCHED)
                ->orderBy('line_order')
                ->orderBy('id')
                ->get();

            $sapLines = $reconciliation->sapGlLines()
                ->where('matched_status', SapGlLine::MATCH_UNMATCHED)
                ->orderBy('posting_date')
                ->orderBy('id')
                ->get();

            $matched = 0;

            foreach ($bankLines as $bankLine) {
                $exact = $this->findExactSapMatch($bankLine, $sapLines);
                if ($exact !== null) {
                    $this->createMatch($reconciliation, $bankLine, $exact, ReconciliationMatch::TYPE_AUTO_EXACT, 1.0);
                    $matched++;

                    continue;
                }

                $fuzzy = $this->findFuzzySapMatch($reconciliation, $bankLine, $sapLines);
                if ($fuzzy !== null) {
                    $confidence = $fuzzy['confidence'];
                    $sapLine = $fuzzy['line'];
                    $this->createMatch($reconciliation, $bankLine, $sapLine, ReconciliationMatch::TYPE_AUTO_FUZZY, $confidence);
                    $matched++;
                }
            }

            return $matched;
        });
    }

    protected function clearAutoMatches(BankReconciliation $reconciliation): void
    {
        $matches = $reconciliation->matches()
            ->whereIn('match_type', [ReconciliationMatch::TYPE_AUTO_EXACT, ReconciliationMatch::TYPE_AUTO_FUZZY])
            ->get();

        foreach ($matches as $match) {
            optional($match->bankStatementLine)->update(['matched_status' => BankStatementLine::MATCH_UNMATCHED]);
            optional($match->sapGlLine)->update(['matched_status' => SapGlLine::MATCH_UNMATCHED]);
            $match->delete();
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SapGlLine>  $sapLines
     */
    protected function findExactSapMatch(BankStatementLine $bankLine, $sapLines): ?SapGlLine
    {
        foreach ($sapLines as $sapLine) {
            if ($sapLine->matched_status !== SapGlLine::MATCH_UNMATCHED) {
                continue;
            }

            if (! $this->amountsEqual($bankLine->debit, $bankLine->credit, $sapLine->debit, $sapLine->credit)) {
                continue;
            }

            if (! $this->datesWithinDays($bankLine->transaction_date?->format('Y-m-d'), $sapLine->posting_date?->format('Y-m-d'), 1)) {
                continue;
            }

            return $sapLine;
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SapGlLine>  $sapLines
     * @return array{line: SapGlLine, confidence: float}|null
     */
    protected function findFuzzySapMatch(BankReconciliation $reconciliation, BankStatementLine $bankLine, $sapLines): ?array
    {
        $candidates = [];
        foreach ($sapLines as $sapLine) {
            if ($sapLine->matched_status !== SapGlLine::MATCH_UNMATCHED) {
                continue;
            }

            if (! $this->amountsEqual($bankLine->debit, $bankLine->credit, $sapLine->debit, $sapLine->credit)) {
                continue;
            }

            if (! $this->datesWithinDays($bankLine->transaction_date?->format('Y-m-d'), $sapLine->posting_date?->format('Y-m-d'), 5)) {
                continue;
            }

            $candidates[] = $sapLine;
        }

        if ($candidates === []) {
            return null;
        }

        foreach ($candidates as $sapLine) {
            $bankDesc = (string) ($bankLine->description ?? '');
            $sapDesc = (string) ($sapLine->description ?? '');
            similar_text(Str::lower($bankDesc), Str::lower($sapDesc), $percent);
            if ($percent >= 40.0) {
                return ['line' => $sapLine, 'confidence' => min(0.95, 0.5 + ($percent / 200))];
            }
        }

        $best = $candidates[0];
        $confirmed = $this->confirmFuzzyWithAi($bankLine, $best);
        if ($confirmed > 0.6) {
            return ['line' => $best, 'confidence' => $confirmed];
        }

        return null;
    }

    protected function confirmFuzzyWithAi(BankStatementLine $bankLine, SapGlLine $sapLine): float
    {
        try {
            $prompt = 'Do these two ledger lines likely refer to the same bank transaction? Reply with JSON only: {"match":true|false,"confidence":0-1}'."\n"
                .'Bank: '.($bankLine->description ?? '').' | Ref: '.($bankLine->reference ?? '')."\n"
                .'SAP: '.($sapLine->description ?? '').' | Doc: '.($sapLine->doc_num ?? '');

            $response = $this->openRouter->chat([
                ['role' => 'user', 'content' => $prompt],
            ]);

            $content = data_get($response, 'choices.0.message.content');
            if (! is_string($content)) {
                return 0.0;
            }

            $trimmed = trim($content);
            if (preg_match('/\{[\s\S]*\}/', $trimmed, $m)) {
                $trimmed = $m[0];
            }

            $decoded = json_decode($trimmed, true);
            if (! is_array($decoded) || data_get($decoded, 'match') !== true) {
                return 0.0;
            }

            return (float) data_get($decoded, 'confidence', 0.75);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    protected function createMatch(
        BankReconciliation $reconciliation,
        BankStatementLine $bankLine,
        SapGlLine $sapLine,
        string $matchType,
        float $confidence,
    ): void {
        ReconciliationMatch::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_statement_line_id' => $bankLine->id,
            'sap_gl_line_id' => $sapLine->id,
            'match_type' => $matchType,
            'confidence_score' => $confidence,
            'created_by' => Auth::id(),
        ]);

        $bankLine->update(['matched_status' => BankStatementLine::MATCH_MATCHED]);
        $sapLine->update(['matched_status' => SapGlLine::MATCH_MATCHED]);
        $bankLine->matched_status = BankStatementLine::MATCH_MATCHED;
        $sapLine->matched_status = SapGlLine::MATCH_MATCHED;
    }

    protected function amountsEqual(string|float $bankDebit, string|float $bankCredit, string|float $sapDebit, string|float $sapCredit): bool
    {
        return abs((float) $bankDebit - (float) $sapDebit) < 0.005
            && abs((float) $bankCredit - (float) $sapCredit) < 0.005;
    }

    protected function datesWithinDays(?string $bankDate, ?string $sapDate, int $days): bool
    {
        if ($bankDate === null || $sapDate === null) {
            return true;
        }

        try {
            $b = Carbon::createFromFormat('Y-m-d', $bankDate)->startOfDay();
            $s = Carbon::createFromFormat('Y-m-d', $sapDate)->startOfDay();

            return abs($b->diffInDays($s)) <= $days;
        } catch (\Throwable) {
            return true;
        }
    }

    public function manualPair(BankReconciliation $reconciliation, BankStatementLine $bankLine, SapGlLine $sapLine): void
    {
        DB::transaction(function () use ($reconciliation, $bankLine, $sapLine): void {
            ReconciliationMatch::query()
                ->where('bank_reconciliation_id', $reconciliation->id)
                ->where(function ($query) use ($bankLine, $sapLine): void {
                    $query->where('bank_statement_line_id', $bankLine->id)
                        ->orWhere('sap_gl_line_id', $sapLine->id);
                })
                ->get()
                ->each(function (ReconciliationMatch $match): void {
                    optional($match->bankStatementLine)->update(['matched_status' => BankStatementLine::MATCH_UNMATCHED]);
                    optional($match->sapGlLine)->update(['matched_status' => SapGlLine::MATCH_UNMATCHED]);
                    $match->delete();
                });

            ReconciliationMatch::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'bank_statement_line_id' => $bankLine->id,
                'sap_gl_line_id' => $sapLine->id,
                'match_type' => ReconciliationMatch::TYPE_MANUAL,
                'confidence_score' => 1.0,
                'created_by' => Auth::id(),
            ]);

            $bankLine->update(['matched_status' => BankStatementLine::MATCH_MANUAL]);
            $sapLine->update(['matched_status' => SapGlLine::MATCH_MANUAL]);
        });
    }
}
