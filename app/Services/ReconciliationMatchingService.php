<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\MatchGroupBankLine;
use App\Models\MatchGroupSapLine;
use App\Models\ReconciliationMatchGroup;
use App\Models\SapGlLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReconciliationMatchingService
{
    private const AMOUNT_TOLERANCE = 0.005;

    private const SPLIT_MAX_SIZE = 5;

    private const SPLIT_DATE_WINDOW_DAYS = 7;

    private const SPLIT_MAX_CANDIDATES = 20;

    private const FUZZY_AI_TOP_N = 3;

    public function __construct(protected OpenRouterService $openRouter) {}

    public function autoMatch(BankReconciliation $reconciliation): int
    {
        return DB::transaction(function () use ($reconciliation): int {
            $this->clearAutoMatchGroups($reconciliation);

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
                if ($bankLine->matched_status !== BankStatementLine::MATCH_UNMATCHED) {
                    continue;
                }

                $exact = $this->findExactSapMatch($bankLine, $sapLines);
                if ($exact !== null) {
                    $this->persistMatchGroup(
                        $reconciliation,
                        [$bankLine],
                        [$exact],
                        ReconciliationMatchGroup::TYPE_AUTO_EXACT,
                        1.0,
                        false
                    );
                    $matched++;
                    $this->refreshCollectionMembership($bankLine, $exact, $sapLines);

                    continue;
                }

                $fuzzy = $this->findFuzzySapMatch($reconciliation, $bankLine, $sapLines);
                if ($fuzzy !== null) {
                    $confidence = $fuzzy['confidence'];
                    $sapLine = $fuzzy['line'];
                    $this->persistMatchGroup(
                        $reconciliation,
                        [$bankLine],
                        [$sapLine],
                        ReconciliationMatchGroup::TYPE_AUTO_FUZZY,
                        $confidence,
                        false
                    );
                    $matched++;
                    $this->refreshCollectionMembership($bankLine, $sapLine, $sapLines);
                }
            }

            $matched += $this->autoMatchSplitManySapToOneBank($reconciliation, $bankLines, $sapLines);
            $matched += $this->autoMatchSplitManyBankToOneSap($reconciliation, $bankLines, $sapLines);

            return $matched;
        });
    }

    protected function autoMatchSplitManySapToOneBank(BankReconciliation $reconciliation, $bankLines, $sapLines): int
    {
        $count = 0;

        foreach ($bankLines as $bankLine) {
            if ($bankLine->matched_status !== BankStatementLine::MATCH_UNMATCHED) {
                continue;
            }

            $target = -$this->netBank($bankLine);

            $candidates = $sapLines
                ->filter(fn (SapGlLine $s) => $s->matched_status === SapGlLine::MATCH_UNMATCHED
                    && $this->datesWithinDays(
                        $bankLine->transaction_date?->format('Y-m-d'),
                        $s->posting_date?->format('Y-m-d'),
                        self::SPLIT_DATE_WINDOW_DAYS
                    ))
                ->sortBy(fn (SapGlLine $s) => $this->dateDistanceDays($bankLine->transaction_date, $s->posting_date))
                ->take(self::SPLIT_MAX_CANDIDATES)
                ->values()
                ->all();

            $combo = $this->findSubsetMatchingNet($target, $candidates, self::SPLIT_MAX_SIZE);
            if ($combo !== null && count($combo) >= 2) {
                $this->persistMatchGroup(
                    $reconciliation,
                    [$bankLine],
                    $combo,
                    ReconciliationMatchGroup::TYPE_AUTO_SPLIT,
                    0.8,
                    false
                );
                $count++;
                foreach ($combo as $s) {
                    $s->matched_status = SapGlLine::MATCH_MATCHED;
                }
                $bankLine->matched_status = BankStatementLine::MATCH_MATCHED;
            }
        }

        return $count;
    }

    protected function autoMatchSplitManyBankToOneSap(BankReconciliation $reconciliation, $bankLines, $sapLines): int
    {
        $count = 0;

        foreach ($sapLines as $sapLine) {
            if ($sapLine->matched_status !== SapGlLine::MATCH_UNMATCHED) {
                continue;
            }

            $target = -$this->netSap($sapLine);

            $candidates = $bankLines
                ->filter(fn (BankStatementLine $b) => $b->matched_status === BankStatementLine::MATCH_UNMATCHED
                    && $this->datesWithinDays(
                        $b->transaction_date?->format('Y-m-d'),
                        $sapLine->posting_date?->format('Y-m-d'),
                        self::SPLIT_DATE_WINDOW_DAYS
                    ))
                ->sortBy(fn (BankStatementLine $b) => $this->dateDistanceDays($b->transaction_date, $sapLine->posting_date))
                ->take(self::SPLIT_MAX_CANDIDATES)
                ->values()
                ->all();

            $combo = $this->findSubsetMatchingNetBankLines($target, $candidates, self::SPLIT_MAX_SIZE);
            if ($combo !== null && count($combo) >= 2) {
                $this->persistMatchGroup(
                    $reconciliation,
                    $combo,
                    [$sapLine],
                    ReconciliationMatchGroup::TYPE_AUTO_SPLIT,
                    0.8,
                    false
                );
                $count++;
                foreach ($combo as $b) {
                    $b->matched_status = BankStatementLine::MATCH_MATCHED;
                }
                $sapLine->matched_status = SapGlLine::MATCH_MATCHED;
            }
        }

        return $count;
    }

    /**
     * @param  array<int, SapGlLine>  $candidates
     * @return array<int, SapGlLine>|null
     */
    protected function findSubsetMatchingNet(float $target, array $candidates, int $maxSize): ?array
    {
        $nets = array_map(fn (SapGlLine $s) => $this->netSap($s), $candidates);
        $indices = $this->findSubsetIndices($target, $nets, $maxSize);

        if ($indices === null) {
            return null;
        }

        return array_map(fn ($i) => $candidates[$i], $indices);
    }

    /**
     * @param  array<int, BankStatementLine>  $candidates
     * @return array<int, BankStatementLine>|null
     */
    protected function findSubsetMatchingNetBankLines(float $target, array $candidates, int $maxSize): ?array
    {
        $nets = array_map(fn (BankStatementLine $b) => $this->netBank($b), $candidates);
        $indices = $this->findSubsetIndices($target, $nets, $maxSize);

        if ($indices === null) {
            return null;
        }

        return array_map(fn ($i) => $candidates[$i], $indices);
    }

    /**
     * Meet-in-the-middle subset search on cent-scaled nets (size 2..maxSize).
     *
     * @param  array<int, float>  $nets
     * @return array<int, int>|null
     */
    protected function findSubsetIndices(float $target, array $nets, int $maxSize): ?array
    {
        $n = count($nets);
        if ($n < 2) {
            return null;
        }

        $maxSize = min($maxSize, $n);
        $targetCents = (int) round($target * 100);
        $centNets = array_map(fn (float $net) => (int) round($net * 100), $nets);

        $mid = intdiv($n, 2);
        $left = $this->enumerateSubsets(array_slice($centNets, 0, $mid), 0, $maxSize);
        $right = $this->enumerateSubsets(array_slice($centNets, $mid), $mid, $maxSize);

        /** @var array<int, array<int, list<array<int, int>>>> $rightBySum */
        $rightBySum = [];
        foreach ($right as [$sum, $size, $indices]) {
            if ($size < 1) {
                continue;
            }
            $rightBySum[$sum][$size][] = $indices;
        }

        foreach ($left as [$sum, $size, $indices]) {
            $need = $targetCents - $sum;
            if (! isset($rightBySum[$need])) {
                continue;
            }

            foreach ($rightBySum[$need] as $rightSize => $combos) {
                $totalSize = $size + $rightSize;
                if ($totalSize < 2 || $totalSize > $maxSize) {
                    continue;
                }

                return array_merge($indices, $combos[0]);
            }
        }

        // Fallback: combinations entirely on one half (rare for n>=4, needed for tiny sets)
        foreach ([$left, $right] as $side) {
            foreach ($side as [$sum, $size, $indices]) {
                if ($size >= 2 && $size <= $maxSize && $sum === $targetCents) {
                    return $indices;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $centNets
     * @return list<array{0: int, 1: int, 2: array<int, int>}>
     */
    protected function enumerateSubsets(array $centNets, int $indexOffset, int $maxSize): array
    {
        $results = [[0, 0, []]];
        $count = count($centNets);

        for ($i = 0; $i < $count; $i++) {
            $currentCount = count($results);
            for ($r = 0; $r < $currentCount; $r++) {
                [$sum, $size, $indices] = $results[$r];
                if ($size >= $maxSize) {
                    continue;
                }

                $results[] = [
                    $sum + $centNets[$i],
                    $size + 1,
                    array_merge($indices, [$indexOffset + $i]),
                ];
            }
        }

        return $results;
    }

    protected function dateDistanceDays(?Carbon $bankDate, ?Carbon $sapDate): int
    {
        if ($bankDate === null || $sapDate === null) {
            return 999;
        }

        return abs($bankDate->startOfDay()->diffInDays($sapDate->startOfDay()));
    }

    protected function refreshCollectionMembership(BankStatementLine $bankLine, SapGlLine $sapLine, $sapLines): void
    {
        $bankLine->matched_status = BankStatementLine::MATCH_MATCHED;
        $sapLine->matched_status = SapGlLine::MATCH_MATCHED;
    }

    protected function clearAutoMatchGroups(BankReconciliation $reconciliation): void
    {
        $groups = $reconciliation->matchGroups()
            ->whereIn('match_type', [
                ReconciliationMatchGroup::TYPE_AUTO_EXACT,
                ReconciliationMatchGroup::TYPE_AUTO_FUZZY,
                ReconciliationMatchGroup::TYPE_AUTO_SPLIT,
            ])
            ->get();

        foreach ($groups as $group) {
            $this->deleteMatchGroup($group);
        }
    }

    public function deleteMatchGroup(ReconciliationMatchGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $bankIds = $group->matchGroupBankLines()->pluck('bank_statement_line_id');
            $sapIds = $group->matchGroupSapLines()->pluck('sap_gl_line_id');

            BankStatementLine::query()->whereIn('id', $bankIds)->update(['matched_status' => BankStatementLine::MATCH_UNMATCHED]);
            SapGlLine::query()->whereIn('id', $sapIds)->update(['matched_status' => SapGlLine::MATCH_UNMATCHED]);

            $group->delete();
        });
    }

    /**
     * @param  array<int, BankStatementLine>  $bankLines
     * @param  array<int, SapGlLine>  $sapLines
     */
    public function manualGroup(BankReconciliation $reconciliation, array $bankLines, array $sapLines): ReconciliationMatchGroup
    {
        return DB::transaction(function () use ($reconciliation, $bankLines, $sapLines): ReconciliationMatchGroup {
            return $this->persistMatchGroup(
                $reconciliation,
                $bankLines,
                $sapLines,
                ReconciliationMatchGroup::TYPE_MANUAL,
                1.0,
                true
            );
        });
    }

    /**
     * @param  array<int, BankStatementLine>  $bankLines
     * @param  array<int, SapGlLine>  $sapLines
     */
    protected function persistMatchGroup(
        BankReconciliation $reconciliation,
        array $bankLines,
        array $sapLines,
        string $matchType,
        float $confidence,
        bool $manual,
    ): ReconciliationMatchGroup {
        $bankTotal = array_sum(array_map(fn (BankStatementLine $b) => $this->netBank($b), $bankLines));
        $sapTotal = array_sum(array_map(fn (SapGlLine $s) => $this->netSap($s), $sapLines));

        if (abs($bankTotal + $sapTotal) >= self::AMOUNT_TOLERANCE) {
            throw new \InvalidArgumentException(
                'Bank and SAP net totals must offset within '.self::AMOUNT_TOLERANCE.'. Bank: '.$bankTotal.' SAP: '.$sapTotal
            );
        }

        $diff = $bankTotal + $sapTotal;

        $group = ReconciliationMatchGroup::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => $matchType,
            'confidence_score' => $confidence,
            'bank_total' => number_format($bankTotal, 2, '.', ''),
            'sap_total' => number_format($sapTotal, 2, '.', ''),
            'difference' => number_format($diff, 2, '.', ''),
            'created_by' => Auth::id(),
        ]);

        $bankStatus = $manual ? BankStatementLine::MATCH_MANUAL : BankStatementLine::MATCH_MATCHED;
        $sapStatus = $manual ? SapGlLine::MATCH_MANUAL : SapGlLine::MATCH_MATCHED;

        foreach ($bankLines as $bankLine) {
            MatchGroupBankLine::create([
                'reconciliation_match_group_id' => $group->id,
                'bank_statement_line_id' => $bankLine->id,
            ]);
            $bankLine->update(['matched_status' => $bankStatus]);
            $bankLine->matched_status = $bankStatus;
        }

        foreach ($sapLines as $sapLine) {
            MatchGroupSapLine::create([
                'reconciliation_match_group_id' => $group->id,
                'sap_gl_line_id' => $sapLine->id,
            ]);
            $sapLine->update(['matched_status' => $sapStatus]);
            $sapLine->matched_status = $sapStatus;
        }

        return $group;
    }

    protected function netBank(BankStatementLine $line): float
    {
        return round((float) $line->debit - (float) $line->credit, 2);
    }

    protected function netSap(SapGlLine $line): float
    {
        return round((float) $line->debit - (float) $line->credit, 2);
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
        $scored = [];
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

            $bankDesc = (string) ($bankLine->description ?? '');
            $sapDesc = (string) ($sapLine->description ?? '');
            similar_text(Str::lower($bankDesc), Str::lower($sapDesc), $percent);
            $dateDistance = $this->dateDistanceDays($bankLine->transaction_date, $sapLine->posting_date);
            $score = $percent - ($dateDistance * 2);

            $scored[] = [
                'line' => $sapLine,
                'percent' => $percent,
                'score' => $score,
            ];
        }

        if ($scored === []) {
            return null;
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        foreach ($scored as $candidate) {
            if ($candidate['percent'] >= 40.0) {
                return [
                    'line' => $candidate['line'],
                    'confidence' => min(0.95, 0.5 + ($candidate['percent'] / 200)),
                ];
            }
        }

        $top = array_slice($scored, 0, self::FUZZY_AI_TOP_N);
        foreach ($top as $candidate) {
            $confirmed = $this->confirmFuzzyWithAi($bankLine, $candidate['line']);
            if ($confirmed > 0.6) {
                return ['line' => $candidate['line'], 'confidence' => $confirmed];
            }
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

    protected function amountsEqual(string|float $bankDebit, string|float $bankCredit, string|float $sapDebit, string|float $sapCredit): bool
    {
        return abs((float) $bankDebit - (float) $sapCredit) < self::AMOUNT_TOLERANCE
            && abs((float) $bankCredit - (float) $sapDebit) < self::AMOUNT_TOLERANCE;
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
}
