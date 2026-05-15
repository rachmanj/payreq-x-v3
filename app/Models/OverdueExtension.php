<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverdueExtension extends Model
{
    public const DOCUMENT_PAYREQ = 'payreq';

    public const DOCUMENT_REALIZATION = 'realization';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected $casts = [
        'current_due_date' => 'date',
        'requested_due_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return array<int, string>
     */
    public static function eligibleProjects(): array
    {
        return ['000H', 'APS'];
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault([
            'name' => 'n/a',
        ]);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withDefault([
            'name' => 'n/a',
        ]);
    }

    public function resolvePayreq(): ?Payreq
    {
        if ($this->document_type !== self::DOCUMENT_PAYREQ) {
            return null;
        }

        return Payreq::query()->find($this->document_id);
    }

    public function resolveRealization(): ?Realization
    {
        if ($this->document_type !== self::DOCUMENT_REALIZATION) {
            return null;
        }

        return Realization::query()->find($this->document_id);
    }

    public function resolveNomor(): ?string
    {
        return match ($this->document_type) {
            self::DOCUMENT_PAYREQ => $this->resolvePayreq()?->nomor,
            self::DOCUMENT_REALIZATION => $this->resolveRealization()?->nomor,
            default => null,
        };
    }

    public function resolveProject(): ?string
    {
        return match ($this->document_type) {
            self::DOCUMENT_PAYREQ => $this->resolvePayreq()?->project,
            self::DOCUMENT_REALIZATION => $this->resolveRealization()?->project,
            default => null,
        };
    }

    public function resolveRemarks(): ?string
    {
        return match ($this->document_type) {
            self::DOCUMENT_PAYREQ => $this->resolvePayreq()?->remarks,
            self::DOCUMENT_REALIZATION => $this->resolveRealization()?->remarks,
            default => null,
        };
    }

    public function extensionSequence(): array
    {
        $ids = static::query()
            ->where('document_type', $this->document_type)
            ->where('document_id', $this->document_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id');

        $position = $ids->search(fn ($id) => (int) $id === (int) $this->id);

        return [
            'index' => $position !== false ? ((int) $position + 1) : 1,
            'total' => $ids->count(),
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
}
