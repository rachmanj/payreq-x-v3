<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AnggaranReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'recalculate_release', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'anggaran_bulk_activate_deactivate', 'guard_name' => 'web']);
    }

    public function test_guest_cannot_post_reports_anggaran_recalculate(): void
    {
        $this->post(route('reports.anggaran.recalculate'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_permission_cannot_recalculate_release_totals(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reports.anggaran.recalculate'))
            ->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_recalculate_release_totals(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('recalculate_release');

        $this->actingAs($user)
            ->post(route('reports.anggaran.recalculate'))
            ->assertRedirect(route('reports.anggaran.index'));
    }

    public function test_bulk_update_many_requires_bulk_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reports.anggaran.update_many'), ['id' => [999999]])
            ->assertForbidden();
    }
}
