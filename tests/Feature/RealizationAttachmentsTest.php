<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RealizationAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'akses_realization_attachments',
            'create_realization_attachments',
            'delete_realization_attachments',
            'realization_attachments_scope_bo',
        ] as $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web'],
            );
        }
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('cashier.realization-attachments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_receives_access_denied_when_visiting_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard.index'))
            ->get(route('cashier.realization-attachments.index'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('alert_type', 'error');
    }

    public function test_user_with_access_permission_can_open_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_realization_attachments');

        $this->actingAs($user)
            ->get(route('cashier.realization-attachments.index'))
            ->assertOk();
    }
}
