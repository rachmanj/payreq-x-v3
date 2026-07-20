<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    public const MATCH_UNMATCHED = 'unmatched';

    public const MATCH_MATCHED = 'matched';

    public const MATCH_MANUAL = 'manual';

    public const MATCH_EXCLUDED = 'excluded';

    public const TYPE_CREDIT_NOT_BOOKED = 'credit_not_booked';

    public const TYPE_CHARGE_NOT_BOOKED = 'charge_not_booked';

    public const TYPE_BANK_ERROR = 'bank_error';

    public const RECONCILING_TYPES = [
        self::TYPE_CREDIT_NOT_BOOKED,
        self::TYPE_CHARGE_NOT_BOOKED,
        self::TYPE_BANK_ERROR,
    ];

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_ai_extracted' => 'boolean',
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
     * Bank-side unmatched lines adjust the book balance.
     * Positive net (debit) is an outflow on the statement (charge not booked);
     * negative net (credit) is an inflow (interest / credit not booked).
     * This matches the opposite-polarity convention used in matching (bank debit ↔ SAP credit).
     */
    public function reconcilingCategory(): string
    {
        if (filled($this->reconciling_type) && in_array($this->reconciling_type, self::RECONCILING_TYPES, true)) {
            return $this->reconciling_type;
        }

        return $this->net() >= 0
            ? self::TYPE_CHARGE_NOT_BOOKED
            : self::TYPE_CREDIT_NOT_BOOKED;
    }
}
