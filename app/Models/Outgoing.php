<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outgoing extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payreq()
    {
        return $this->belongsTo(Payreq::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function cash_journal()
    {
        return $this->belongsTo(CashJournal::class);
    }
}
