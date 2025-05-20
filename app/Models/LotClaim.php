<?php

namespace App\Models;

use App\Models\User;
use App\Models\LotClaimMeal;
use App\Models\LotClaimTravel;
use App\Models\LotClaimAccommodation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LotClaim extends Model
{
    use HasFactory;

    protected $guarded = [];

    // protected $table = 'lot_claims';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function accommodations()
    {
        return $this->hasMany(LotClaimAccommodation::class, 'lot_claim_id');
    }

    public function travels()
    {
        return $this->hasMany(LotClaimTravel::class, 'lot_claim_id');
    }

    public function meals()
    {
        return $this->hasMany(LotClaimMeal::class, 'lot_claim_id');
    }
}
