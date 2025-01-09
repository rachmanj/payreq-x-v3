<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationJournal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function realization_details()
    {
        return $this->hasMany(RealizationDetail::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verificationJournalDetails()
    {
        return $this->hasMany(VerificationJournalDetail::class);
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by')->withDefault([
            'name' => 'N/A'
        ]);
    }

    public function realizations()
    {
        return $this->hasMany(Realization::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}
