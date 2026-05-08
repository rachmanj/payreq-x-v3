<?php

namespace App\Jobs;

use App\Models\BankReconciliation;
use App\Services\BankStatementParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ParseBankStatementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $bankReconciliationId)
    {
        $this->afterCommit();
    }

    public function handle(BankStatementParserService $parser): void
    {
        $reconciliation = BankReconciliation::query()->find($this->bankReconciliationId);

        if ($reconciliation === null) {
            return;
        }

        if ($reconciliation->dokumen_id === null) {
            return;
        }

        try {
            $parser->parseAndPersist($reconciliation);

            $reconciliation->refresh();

            if ($reconciliation->status === BankReconciliation::STATUS_PROCESSING) {
                $reconciliation->update(['status' => BankReconciliation::STATUS_IN_REVIEW]);
            }
        } catch (\Throwable $exception) {
            Log::error('Bank statement parse failed', [
                'bank_reconciliation_id' => $this->bankReconciliationId,
                'exception' => $exception->getMessage(),
            ]);

            $reconciliation->update([
                'status' => BankReconciliation::STATUS_FAILED,
                'notes' => 'PDF parse failed: '.$exception->getMessage(),
            ]);
        }
    }
}
