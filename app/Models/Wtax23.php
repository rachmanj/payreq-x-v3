<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wtax23 extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'create_date' => 'date:Y-m-d',
        'posting_date' => 'date:Y-m-d',
    ];
}
