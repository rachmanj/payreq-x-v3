<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationMatchGroup extends Model
{
    public const TYPE_AUTO_EXACT = 'auto_exact';

    public const TYPE_AUTO_FUZZY = 'auto_fuzzy';

    public const TYPE_AUTO_SPLIT = 'auto_split';

    public const TYPE_MANUAL = 'manual';

    protected $guarded = [];

    protected $casts = [
        'bank_total' => 'decimal:2',
        'sap_total' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function matchGroupBankLines(): HasMany
    {
        return $this->hasMany(MatchGroupBankLine::class);
    }

    public function matchGroupSapLines(): HasMany
    {
        return $this->hasMany(MatchGroupSapLine::class);
    }
}
