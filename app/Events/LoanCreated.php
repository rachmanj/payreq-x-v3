<?php

namespace App\Events;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanCreated
{
    use Dispatchable, SerializesModels;

    public $loan;
    public $user;
    public $data;

    public function __construct(Loan $loan, User $user, array $data = [])
    {
        $this->loan = $loan;
        $this->user = $user;
        $this->data = $data;
    }
}
