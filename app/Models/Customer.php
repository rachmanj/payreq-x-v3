<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sapBusinessPartner()
    {
        return $this->belongsTo(SapBusinessPartner::class, 'code', 'code');
    }

    public function hasSapBusinessPartner(): bool
    {
        return $this->code && $this->sapBusinessPartner !== null;
    }

    public function getSapBusinessPartnerAttribute()
    {
        if (!$this->code) {
            return null;
        }
        
        return SapBusinessPartner::where('code', $this->code)->first();
    }
}
