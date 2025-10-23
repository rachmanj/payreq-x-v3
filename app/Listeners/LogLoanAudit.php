<?php

namespace App\Listeners;

use App\Events\LoanCreated;
use App\Events\LoanUpdated;
use App\Events\LoanStatusChanged;
use App\Models\LoanAudit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Request;

class LogLoanAudit
{
    public function __construct()
    {
        //
    }

    public function handle($event): void
    {
        if ($event instanceof LoanCreated) {
            $this->logCreated($event);
        } elseif ($event instanceof LoanUpdated) {
            $this->logUpdated($event);
        } elseif ($event instanceof LoanStatusChanged) {
            $this->logStatusChanged($event);
        }
    }

    private function logCreated(LoanCreated $event)
    {
        LoanAudit::create([
            'loan_id' => $event->loan->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => $event->loan->toArray(),
            'user_id' => $event->user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'notes' => 'New loan created',
        ]);
    }

    private function logUpdated(LoanUpdated $event)
    {
        LoanAudit::create([
            'loan_id' => $event->loan->id,
            'action' => 'updated',
            'old_values' => $event->oldValues,
            'new_values' => $event->newValues,
            'user_id' => $event->user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'notes' => $this->generateNotes($event),
        ]);
    }

    private function logStatusChanged(LoanStatusChanged $event)
    {
        LoanAudit::create([
            'loan_id' => $event->loan->id,
            'action' => 'status_changed',
            'old_values' => ['status' => $event->oldStatus],
            'new_values' => ['status' => $event->newStatus],
            'user_id' => $event->user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'notes' => "Status changed from {$event->oldStatus} to {$event->newStatus}",
        ]);
    }

    private function generateNotes(LoanUpdated $event)
    {
        $notes = [];
        $changes = [];

        foreach ($event->newValues as $field => $newValue) {
            $oldValue = $event->oldValues[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = "{$field}: {$oldValue} → {$newValue}";
            }
        }

        if (!empty($changes)) {
            $notes[] = 'Updated: ' . implode(', ', $changes);
        }

        return implode('; ', $notes);
    }
}
