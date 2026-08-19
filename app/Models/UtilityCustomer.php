<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilityCustomer extends Model
{
    public const JENIS_UTILITAS = [
        'pln' => 'PLN',
        'pdam' => 'PDAM',
        'telkom' => 'TELKOM',
    ];

    public const TIPE = [
        'postpaid' => 'Pascabayar',
        'prepaid' => 'Token / Prabayar',
    ];

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePostpaid(Builder $query): Builder
    {
        return $query->where('tipe', 'postpaid');
    }

    public function scopePrepaid(Builder $query): Builder
    {
        return $query->where('tipe', 'prepaid');
    }
}
