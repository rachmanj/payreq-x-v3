<?php

namespace Tests\Feature;

use App\Models\PeriodeAnggaran;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PeriodeAnggaranGenerateMonthlyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedProjects();
    }

    public function test_command_creates_current_month_periods_for_active_selectable_projects(): void
    {
        Carbon::setTestNow('2026-08-01 00:10:00');

        $exitCode = Artisan::call('periode-anggaran:generate-monthly');

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '000H',
            'periode_type' => 'anggaran',
            'periode' => '2026-08-01',
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '017C',
            'periode_type' => 'ofr',
            'periode' => '2026-08-01',
            'is_active' => 1,
        ]);
        $this->assertSame(4, PeriodeAnggaran::query()->count());

        Carbon::setTestNow();
    }

    public function test_command_dry_run_does_not_persist_changes(): void
    {
        Carbon::setTestNow('2026-08-01 00:10:00');

        $exitCode = Artisan::call('periode-anggaran:generate-monthly', ['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, PeriodeAnggaran::query()->count());

        Carbon::setTestNow();
    }

    public function test_command_deactivates_previous_active_period(): void
    {
        Carbon::setTestNow('2026-08-01 00:10:00');

        $previous = PeriodeAnggaran::create([
            'periode' => '2026-07-01',
            'project' => '000H',
            'periode_type' => 'anggaran',
            'is_active' => 1,
            'description' => 'July',
        ]);

        Artisan::call('periode-anggaran:generate-monthly', [
            '--projects' => ['000H'],
            '--types' => 'anggaran',
        ]);

        $this->assertSame(0, (int) $previous->fresh()->is_active);
        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '000H',
            'periode_type' => 'anggaran',
            'periode' => '2026-08-01',
            'is_active' => 1,
        ]);

        Carbon::setTestNow();
    }

    private function seedProjects(): void
    {
        foreach ([
            ['code' => '000H', 'is_active' => true, 'is_selectable' => true],
            ['code' => '017C', 'is_active' => true, 'is_selectable' => true],
            ['code' => '023C', 'is_active' => false, 'is_selectable' => false],
        ] as $project) {
            Project::create([
                'code' => $project['code'],
                'name' => $project['code'],
                'sap_code' => $project['code'],
                'is_active' => $project['is_active'],
                'is_selectable' => $project['is_selectable'],
            ]);
        }
    }
}
