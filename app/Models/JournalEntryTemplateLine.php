<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryTemplateLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_template_id',
        'line_no',
        'account_code',
        'debit_credit',
        'default_amount',
        'project',
        'cost_center',
        'description',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(JournalEntryTemplate::class, 'journal_entry_template_id');
    }
}
