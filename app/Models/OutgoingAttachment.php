<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingAttachment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'verification_result' => 'array',
    ];

    public function outgoing(): BelongsTo
    {
        return $this->belongsTo(Outgoing::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getVerificationStatusBadgeAttribute(): string
    {
        return match ($this->verification_status) {
            'verified' => '<span class="badge badge-success">✓ Sesuai</span>',
            'mismatch' => '<span class="badge badge-danger">✗ Tidak Sesuai</span>',
            'failed' => '<span class="badge badge-warning">⚠️ Gagal Verifikasi</span>',
            default => '<span class="badge badge-secondary">⏳ Memverifikasi</span>',
        };
    }
}
