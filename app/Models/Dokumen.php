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
        return asset('file_upload/' . $value);
    }

    public function getFilename2Attribute($value)
    {
        return asset('file_upload/' . $value);
    }

    public function getCreatedByNameAttribute()
    {
        return $this->belongsTo(User::class, 'created_by')->first()->name;
    }

    public function getUpdatedByNameAttribute()
    {
        return $this->belongsTo(User::class, 'updated_by')->first()->name;
    }

    public function giro()
    {
        return $this->belongsTo(Giro::class);
    }
}
