<?php

namespace Database\Factories;

use App\Models\SapBusinessPartner;
use App\Models\UtilityVendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtilityVendor>
 */
class UtilityVendorFactory extends Factory
{
    protected $model = UtilityVendor::class;

    public function definition(): array
    {
        return [
            'jenis_utilitas' => fake()->unique()->randomElement(['pln', 'pdam', 'telkom']),
            'sap_business_partner_id' => null,
        ];
    }

    public function withSupplier(?SapBusinessPartner $partner = null): static
    {
        return $this->state(function () use ($partner) {
            $partner ??= SapBusinessPartner::query()->create([
                'code' => 'V'.strtoupper(fake()->unique()->bothify('???###')),
                'name' => fake()->company(),
                'type' => SapBusinessPartner::TYPE_SUPPLIER,
                'active' => true,
            ]);

            return [
                'sap_business_partner_id' => $partner->id,
            ];
        });
    }
}
