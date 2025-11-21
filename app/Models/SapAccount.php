<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'category',
        'active',
        'postable',
        'metadata',
        'last_synced_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'postable' => 'boolean',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
