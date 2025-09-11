<?php

namespace App\Events;

use App\Models\Bilyet;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BilyetCreated
{
    use Dispatchable, SerializesModels;

    public $bilyet;
    public $user;
    public $data;

    public function __construct(Bilyet $bilyet, User $user, array $data = [])
    {
        $this->bilyet = $bilyet;
        $this->user = $user;
        $this->data = $data;
    }
}
