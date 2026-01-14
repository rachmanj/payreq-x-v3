<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapSubmissionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'verification_journal_id',
        'faktur_id',
        'document_type',
        'user_id',
        'submitted_by',
        'status',
        'error_message',
        'sap_error',
        'sap_response',
        'sap_journal_number',
        'sap_doc_num',
        'sap_doc_entry',
        'attempt_number',
    ];

    protected $casts = [
        'sap_response' => 'array',
    ];

    public function verificationJournal()
    {
        return $this->belongsTo(VerificationJournal::class);
    }

    public function faktur()
    {
        return $this->belongsTo(Faktur::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
