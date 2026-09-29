<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpnInputSync extends Model
{
    protected $table = 'ppn_input_sync';

    protected $guarded = [];

    protected $casts = [
        'creation_date' => 'date',
        'posting_date' => 'date',
        'faktur_date' => 'date',
        'amount' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
