<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapCostCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'segment',
        'department',
        'active',
        'metadata',
        'last_synced_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
