<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SapGlLine extends Model
{
    public const MATCH_UNMATCHED = 'unmatched';

    public const MATCH_MATCHED = 'matched';

    public const MATCH_MANUAL = 'manual';

    public const MATCH_EXCLUDED = 'excluded';

    public const TYPE_DEPOSIT_IN_TRANSIT = 'deposit_in_transit';

    public const TYPE_OUTSTANDING_PAYMENT = 'outstanding_payment';

    public const TYPE_BOOK_ERROR = 'book_error';

    public const RECONCILING_TYPES = [
        self::TYPE_DEPOSIT_IN_TRANSIT,
        self::TYPE_OUTSTANDING_PAYMENT,
        self::TYPE_BOOK_ERROR,
    ];

    protected $guarded = [];

    protected $casts = [
        'doc_date' => 'date',
        'posting_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function net(): float
    {
        return round((float) $this->debit - (float) $this->credit, 2);
    }

    /**
     * Book-side unmatched lines adjust the bank balance.
     * Positive net (debit on bank GL) typically means a deposit in transit;
     * negative net (credit on bank GL) typically means an outstanding payment.
     */
    public function reconcilingCategory(): string
    {
        if (filled($this->reconciling_type) && in_array($this->reconciling_type, self::RECONCILING_TYPES, true)) {
            return $this->reconciling_type;
        }

        return $this->net() >= 0
            ? self::TYPE_DEPOSIT_IN_TRANSIT
            : self::TYPE_OUTSTANDING_PAYMENT;
    }
}
