<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralOutgoingPaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'general_outgoing_payment_id',
        'account_id',
        'sap_account',
        'account_name',
        'amount',
        'description',
        'profit_center',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function generalOutgoingPayment(): BelongsTo
    {
        return $this->belongsTo(GeneralOutgoingPayment::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
