<?php

namespace App\Listeners;

use App\Events\BilyetStatusChanged;
use App\Notifications\BilyetStatusChangedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendBilyetStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(BilyetStatusChanged $event): void
    {
        // Get users who should be notified
        $usersToNotify = $this->getUsersToNotify($event);

        // Send notification to each user
        foreach ($usersToNotify as $user) {
            $user->notify(new BilyetStatusChangedNotification(
                $event->bilyet,
                $event->oldStatus,
                $event->newStatus,
                $event->user
            ));
        }
    }

    /**
     * Get users who should be notified about status changes
     */
    private function getUsersToNotify(BilyetStatusChanged $event): array
    {
        $users = [];

        // Notify the creator of the bilyet (if different from the one making the change)
        if ($event->bilyet->created_by && $event->bilyet->created_by !== $event->user->id) {
            $creator = User::find($event->bilyet->created_by);
            if ($creator) {
                $users[] = $creator;
            }
        }

        // Notify admins and superadmins
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'superadmin']);
        })->get();

        foreach ($admins as $admin) {
            if ($admin->id !== $event->user->id && !in_array($admin, $users)) {
                $users[] = $admin;
            }
        }

        // Notify users from the same project
        if ($event->bilyet->project) {
            $projectUsers = User::where('project', $event->bilyet->project)
                ->where('id', '!=', $event->user->id)
                ->get();

            foreach ($projectUsers as $projectUser) {
                if (!in_array($projectUser, $users)) {
                    $users[] = $projectUser;
                }
            }
        }

        return $users;
    }
}
