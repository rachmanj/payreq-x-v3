<?php

namespace Tests\Unit;

use App\Models\TaxPeriod;
use App\Models\User;
use App\Services\TaxPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxPeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxPeriodService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxPeriodService::class);
    }

    public function test_save_reconciliation_snapshot_persists_aggregates_and_json(): void
    {
        $period = TaxPeriod::query()->create([
            'masa_pajak' => '2026-08',
            'tax_type' => 'ppn',
            'status' => 'open',
        ]);

        $result = [
            'masa_pajak' => '2026-08',
            'sap' => ['pk_total' => 100.0, 'pm_total' => 80.0],
            'app' => ['pk_total' => 90.0, 'pm_total' => 70.0],
            'totals' => [
                'kb_lb' => 20.0,
                'diff_sap_app' => 20.0,
                'diff_coretax_app' => null,
                'diff_pk_pm' => 20.0,
            ],
            'missing_faktur_exposure' => [],
            'reconciled_at' => now()->toIso8601String(),
        ];

        $updated = $this->service->saveReconciliationSnapshot($period, $result);

        $this->assertSame(100.0, (float) $updated->pk_total);
        $this->assertSame(80.0, (float) $updated->pm_total);
        $this->assertSame(20.0, (float) $updated->kb_lb);
        $this->assertSame(20.0, (float) $updated->diff_sap_app);
        $this->assertNull($updated->diff_coretax_app);
        $this->assertSame('2026-08', $updated->snapshot_json['masa_pajak']);
    }

    public function test_period_status_transitions_happy_path(): void
    {
        $user = User::factory()->create();
        $period = TaxPeriod::query()->create([
            'masa_pajak' => '2026-07',
            'tax_type' => 'ppn',
            'status' => 'open',
        ]);

        $this->service->prepare($period, $user);
        $period->refresh();
        $this->assertSame('prepared', $period->status);
        $this->assertSame($user->id, $period->prepared_by);

        $this->service->approve($period, $user);
        $period->refresh();
        $this->assertSame('approved', $period->status);

        $this->service->close($period, $user);
        $period->refresh();
        $this->assertSame('filed', $period->status);
        $this->assertNotNull($period->filed_at);

        $this->service->close($period, $user);
        $period->refresh();
        $this->assertSame('locked', $period->status);
        $this->assertSame($user->id, $period->closed_by);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $user = User::factory()->create();
        $period = TaxPeriod::query()->create([
            'masa_pajak' => '2026-06',
            'tax_type' => 'ppn',
            'status' => 'open',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->approve($period, $user);
    }

    public function test_reopen_requires_reason_and_logs_audit(): void
    {
        $user = User::factory()->create();
        $period = TaxPeriod::query()->create([
            'masa_pajak' => '2026-05',
            'tax_type' => 'ppn',
            'status' => 'locked',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->reopen($period, $user, '   ');

        $updated = $this->service->reopen($period, $user, 'Koreksi setelah review SPT');
        $this->assertSame('open', $updated->status);
        $this->assertCount(1, $updated->audit_log);
        $this->assertSame('Koreksi setelah review SPT', $updated->audit_log[0]['reason']);
    }
}
