<?php

namespace App\Listeners;

use App\Events\BilyetCreated;
use App\Events\BilyetUpdated;
use App\Events\BilyetStatusChanged;
use App\Models\BilyetAudit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Request;

class LogBilyetAudit
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        if ($event instanceof BilyetCreated) {
            $this->logCreated($event);
        } elseif ($event instanceof BilyetUpdated) {
            $this->logUpdated($event);
        } elseif ($event instanceof BilyetStatusChanged) {
            $this->logStatusChanged($event);
        }
    }

    private function logCreated(BilyetCreated $event)
    {
        BilyetAudit::create([
            'bilyet_id' => $event->bilyet->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => $event->bilyet->toArray(),
            'user_id' => $event->user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'notes' => 'New bilyet created',
        ]);
    }

    private function logUpdated(BilyetUpdated $event)
    {
        BilyetAudit::create([
            'bilyet_id' => $event->bilyet->id,
            'action' => $event->action,
            'old_values' => $event->oldValues,
            'new_values' => $event->newValues,
            'user_id' => $event->user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'notes' => $this->generateNotes($event),
        ]);
    }

    private function logStatusChanged(BilyetStatusChanged $event)
    {
        BilyetAudit::create([
            'bilyet_id' => $event->bilyet->id,
            'action' => 'status_changed',
            'old_values' => ['status' => $event->oldStatus],
            'new_values' => ['status' => $event->newStatus],
            'user_id' => $event->user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'notes' => "Status changed from {$event->oldStatus} to {$event->newStatus}",
        ]);
    }

    private function generateNotes(BilyetUpdated $event)
    {
        $notes = [];

        if ($event->action === 'voided') {
            $notes[] = 'Bilyet voided';
        } elseif ($event->action === 'bulk_updated') {
            $notes[] = 'Bulk update performed';
        } else {
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
        }

        return implode('; ', $notes);
    }
}
