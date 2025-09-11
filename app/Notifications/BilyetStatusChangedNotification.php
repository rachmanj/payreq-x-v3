<?php

namespace App\Notifications;

use App\Models\Bilyet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class BilyetStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $bilyet;
    public $oldStatus;
    public $newStatus;
    public $changedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Bilyet $bilyet, string $oldStatus, string $newStatus, User $changedBy)
    {
        $this->bilyet = $bilyet;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = Bilyet::STATUS_LABELS;

        return (new MailMessage)
            ->subject('Bilyet Status Changed: ' . $this->bilyet->full_nomor)
            ->greeting('Hello!')
            ->line('A bilyet status has been changed.')
            ->line('**Bilyet Details:**')
            ->line('Number: ' . $this->bilyet->full_nomor)
            ->line('Bank: ' . ($this->bilyet->giro->bank->name ?? 'N/A'))
            ->line('Amount: ' . number_format($this->bilyet->amount, 2))
            ->line('Status Changed: ' . ($statusLabels[$this->oldStatus] ?? $this->oldStatus) . ' → ' . ($statusLabels[$this->newStatus] ?? $this->newStatus))
            ->line('Changed By: ' . $this->changedBy->name)
            ->line('Changed At: ' . now()->format('d M Y H:i:s'))
            ->action('View Bilyet', route('cashier.bilyets.history', $this->bilyet->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'bilyet_id' => $this->bilyet->id,
            'bilyet_number' => $this->bilyet->full_nomor,
            'bank_name' => $this->bilyet->giro->bank->name ?? 'N/A',
            'amount' => $this->bilyet->amount,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy->name,
            'changed_at' => now()->toISOString(),
            'type' => 'bilyet_status_changed'
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'bilyet_id' => $this->bilyet->id,
            'bilyet_number' => $this->bilyet->full_nomor,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy->name,
            'type' => 'bilyet_status_changed'
        ];
    }
}
