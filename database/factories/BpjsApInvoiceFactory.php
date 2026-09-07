<?php

namespace Database\Factories;

use App\Models\BpjsApInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BpjsApInvoice>
 */
class BpjsApInvoiceFactory extends Factory
{
    protected $model = BpjsApInvoice::class;

    public function definition(): array
    {
        $periode = now()->format('Y-m');
        $docDate = Carbon::today();

        return [
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => $periode,
            'amount' => fake()->randomFloat(2, 1000000, 50000000),
            'doc_date' => $docDate->toDateString(),
            'due_date' => $docDate->copy()->addDays(30)->toDateString(),
            'num_at_card' => Carbon::createFromFormat('Y-m', $periode)->format('n').'/'.Carbon::createFromFormat('Y-m', $periode)->format('y'),
            'label' => 'BPJS Kesehatan HO per '.now()->translatedFormat('F Y'),
            'status' => BpjsApInvoice::STATUS_PENDING,
            'paid_amount' => 0,
        ];
    }

    public function posted(): static
    {
        return $this->state(fn () => [
            'status' => BpjsApInvoice::STATUS_POSTED,
            'sap_doc_num' => (string) fake()->unique()->numberBetween(10000, 99999),
            'sap_doc_entry' => fake()->unique()->numberBetween(10000, 99999),
            'submitted_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => BpjsApInvoice::STATUS_FAILED,
            'sap_error_message' => 'SAP submission failed',
        ]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $amount = (float) ($attributes['amount'] ?? 0);

            return [
                'status' => BpjsApInvoice::STATUS_PAID,
                'paid_amount' => $amount,
                'paid_at' => now()->toDateString(),
                'sap_doc_num' => (string) fake()->unique()->numberBetween(10000, 99999),
                'sap_doc_entry' => fake()->unique()->numberBetween(10000, 99999),
                'submitted_at' => now(),
            ];
        });
    }
}
