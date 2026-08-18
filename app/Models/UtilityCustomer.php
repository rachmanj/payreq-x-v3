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
}
