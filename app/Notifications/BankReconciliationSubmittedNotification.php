<?php

namespace App\Notifications;

use App\Models\BankReconciliation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BankReconciliationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BankReconciliation $reconciliation,
        public User $submittedBy,
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
            ->subject('Bank reconciliation pending validation #'.$this->reconciliation->id)
            ->greeting('Hello!')
            ->line('A bank reconciliation has been submitted for validation.')
            ->line('Account: '.($this->reconciliation->giro?->acc_no ?? '-').' '.($this->reconciliation->giro?->acc_name ?? ''))
            ->line('Bank: '.($this->reconciliation->giro?->bank?->name ?? '-'))
            ->line('Period: '.($this->reconciliation->periode?->format('F Y') ?? '-'))
            ->line('Submitted by: '.$this->submittedBy->name)
            ->action('Review reconciliation', route('cashier.bank-reconciliation.show', $this->reconciliation))
            ->line('Thank you for using our application!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->reconciliation->loadMissing(['giro.bank']);

        return [
            'type' => 'bank_reconciliation_submitted',
            'bank_reconciliation_id' => $this->reconciliation->id,
            'giro_acc_no' => $this->reconciliation->giro?->acc_no,
            'periode' => $this->reconciliation->periode?->format('Y-m'),
            'submitted_by' => $this->submittedBy->name,
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
