<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        $bankName = $this->bank?->name ?? 'n/a';

        return "{$this->label} — {$bankName} ({$this->account_number}) — {$this->account_name}";
    }
}
