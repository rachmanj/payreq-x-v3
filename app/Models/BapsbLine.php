<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BapsbLine extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'bilyet_date' => 'date',
        'cair_date' => 'date',
        'physical_present' => 'boolean',
    ];

    public function bapsb()
    {
        return $this->belongsTo(Bapsb::class);
    }

    public function bilyet()
    {
        return $this->belongsTo(Bilyet::class);
    }
}
