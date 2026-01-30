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

    const SAP_SYNC_STATUSES = [
        'pending' => 'Pending',
        'ap_created' => 'AP Invoice Created',
        'payment_created' => 'Payment Created',
        'completed' => 'Completed'
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

    public function scopeWithSapApInvoice($query)
    {
        return $query->whereNotNull('sap_ap_doc_num');
    }

    public function scopePendingSapSync($query)
    {
        return $query->where('sap_sync_status', 'pending');
    }

    public function getSapSyncStatusLabelAttribute()
    {
        return self::SAP_SYNC_STATUSES[$this->sap_sync_status] ?? $this->sap_sync_status;
    }

    public function hasSapApInvoice(): bool
    {
        return !is_null($this->sap_ap_doc_num);
    }

    public function hasSapPayment(): bool
    {
        return !is_null($this->sap_payment_doc_num);
    }

    public function canCreateSapApInvoice(): bool
    {
        if ($this->hasSapApInvoice()) {
            return false;
        }

        if (!$this->loan_id) {
            return false;
        }

        if (!$this->loan || !$this->loan->creditor_id) {
            return false;
        }

        return in_array($this->payment_method, ['bilyet', 'auto_debit']);
    }

    public function canCreateSapPayment(): bool
    {
        if ($this->hasSapPayment()) {
            return false;
        }

        if (!$this->hasSapApInvoice()) {
            return false;
        }

        if ($this->payment_method === 'bilyet') {
            return $this->bilyet && $this->bilyet->status === 'cair';
        }

        if ($this->payment_method === 'auto_debit') {
            return !is_null($this->paid_date);
        }

        return false;
    }
}
