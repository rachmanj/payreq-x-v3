<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function payreq(): BelongsTo
    {
        return $this->belongsTo(Payreq::class);
    }

    public function utilityApInvoice(): BelongsTo
    {
        return $this->belongsTo(UtilityApInvoice::class);
    }

    public function getSapStatusAttribute(): ?string
    {
        return $this->utilityApInvoice?->status;
    }

    public function getSapApDocNumAttribute(): ?string
    {
        return $this->utilityApInvoice?->sap_doc_num;
    }

    public function getSapApDocEntryAttribute(): ?string
    {
        $docEntry = $this->utilityApInvoice?->sap_doc_entry;

        return $docEntry !== null ? (string) $docEntry : null;
    }

    public function scopeUnclaimed(Builder $query): Builder
    {
        return $query->whereNull('payreq_id');
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
            default => 'neutral',
        };
    }
}
