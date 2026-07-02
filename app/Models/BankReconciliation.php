<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public const SOURCE_AI = 'ai';

    public const SOURCE_MANUAL = 'manual';

    public const VALIDATION_PENDING = 'pending_validation';

    public const VALIDATION_VALIDATED = 'validated';

    public const VALIDATION_REJECTED = 'rejected';

    protected $guarded = [];

    protected $casts = [
        'periode' => 'date',
        'opening_balance_bank' => 'decimal:2',
        'closing_balance_bank' => 'decimal:2',
        'opening_balance_book' => 'decimal:2',
        'closing_balance_book' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
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

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function bankStatementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function sapGlLines(): HasMany
    {
        return $this->hasMany(SapGlLine::class);
    }

    public function matchGroups(): HasMany
    {
        return $this->hasMany(ReconciliationMatchGroup::class);
    }

    public function isLockedForEditing(): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return true;
        }

        return $this->validation_status === self::VALIDATION_PENDING;
    }

    public function isPreparer(int $userId): bool
    {
        return in_array($userId, array_filter([
            $this->created_by,
            $this->submitted_by,
            $this->reconciled_by,
        ]), true);
    }

    public function scopePendingValidation(Builder $query): Builder
    {
        return $query->where('validation_status', self::VALIDATION_PENDING);
    }

    public function scopeExcludingPreparer(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where(function (Builder $query) use ($userId): void {
                $query->where('created_by', '!=', $userId)->orWhereNull('created_by');
            })->where(function (Builder $query) use ($userId): void {
                $query->where('submitted_by', '!=', $userId)->orWhereNull('submitted_by');
            })->where(function (Builder $query) use ($userId): void {
                $query->where('reconciled_by', '!=', $userId)->orWhereNull('reconciled_by');
            });
        });
    }
}
