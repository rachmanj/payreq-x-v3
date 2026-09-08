<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'principal' => 'decimal:2',
        'total_bunga' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class)->withDefault([
            'account_number' => '-',
        ]);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Unknown',
        ]);
    }

    public function creditor()
    {
        return $this->belongsTo(Creditor::class)->withDefault([
            'name' => 'Unknown',
        ]);
    }

    public function audits()
    {
        return $this->hasMany(LoanAudit::class);
    }
}
