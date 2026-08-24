<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityVendor extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sapBusinessPartner(): BelongsTo
    {
        return $this->belongsTo(SapBusinessPartner::class);
    }
}
