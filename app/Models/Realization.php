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
        return $this->belongsTo(Payreq::class);
    }

    public function realizationDetails()
    {
        return $this->hasMany(RealizationDetail::class);
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'user_id');
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
}
