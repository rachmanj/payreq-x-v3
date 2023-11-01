<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function realizations()
    {
        return $this->hasMany(Realization::class);
    }

    public function realization_details()
    {
        return $this->hasMany(RealizationDetail::class);
    }
}
