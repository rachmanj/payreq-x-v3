<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Outgoing;

class Payreq extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'submit_at' => 'datetime',
    ];

    public function approval_plans()
    {
        return $this->hasMany(ApprovalPlan::class, 'document_id')
            ->where('document_type', 'payreq');
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

    public function outgoings()
    {
        return $this->hasMany(Outgoing::class);
    }

    public function realization()
    {
        return $this->hasOne(Realization::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id', 'id')->withDefault([
            'rab_no' => 'n/a',
        ]);
    }

    public function realization_details()
    {
        return $this->hasMany(RealizationDetail::class, 'payreq_id', 'id');
    }

    public function last_outgoing()
    {
        $outgoings = $this->outgoings;

        // check if payreq amount === sum of outgoings
        if ($outgoings->sum('amount') < $this->amount) {
            return null;
        } else {
            $lastOutgoing = $outgoings->sortByDesc('outgoing_date')->first();

            // return $lastOutgoing->outgoing_date;
            return $lastOutgoing;
        }
    }

    public function anggaran()
    {
        return $this->belongsTo(Anggaran::class, 'rab_id', 'id');
    }

    public function PayreqMigrasi()
    {
        return $this->hasOne(PayreqMigrasi::class);
    }
}
