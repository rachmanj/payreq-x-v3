<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\SapGlLine;
use App\Services\SapBridge\AccountStatementService;
use App\Services\SapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchSapGlLinesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $bankReconciliationId)
    {
        $this->afterCommit();
    }

    public function handle(SapService $sapService, AccountStatementService $bridge): void
    {
        $reconciliation = BankReconciliation::query()->with('giro')->find($this->bankReconciliationId);

        if ($reconciliation === null || $reconciliation->giro === null) {
            return;
        }

        $start = $reconciliation->periode->copy()->startOfMonth()->format('Y-m-d');
        $end = $reconciliation->periode->copy()->endOfMonth()->format('Y-m-d');

        $accountCode = trim((string) ($reconciliation->giro->sap_account ?? ''));

        if ($accountCode === '') {
            $fallbackAccount = Account::query()
                ->where('project', $reconciliation->giro->project)
                ->where('type', 'bank')
                ->orderBy('account_number')
                ->first();

            $accountCode = $fallbackAccount !== null ? trim((string) $fallbackAccount->account_number) : '';
        }

        $reconciliation->sapGlLines()->delete();

        $lines = [];

        if ($accountCode !== '') {
            try {
                $lines = $sapService->getGLLines($accountCode, $start, $end);
            } catch (\Throwable $exception) {
                Log::warning('SAP Service Layer GL fetch failed, trying SAP Bridge', [
                    'bank_reconciliation_id' => $this->bankReconciliationId,
                    'error' => $exception->getMessage(),
                ]);
            }

            if ($lines === []) {
                try {
                    $statement = $bridge->getAccountStatement($accountCode, $start, $end);
                    $opening = data_get($statement, 'opening_balance');
                    $closing = data_get($statement, 'closing_balance');

                    if ($opening !== null || $closing !== null) {
                        $reconciliation->update([
                            'opening_balance_book' => $opening !== null ? number_format((float) $opening, 2, '.', '') : null,
                            'closing_balance_book' => $closing !== null ? number_format((float) $closing, 2, '.', '') : null,
                        ]);
                    }

                    foreach (data_get($statement, 'transactions', []) as $row) {
                        if (! is_array($row)) {
                            continue;
                        }

                        $posting = isset($row['posting_date']) ? substr((string) $row['posting_date'], 0, 10) : null;

                        $lines[] = [
                            'doc_date' => $posting,
                            'posting_date' => $posting,
                            'doc_num' => (string) ($row['doc_num'] ?? ''),
                            'ref_doc_num' => (string) ($row['tx_num'] ?? ''),
                            'transaction_id' => (string) ($row['tx_num'] ?? ''),
                            'description' => trim((string) ($row['description'] ?? '')),
                            'project_code' => (string) ($row['project_code'] ?? ''),
                            'debit' => (float) ($row['debit_amount'] ?? 0),
                            'credit' => (float) ($row['credit_amount'] ?? 0),
                        ];
                    }
                } catch (\Throwable $exception) {
                    Log::warning('SAP Bridge account statement fetch failed', [
                        'bank_reconciliation_id' => $this->bankReconciliationId,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        foreach ($lines as $line) {
            SapGlLine::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'doc_date' => $line['doc_date'] ?? null,
                'posting_date' => $line['posting_date'] ?? null,
                'doc_num' => substr((string) ($line['doc_num'] ?? ''), 0, 64),
                'ref_doc_num' => substr((string) ($line['ref_doc_num'] ?? ''), 0, 128),
                'transaction_id' => substr((string) ($line['transaction_id'] ?? ''), 0, 64),
                'description' => $line['description'] ?? null,
                'project_code' => substr((string) ($line['project_code'] ?? ''), 0, 64),
                'debit' => number_format((float) ($line['debit'] ?? 0), 2, '.', ''),
                'credit' => number_format((float) ($line['credit'] ?? 0), 2, '.', ''),
                'matched_status' => SapGlLine::MATCH_UNMATCHED,
            ]);
        }
    }
}
