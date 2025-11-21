<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
        'active',
        'start_date',
        'end_date',
        'project_manager',
        'metadata',
        'last_synced_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_synced_at' => 'datetime',
    ];
}
