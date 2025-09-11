<?php

namespace App\Events;

use App\Models\Bilyet;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BilyetUpdated
{
    use Dispatchable, SerializesModels;

    public $bilyet;
    public $user;
    public $oldValues;
    public $newValues;
    public $action;

    public function __construct(Bilyet $bilyet, User $user, array $oldValues, array $newValues, string $action = 'updated')
    {
        $this->bilyet = $bilyet;
        $this->user = $user;
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
        $this->action = $action;
    }
}
