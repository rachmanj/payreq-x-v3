<?php

namespace App\Notifications;

use App\Models\BankReconciliation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankReconciliationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BankReconciliation $reconciliation,
        public User $rejectedBy,
        public string $rejectionReason,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->reconciliation->loadMissing(['giro.bank']);

        return (new MailMessage)
            ->subject('Bank reconciliation rejected #'.$this->reconciliation->id)
            ->greeting('Hello!')
            ->line('Your bank reconciliation was rejected and returned for revision.')
            ->line('Account: '.($this->reconciliation->giro?->acc_no ?? '-').' '.($this->reconciliation->giro?->acc_name ?? ''))
            ->line('Period: '.($this->reconciliation->periode?->format('F Y') ?? '-'))
            ->line('Rejected by: '.$this->rejectedBy->name)
            ->line('Reason: '.$this->rejectionReason)
            ->action('Open reconciliation', route('cashier.bank-reconciliation.show', $this->reconciliation))
            ->line('Please revise and resubmit when ready.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->reconciliation->loadMissing(['giro.bank']);

        return [
            'type' => 'bank_reconciliation_rejected',
            'bank_reconciliation_id' => $this->reconciliation->id,
            'giro_acc_no' => $this->reconciliation->giro?->acc_no,
            'periode' => $this->reconciliation->periode?->format('Y-m'),
            'rejected_by' => $this->rejectedBy->name,
            'rejection_reason' => $this->rejectionReason,
            'url' => route('cashier.bank-reconciliation.show', $this->reconciliation),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
