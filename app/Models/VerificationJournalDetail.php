<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationJournalDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function verificationJournal()
    {
        return $this->belongsTo(VerificationJournal::class);
    }
}
