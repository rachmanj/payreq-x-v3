<?php

namespace Tests\Feature\Help;

use App\Models\HelpFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_guest_cannot_submit_feedback(): void
    {
        $this->postJson(route('help.feedback'), [
            'type' => 'bug',
            'title' => 'Broken',
            'body' => 'Details',
        ])
            ->assertStatus(401)
            ->assertUnauthorized();
    }

    public function test_user_without_permission_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('help.feedback'), [
                'type' => 'feature',
                'title' => 'Idea',
                'body' => 'Body',
            ])
            ->assertForbidden();
    }

    public function test_user_with_permission_can_submit_and_stores_row(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_help');

        $response = $this->actingAs($user)
            ->postJson(route('help.feedback'), [
                'type' => 'bug',
                'title' => 'Broken report',
                'body' => 'It happens when I click save.',
                'steps_to_reproduce' => 'Open X, click Y',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'ok')
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('help_feedbacks', [
            'user_id' => $user->id,
            'type' => 'bug',
            'title' => 'Broken report',
            'steps_to_reproduce' => 'Open X, click Y',
        ]);

        $this->assertSame(1, HelpFeedback::query()->count());
    }

    public function test_validation_requires_title_and_body(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_help');

        $this->actingAs($user)
            ->postJson(route('help.feedback'), [])
            ->assertStatus(422);
    }
}
