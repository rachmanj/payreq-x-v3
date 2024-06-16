<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Realization extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payreq()
    {
        return $this->belongsTo(Payreq::class)->withDefault([
            'payreq_no' => 'n/a',
        ]);
    }

    public function realizationDetails()
    {
        return $this->hasMany(RealizationDetail::class);
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault([
            'name' => 'n/a',
        ]);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function verification()
    {
        return $this->hasOne(Verification::class);
    }

    public function journal()
    {
        return $this->belogsTo(Journal::class);
    }

    public function verificationJournal()
    {
        return $this->belongsTo(VerificationJournal::class)->withDefault([
            'sap_journal_no' => 'n/a',
            'sap_posting_date' => 'n/a',
            'udpated_at' => 'n/a',
        ]);
    }
}
