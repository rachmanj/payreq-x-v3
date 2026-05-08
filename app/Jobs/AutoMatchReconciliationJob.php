<?php

namespace App\Jobs;

use App\Models\BankReconciliation;
use App\Services\ReconciliationMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoMatchReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $bankReconciliationId)
    {
        $this->afterCommit();
    }

    public function handle(ReconciliationMatchingService $matchingService): void
    {
        $reconciliation = BankReconciliation::query()->find($this->bankReconciliationId);

        if ($reconciliation === null) {
            return;
        }

        $matchingService->autoMatch($reconciliation);
    }
}
