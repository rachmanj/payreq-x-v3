<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapSubmissionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'verification_journal_id',
        'user_id',
        'status',
        'error_message',
        'sap_response',
        'sap_journal_number',
        'attempt_number',
    ];

    protected $casts = [
        'sap_response' => 'array',
    ];

    public function verificationJournal()
    {
        return $this->belongsTo(VerificationJournal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
