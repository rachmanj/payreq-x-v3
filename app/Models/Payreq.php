<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payreq extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function outgongs()
    {
        return $this->hasMany(Outgoing::class);
    }

    public function relization()
    {
        return $this->hasOne(Relization::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class);
    }
}
