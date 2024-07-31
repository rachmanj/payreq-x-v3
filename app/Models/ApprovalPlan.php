<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id')->withDefault([
            'name' => 'N/A',
        ]);
    }

    public function payreq()
    {
        return $this->belongsTo(PayReq::class, 'document_id', 'id');
    }

    public function realization()
    {
        return $this->belongsTo(Realization::class, 'document_id', 'id');
    }

    public function anggaran()
    {
        return $this->belongsTo(Anggaran::class, 'document_id', 'id');
    }
}
