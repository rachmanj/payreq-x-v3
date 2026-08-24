<?php

namespace Database\Factories;

use App\Models\SapBusinessPartner;
use App\Models\UtilityApInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtilityApInvoice>
 */
class UtilityApInvoiceFactory extends Factory
{
    protected $model = UtilityApInvoice::class;

    public function definition(): array
    {
        $partner = SapBusinessPartner::query()->create([
            'code' => 'V'.strtoupper(fake()->unique()->bothify('???###')),
            'name' => fake()->company(),
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        return [
            'jenis_utilitas' => 'pln',
            'sap_business_partner_id' => $partner->id,
            'num_at_card' => 'PLN '.fake()->unique()->numerify('#/##'),
            'tax_code' => 'B100',
            'periode_summary' => now()->format('Y-m'),
            'total_amount' => fake()->randomFloat(2, 1000, 50000),
            'status' => UtilityApInvoice::STATUS_PENDING,
        ];
    }

    public function posted(): static
    {
        return $this->state(fn () => [
            'status' => UtilityApInvoice::STATUS_POSTED,
            'sap_doc_num' => (string) fake()->unique()->numberBetween(1000, 9999),
            'sap_doc_entry' => fake()->unique()->numberBetween(1000, 9999),
            'submitted_at' => now(),
        ]);
    }
}
