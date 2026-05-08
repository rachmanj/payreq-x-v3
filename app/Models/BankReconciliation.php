<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'periode' => 'date',
        'opening_balance_bank' => 'decimal:2',
        'closing_balance_bank' => 'decimal:2',
        'opening_balance_book' => 'decimal:2',
        'closing_balance_book' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function giro(): BelongsTo
    {
        return $this->belongsTo(Giro::class);
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bankStatementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function sapGlLines(): HasMany
    {
        return $this->hasMany(SapGlLine::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ReconciliationMatch::class);
    }
}
