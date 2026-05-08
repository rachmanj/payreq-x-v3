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
}
