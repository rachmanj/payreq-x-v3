<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiroDetail extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function giro()
    {
        return $this->belongsTo(Giro::class);
    }
}
