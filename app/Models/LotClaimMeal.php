<?php

namespace App\Models;

use App\Models\LotClaim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LotClaimMeal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'lot_claim_meals';

    public function lotClaim()
    {
        return $this->belongsTo(LotClaim::class);
    }

    // Otomatis menghitung total_amount
    protected static function booted()
    {
        static::creating(function ($meal) {
            // Convert string values to numeric
            $peopleCount = intval($meal->people_count);
            $perPersonLimit = floatval(str_replace(',', '', $meal->per_person_limit));
            $frequency = intval($meal->frequency);

            // Calculate meal_amount
            $meal->meal_amount = $peopleCount * $perPersonLimit * $frequency;
        });

        static::updating(function ($meal) {
            // Convert string values to numeric
            $peopleCount = intval($meal->people_count);
            $perPersonLimit = floatval(str_replace(',', '', $meal->per_person_limit));
            $frequency = intval($meal->frequency);

            // Calculate meal_amount
            $meal->meal_amount = $peopleCount * $perPersonLimit * $frequency;
        });
    }
}
