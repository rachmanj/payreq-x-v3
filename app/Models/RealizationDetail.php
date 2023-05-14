<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RealizationDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function realization()
    {
        return $this->belongsTo(Realization::class);
    }
}
