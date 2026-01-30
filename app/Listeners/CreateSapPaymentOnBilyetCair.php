<?php

namespace App\Listeners;

use App\Events\BilyetStatusChanged;
use App\Services\LoanSapIntegrationService;
use Illuminate\Support\Facades\Log;

class CreateSapPaymentOnBilyetCair
{
    protected LoanSapIntegrationService $integrationService;

    public function __construct(LoanSapIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    public function handle(BilyetStatusChanged $event): void
    {
        // Payment creation is now handled manually in BilyetController based on user's checkbox choice
        // This listener is kept for potential future use or audit logging
        // No automatic payment creation - user must explicitly choose via checkbox

        Log::debug('Bilyet status changed to cair', [
            'bilyet_id' => $event->bilyet->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'purpose' => $event->bilyet->purpose,
        ]);
    }
}
