<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsTo(User::class, 'user_id');
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
        return $this->belongsTo(Rab::class);
    }
}
