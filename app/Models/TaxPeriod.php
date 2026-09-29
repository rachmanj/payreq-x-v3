<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPeriod extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'snapshot_json' => 'array',
        'prepared_at' => 'datetime',
        'approved_at' => 'datetime',
        'filed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
}
