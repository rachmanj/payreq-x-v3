<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoicePaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('DDS_API_URL=http://dds.test');
        putenv('DDS_API_KEY=test-api-key');
        putenv('DDS_DEPARTMENT_CODE=');
    }

    public function test_dashboard_counts_waiting_and_paid_by_payment_date(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_ends_with($url, '/api/v1/departments')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'departments' => [
                            ['location_code' => '000HCASHO', 'name' => 'Cashier HO'],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, '/api/v1/departments/000HCASHO/invoices')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'invoices' => [
                            [
                                'id' => 1,
                                'amount' => 1000000,
                                'receive_date' => '2025-01-01',
                                'payment_date' => null,
                                'status' => 'open',
                            ],
                            [
                                'id' => 2,
                                'amount' => 500000,
                                'receive_date' => '2025-01-15',
                                'payment_date' => '2025-02-01',
                                'status' => 'open',
                            ],
                            [
                                'id' => 3,
                                'amount' => 250000,
                                'receive_date' => '2024-01-01',
                                'payment_date' => null,
                                'status' => 'overdue',
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create([
            'dds_department_code' => '000HCASHO',
        ]);

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.dashboard'))
            ->assertOk()
            ->assertJson([
                'total_invoices' => 3,
                'waiting_invoices' => 2,
                'paid_invoices' => 1,
                'overdue_invoices' => 2,
            ]);
    }

    public function test_department_scoped_endpoints_reject_invalid_department_code(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/v1/departments')) {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'departments' => [
                            ['location_code' => '000HCASHO', 'name' => 'Cashier HO'],
                        ],
                    ],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create([
            'dds_department_code' => 'INVALID',
        ]);

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.waiting'))
            ->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid department code',
            ])
            ->assertJsonFragment(['department_code' => 'INVALID']);
    }

    public function test_update_payment_sends_closed_status_and_paid_payment_status_to_dds(): void
    {
        Http::preventStrayRequests();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/v1/invoices/42/payment')) {
                return Http::response([
                    'success' => true,
                    'message' => 'Invoice payment updated successfully',
                    'data' => ['id' => 42],
                ]);
            }

            return Http::response(['success' => false], 404);
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('cashier.invoice-payment.update-payment', ['invoiceId' => 42]), [
                'payment_date' => '2025-08-20',
                'remarks' => 'Paid via bank transfer',
                'payment_project' => '001H',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/invoices/42/payment')
                && $request['payment_status'] === 'paid'
                && $request['status'] === 'closed'
                && $request['payment_date'] === '2025-08-20'
                && $request['payment_project'] === '001H'
                && ! array_key_exists('sap_doc', $request->data());
        });
    }
}
