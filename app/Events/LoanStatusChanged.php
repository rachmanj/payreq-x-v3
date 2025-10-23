<?php

namespace App\Events;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanStatusChanged
{
    use Dispatchable, SerializesModels;

    public $loan;
    public $user;
    public $oldStatus;
    public $newStatus;

    public function __construct(Loan $loan, User $user, $oldStatus, $newStatus)
    {
        $this->loan = $loan;
        $this->user = $user;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
