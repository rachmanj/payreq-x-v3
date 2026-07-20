<?php

namespace App\Jobs;

use App\Models\BankReconciliation;
use App\Services\ReconciliationMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoMatchReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $bankReconciliationId)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(ReconciliationMatchingService $matchingService): void
    {
        $reconciliation = BankReconciliation::query()->find($this->bankReconciliationId);

        if ($reconciliation === null) {
            return;
        }

        try {
            $matchingService->autoMatch($reconciliation);

            if ($reconciliation->fresh()->status === BankReconciliation::STATUS_FAILED) {
                $reconciliation->update([
                    'status' => BankReconciliation::STATUS_IN_REVIEW,
                    'notes' => null,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Auto-match reconciliation failed', [
                'bank_reconciliation_id' => $this->bankReconciliationId,
                'exception' => $exception->getMessage(),
            ]);

            $this->markFailed($reconciliation, 'Auto-match failed: '.$exception->getMessage());

            throw $exception;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $reconciliation = BankReconciliation::query()->find($this->bankReconciliationId);

        if ($reconciliation === null) {
            return;
        }

        $message = $exception !== null
            ? 'Auto-match failed after retries: '.$exception->getMessage()
            : 'Auto-match failed after retries.';

        $this->markFailed($reconciliation, $message);
    }

    protected function markFailed(BankReconciliation $reconciliation, string $notes): void
    {
        $reconciliation->update([
            'status' => BankReconciliation::STATUS_FAILED,
            'notes' => $notes,
        ]);
    }
}
