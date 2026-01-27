<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pcbc extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project', 'code');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getSystemVarianceAttribute(): float
    {
        return ($this->system_amount ?? 0) - ($this->fisik_amount ?? 0);
    }

    public function getSapVarianceAttribute(): float
    {
        return ($this->sap_amount ?? 0) - ($this->fisik_amount ?? 0);
    }
}
