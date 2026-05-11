<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_requesting_menu_search_items(): void
    {
        $this->get(route('menu.search.items'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_receives_json_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('menu.search.items'))
            ->assertOk()
            ->assertJsonStructure(['items']);
    }
}
