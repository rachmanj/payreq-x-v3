<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rab extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function payreqs()
    {
        return $this->hasMany(Payreq::class, 'rab_id', 'id');
    }

    public function advance()
    {
        return Payreq::where('rab_id', $this->id)->whereNotNull('outgoing_date')->sum('payreq_idr');
    }

    public function realization()
    {
        return Payreq::where('rab_id', $this->id)->whereNotNull('realization_date')->sum('payreq_idr');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
