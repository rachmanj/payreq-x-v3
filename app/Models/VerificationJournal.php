<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationJournal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function realization_details()
    {
        return $this->hasMany(RealizationDetail::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
