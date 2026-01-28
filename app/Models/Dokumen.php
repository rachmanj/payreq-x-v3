<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getPeriodeAttribute($value)
    {
        return date('F Y', strtotime($value));
    }

    public function getDokumenDateAttribute($value)
    {
        return date('d M Y', strtotime($value));
    }

    public function getFilename1Attribute($value)
    {
        return asset('dokumens/' . $value);
    }

    public function getFilename2Attribute($value)
    {
        return asset('file_upload/' . $value);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCreatedByNameAttribute()
    {
        if ($this->relationLoaded('createdBy') && $this->createdBy) {
            return $this->createdBy->name;
        }
        
        $user = $this->createdBy;
        return $user ? $user->name : '-';
    }

    public function getUpdatedByNameAttribute()
    {
        if ($this->relationLoaded('updatedBy') && $this->updatedBy) {
            return $this->updatedBy->name;
        }
        
        $user = $this->updatedBy;
        return $user ? $user->name : '-';
    }

    public function giro()
    {
        return $this->belongsTo(Giro::class);
    }
}
