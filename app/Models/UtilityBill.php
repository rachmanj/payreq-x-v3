<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityBill extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tanggal_jatuh_tempo' => 'date',
        'tanggal_bayar' => 'date',
        'jumlah_tagihan' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(UtilityCustomer::class, 'utility_customer_id');
    }

    public function getStatusAttribute(): string
    {
        if ($this->tanggal_bayar) {
            return 'lunas';
        }

        if (! $this->tanggal_jatuh_tempo) {
            return 'belum';
        }

        if ($this->tanggal_jatuh_tempo < now()->toDateString()) {
            return 'telat';
        }
        if ($this->tanggal_jatuh_tempo <= now()->addDays(3)->toDateString()) {
            return 'mendekati';
        }

        return 'belum';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'lunas' => 'Lunas',
            'telat' => 'Telat',
            'mendekati' => 'Jatuh Tempo',
            default => 'Belum',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'lunas' => 'success',
            'telat' => 'danger',
            'mendekati' => 'warning',
            default => 'secondary',
        };
    }
}
