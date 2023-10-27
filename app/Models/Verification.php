<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function realization_details()
    {
        return $this->hasMany(RealizationDetail::class);
    }

    public function realization()
    {
        return $this->belongsTo(Realization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
