<?php

namespace App\Events;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanUpdated
{
    use Dispatchable, SerializesModels;

    public $loan;
    public $user;
    public $oldValues;
    public $newValues;

    public function __construct(Loan $loan, User $user, array $oldValues = [], array $newValues = [])
    {
        $this->loan = $loan;
        $this->user = $user;
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
    }
}
