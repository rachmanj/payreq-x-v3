<?php

namespace Tests\Feature\Notulen;

use App\Jobs\ProcessMeeting;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MeetingUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('notulen');
    }

    public function test_guest_cannot_upload(): void
    {
        $file = UploadedFile::fake()->create('notulen.pdf', 100, 'application/pdf');

        $this->post(route('notulen.meetings.store'), [
            'title' => 'Rapat Test',
            'file' => $file,
        ])->assertRedirect(route('login'));
    }

    public function test_forbidden_without_upload_permission(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('notulen.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->postJson(route('notulen.meetings.store'), [
                'title' => 'Rapat Test',
                'file' => $file,
            ])
            ->assertForbidden();
    }

    public function test_upload_dispatches_process_meeting_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->givePermissionTo('upload_notulen');

        $file = UploadedFile::fake()->create('notulen.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('notulen.meetings.store'), [
                'title' => 'Rapat Anggaran',
                'meeting_date' => '2026-02-01',
                'file' => $file,
            ]);

        $response->assertRedirect(route('notulen.meetings.index'));

        $this->assertDatabaseHas('meetings', [
            'title' => 'Rapat Anggaran',
            'status' => Meeting::STATUS_PENDING,
            'uploaded_by' => $user->id,
        ]);

        Queue::assertPushed(ProcessMeeting::class);
    }

    public function test_validation_requires_title_and_pdf(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('upload_notulen');

        $this->actingAs($user)
            ->post(route('notulen.meetings.store'), [])
            ->assertSessionHasErrors(['title', 'file']);
    }
}
