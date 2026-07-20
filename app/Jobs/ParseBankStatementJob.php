<?php

namespace App\Jobs;

use App\Exceptions\OpenRouterException;
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

    public function handle(BankStatementParserService $parser): void
    {
        $reconciliation = BankReconciliation::query()->find($this->bankReconciliationId);

        if ($reconciliation === null) {
            return;
        }

        if ($reconciliation->dokumen_id === null) {
            $this->markFailed($reconciliation, 'PDF parse failed: no koran PDF is linked to this reconciliation.');

            return;
        }

        try {
            $parser->parseAndPersist($reconciliation);

            $reconciliation->refresh();

            if ($reconciliation->status === BankReconciliation::STATUS_PROCESSING
                || $reconciliation->status === BankReconciliation::STATUS_FAILED) {
                $reconciliation->update([
                    'status' => BankReconciliation::STATUS_IN_REVIEW,
                    'notes' => null,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Bank statement parse failed', [
                'bank_reconciliation_id' => $this->bankReconciliationId,
                'exception' => $exception->getMessage(),
                'openrouter_response' => $exception instanceof OpenRouterException ? $exception->getResponseBody() : null,
            ]);

            $this->markFailed($reconciliation, 'PDF parse failed: '.$exception->getMessage());

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
            ? 'PDF parse failed after retries: '.$exception->getMessage()
            : 'PDF parse failed after retries.';

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
