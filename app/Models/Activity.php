<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $guarded = [];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function anggarans(): BelongsToMany
    {
        return $this->belongsToMany(Anggaran::class, 'activity_rab', 'activity_id', 'anggaran_id')
            ->withTimestamps();
    }

    public function realizations(): HasMany
    {
        return $this->hasMany(Realization::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeOpen($query);
    }

    public function isReklasifikasi(): bool
    {
        return $this->mode === 'reklasifikasi';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public static function generateCode(?string $year = null): string
    {
        $year = $year ?? now()->format('Y');
        $prefix = 'KEG-'.$year.'-';

        $lastCode = static::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $sequence = 1;
        if ($lastCode && preg_match('/-(\d+)$/', $lastCode, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
