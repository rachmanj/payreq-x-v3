<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'line_no',
        'account_code',
        'debit_credit',
        'amount',
        'currency',
        'fc_amount',
        'exchange_rate',
        'project',
        'cost_center',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fc_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
