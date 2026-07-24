<?php

namespace Tests\Feature;

use App\Models\PeriodeAnggaran;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodeAnggaranBulkGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedProjects();
    }

    public function test_bulk_generate_creates_rows_for_multiple_projects_and_types(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reports.periode-anggaran.bulk-generate'), [
            'periode' => '2026-08',
            'types' => ['anggaran', 'ofr'],
            'projects' => ['000H', '017C'],
            'is_active' => 'yes',
            'description' => 'August setup',
            'deactivate_previous' => '1',
        ]);

        $response->assertRedirect(route('reports.periode-anggaran.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '000H',
            'periode_type' => 'anggaran',
            'periode' => '2026-08-01',
            'is_active' => 1,
            'description' => 'August setup',
        ]);
        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '017C',
            'periode_type' => 'ofr',
            'periode' => '2026-08-01',
            'is_active' => 1,
        ]);

        $this->assertSame(4, PeriodeAnggaran::query()->count());
    }

    public function test_bulk_generate_skips_existing_project_type_periode_combinations(): void
    {
        $user = User::factory()->create();

        PeriodeAnggaran::create([
            'periode' => '2026-08-01',
            'project' => '000H',
            'periode_type' => 'anggaran',
            'is_active' => 1,
            'description' => 'Existing',
        ]);

        $this->actingAs($user)->post(route('reports.periode-anggaran.bulk-generate'), [
            'periode' => '2026-08',
            'types' => ['anggaran', 'ofr'],
            'projects' => ['000H'],
            'is_active' => 'yes',
            'deactivate_previous' => '1',
        ])->assertRedirect(route('reports.periode-anggaran.index'));

        $this->assertSame(2, PeriodeAnggaran::query()->count());
        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '000H',
            'periode_type' => 'ofr',
            'periode' => '2026-08-01',
        ]);
    }

    public function test_bulk_generate_deactivates_previous_active_period_for_same_project_and_type(): void
    {
        $user = User::factory()->create();

        $previous = PeriodeAnggaran::create([
            'periode' => '2026-07-01',
            'project' => '000H',
            'periode_type' => 'anggaran',
            'is_active' => 1,
            'description' => 'July',
        ]);

        $this->actingAs($user)->post(route('reports.periode-anggaran.bulk-generate'), [
            'periode' => '2026-08',
            'types' => ['anggaran'],
            'projects' => ['000H'],
            'is_active' => 'yes',
            'deactivate_previous' => '1',
        ])->assertRedirect(route('reports.periode-anggaran.index'));

        $this->assertSame(0, (int) $previous->fresh()->is_active);
        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '000H',
            'periode_type' => 'anggaran',
            'periode' => '2026-08-01',
            'is_active' => 1,
        ]);
    }

    public function test_bulk_generate_all_projects_sentinel_uses_active_selectable_projects(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('reports.periode-anggaran.bulk-generate'), [
            'periode' => '2026-08',
            'types' => ['anggaran'],
            'projects' => ['all'],
            'is_active' => 'yes',
            'deactivate_previous' => '1',
        ])->assertRedirect(route('reports.periode-anggaran.index'));

        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '000H',
            'periode_type' => 'anggaran',
            'periode' => '2026-08-01',
        ]);
        $this->assertDatabaseHas('periode_anggarans', [
            'project' => '017C',
            'periode_type' => 'anggaran',
            'periode' => '2026-08-01',
        ]);
        $this->assertDatabaseMissing('periode_anggarans', [
            'project' => '023C',
            'periode_type' => 'anggaran',
            'periode' => '2026-08-01',
        ]);
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
