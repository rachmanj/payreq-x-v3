<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpnSyncRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public static function lastSuccessful(): ?self
    {
        return self::query()
            ->where('status', 'success')
            ->orderByDesc('finished_at')
            ->first();
    }

    public static function lastRun(): ?self
    {
        return self::query()
            ->orderByDesc('started_at')
            ->first();
    }

    public function isStaleAlert(): bool
    {
        if ($this->status === 'failed') {
            return true;
        }

        return false;
    }

    public static function shouldShowHealthAlert(): bool
    {
        $last = self::lastRun();
        if ($last === null) {
            return true;
        }

        if ($last->status === 'failed') {
            return true;
        }

        $lastSuccess = self::lastSuccessful();
        if ($lastSuccess === null || $lastSuccess->finished_at === null) {
            return true;
        }

        return $lastSuccess->finished_at->lt(now()->subDays(2));
    }
}
