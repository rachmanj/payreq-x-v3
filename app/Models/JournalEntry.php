<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'date',
        'memo',
        'reference',
        'journal_entry_template_id',
        'created_by',
        'sap_journal_no',
        'sap_je_jdt_num',
        'sap_posting_date',
        'sap_submission_status',
        'sap_submission_attempts',
        'sap_submission_error',
        'sap_submitted_at',
        'sap_submitted_by',
        'sap_reversed_at',
        'sap_reversed_by',
        'sap_reversal_reason',
        'sap_reversal_journal_no',
    ];

    protected $casts = [
        'date' => 'date',
        'sap_posting_date' => 'date',
        'sap_submitted_at' => 'datetime',
        'sap_reversed_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_no');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(JournalEntryTemplate::class, 'journal_entry_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sapSubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sap_submitted_by');
    }

    public function sapReversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sap_reversed_by');
    }

    public function submissionLogs(): HasMany
    {
        return $this->hasMany(SapSubmissionLog::class);
    }

    public function isPosted(): bool
    {
        return $this->sap_submission_status === 'success' && ! empty($this->sap_journal_no);
    }

    public function isReversed(): bool
    {
        return ! empty($this->sap_reversed_at);
    }

    public function isEditable(): bool
    {
        return $this->sap_submission_status !== 'success' && ! $this->isReversed();
    }

    public function totalDebit(): float
    {
        return (float) $this->lines()->where('debit_credit', 'debit')->sum('amount');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines()->where('debit_credit', 'credit')->sum('amount');
    }
}
