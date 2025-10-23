<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    protected $guarded = [];

    const PAYMENT_METHODS = [
        'bilyet' => 'Bilyet Payment',
        'auto_debit' => 'Auto Debit',
        'cash' => 'Cash',
        'transfer' => 'Bank Transfer',
        'other' => 'Other'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withDefault([
            'account_number' => '-'
        ]);
    }

    public function bilyet()
    {
        return $this->belongsTo(Bilyet::class);
    }

    public function getPaymentMethodLabelAttribute()
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? '-';
    }

    public function isPaid()
    {
        return !is_null($this->paid_date);
    }

    public function scopePaid($query)
    {
        return $query->whereNotNull('paid_date');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNull('paid_date');
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
}
