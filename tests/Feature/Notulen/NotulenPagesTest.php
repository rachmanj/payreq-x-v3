<?php

namespace Tests\Feature\Notulen;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotulenPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ask_page_renders_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');

        $this->actingAs($user)
            ->get(route('notulen.ask.index'))
            ->assertOk()
            ->assertSee('Tanya Notulen Rapat');
    }

    public function test_meetings_page_renders_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');

        $this->actingAs($user)
            ->get(route('notulen.meetings.index'))
            ->assertOk()
            ->assertSee('Notulen Rapat');
    }
}
