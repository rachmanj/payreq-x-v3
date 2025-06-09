<?php

namespace App\Models;

use App\Models\LotClaim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LotClaimAccommodation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'lot_claim_accommodations';

    public function lotClaim()
    {
        return $this->belongsTo(LotClaim::class);
    }
}
