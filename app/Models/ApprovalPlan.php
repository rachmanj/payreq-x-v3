<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function approvers()
    {
        return $this->hasMany(Approver::class, 'approval_id', 'id');
    }

    public function payreq()
    {
        return $this->belongsTo(PayReq::class, 'payreq_id', 'id');
    }
}
