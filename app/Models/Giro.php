<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Giro extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function giro_details()
    {
        return $this->hasMany(GiroDetail::class);
    }
}
