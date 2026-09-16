<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\HandlesApprovalActivityDetails;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandlesApprovalActivityDetailsTest extends TestCase
{
    use RefreshDatabase;

    private object $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new class
        {
            use HandlesApprovalActivityDetails;

            public function fetchForProjects(iterable $projects)
            {
                return $this->openActivitiesForProjects($projects);
            }

            public function fetchForProject(?string $project)
            {
                return $this->openActivitiesForProject($project);
            }
        };
    }

    protected function createActivity(array $attributes): Activity
    {
        $user = User::factory()->create();

        return Activity::query()->create(array_merge([
            'code' => 'KEG-'.uniqid(),
            'name' => 'Test Activity',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'status' => 'open',
            'created_by' => $user->id,
        ], $attributes));
    }

    public function test_open_activity_for_detail_project_appears_when_header_project_differs(): void
    {
        $reklasActivity = $this->createActivity([
            'code' => 'KEG-2026-003',
            'name' => 'Reklasifikasi 022C',
            'project' => '022C',
        ]);

        $this->createActivity([
            'code' => 'KEG-2026-099',
            'name' => 'Other Project',
            'project' => '999Z',
        ]);

        $activities = $this->handler->fetchForProjects(['000H', '022C']);

        $this->assertTrue($activities->contains('id', $reklasActivity->id));
    }

    public function test_open_activity_for_detail_project_does_not_appear_without_matching_detail_project(): void
    {
        $reklasActivity = $this->createActivity([
            'code' => 'KEG-2026-003',
            'name' => 'Reklasifikasi 022C',
            'project' => '022C',
        ]);

        $activities = $this->handler->fetchForProjects(['000H']);

        $this->assertFalse($activities->contains('id', $reklasActivity->id));
    }

    public function test_open_activity_with_null_project_always_appears(): void
    {
        $globalActivity = $this->createActivity([
            'code' => 'KEG-2026-GLOBAL',
            'name' => 'Global Activity',
            'project' => null,
        ]);

        $activitiesForHeaderOnly = $this->handler->fetchForProjects(['000H']);
        $activitiesForEmptyProjects = $this->handler->fetchForProjects([]);

        $this->assertTrue($activitiesForHeaderOnly->contains('id', $globalActivity->id));
        $this->assertTrue($activitiesForEmptyProjects->contains('id', $globalActivity->id));
    }

    public function test_non_open_activities_do_not_appear(): void
    {
        $closedActivity = $this->createActivity([
            'code' => 'KEG-2026-CLOSED',
            'name' => 'Closed Activity',
            'project' => '022C',
            'status' => 'closed',
        ]);

        $activities = $this->handler->fetchForProjects(['000H', '022C']);

        $this->assertFalse($activities->contains('id', $closedActivity->id));
    }

    public function test_open_activities_for_project_delegates_to_projects_helper(): void
    {
        $headerActivity = $this->createActivity([
            'code' => 'KEG-2026-HEADER',
            'name' => 'Header Project Activity',
            'project' => '000H',
        ]);

        $singleProjectActivities = $this->handler->fetchForProject('000H');
        $multiProjectActivities = $this->handler->fetchForProjects(['000H']);

        $this->assertEquals(
            $singleProjectActivities->pluck('id')->all(),
            $multiProjectActivities->pluck('id')->all()
        );
        $this->assertTrue($singleProjectActivities->contains('id', $headerActivity->id));
    }
}
