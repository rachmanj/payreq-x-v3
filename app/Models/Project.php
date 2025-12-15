<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'sap_code',
        'name',
        'description',
        'is_active',
        'is_selectable',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_selectable' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function scopeSelectable($query)
    {
        return $query->where('is_selectable', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
