<?php

namespace App\Events;

use App\Models\Bilyet;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BilyetStatusChanged
{
    use Dispatchable, SerializesModels;

    public $bilyet;
    public $user;
    public $oldStatus;
    public $newStatus;

    public function __construct(Bilyet $bilyet, User $user, string $oldStatus, string $newStatus)
    {
        $this->bilyet = $bilyet;
        $this->user = $user;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
