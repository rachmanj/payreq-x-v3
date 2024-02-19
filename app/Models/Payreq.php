<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Outgoing;

class Payreq extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function approval_plans()
    {
        return $this->hasMany(ApprovalPlan::class);
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

    public function last_outgoing_date()
    {
        $outgoings = $this->outgoings;

        // check if payreq amount === sum of outgoings
        if ($outgoings->sum('amount') < $this->amount) {
            return null;
        } else {
            $lastOutgoing = $outgoings->sortByDesc('created_at')->first();

            return $lastOutgoing->outgoing_date;
        }
    }
}
