<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationJournal extends Model
{
    use HasFactory;

    public const VALIDATION_PENDING = 'pending';

    public const VALIDATION_VALIDATED = 'validated';

    public const VALIDATION_REJECTED = 'rejected';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'sap_posting_date' => 'date',
        'sap_submitted_at' => 'datetime',
        'sap_reversed_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

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
            'name' => 'N/A',
        ]);
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'sap_reversed_by')->withDefault([
            'name' => 'N/A',
        ]);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by')->withDefault([
            'name' => 'N/A',
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
