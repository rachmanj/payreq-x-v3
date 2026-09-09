<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilityApInvoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PAID = 'paid';

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }

    public function sapBusinessPartner(): BelongsTo
    {
        return $this->belongsTo(SapBusinessPartner::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null || $this->status === self::STATUS_PAID;
    }

    public function canCreateOutgoingPayment(): bool
    {
        return $this->status === self::STATUS_POSTED && ! $this->isPaid();
    }

    public function statusLabel(): string
    {
        if ($this->isPaid()) {
            $docNum = $this->paid_sap_doc_num ?: '-';

            return 'Paid (DocNum '.$docNum.')';
        }

        return match ($this->status) {
            self::STATUS_POSTED => 'Posted',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_PENDING => 'Pending',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusChipClass(): string
    {
        if ($this->isPaid()) {
            return 'success';
        }

        return match ($this->status) {
            self::STATUS_POSTED => 'neutral',
            self::STATUS_FAILED => 'danger',
            default => 'neutral',
        };
    }
}
