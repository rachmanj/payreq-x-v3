<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentNumber;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAnggaranProjectSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedProjects();
    }

    public function test_unrestricted_user_sees_active_selectable_projects_on_create_form(): void
    {
        $user = $this->makeUser('000H');

        $options = $this->projectOptionsFromCreatePage($user);

        $this->assertContains('000H', $options);
        $this->assertContains('001H', $options);
        $this->assertContains('017C', $options);
        $this->assertNotContains('023C', $options);
    }

    public function test_restricted_user_sees_only_own_project_on_create_form(): void
    {
        $user = $this->makeUser('017C');

        $options = $this->projectOptionsFromCreatePage($user);

        $this->assertSame(['017C'], $options);
    }

    public function test_restricted_user_cannot_submit_rab_for_another_project(): void
    {
        $user = $this->makeUser('017C');

        $this->actingAs($user)
            ->post(route('user-payreqs.anggarans.proses'), $this->validPayload('017C', '001H'))
            ->assertSessionHasErrors('project');
    }

    public function test_unrestricted_user_can_submit_rab_for_another_project(): void
    {
        $user = $this->makeUser('000H');

        $this->actingAs($user)
            ->post(route('user-payreqs.anggarans.proses'), $this->validPayload('000H', '017C'))
            ->assertSessionDoesntHaveErrors('project')
            ->assertRedirect(route('user-payreqs.anggarans.index'));
    }

    /**
     * @return array<int, string>
     */
    private function projectOptionsFromCreatePage(User $user): array
    {
        $this->seedDraftDocumentNumber($user->project);

        $response = $this->actingAs($user)
            ->get(route('user-payreqs.anggarans.create'));

        $response->assertOk();

        preg_match_all('/<select[^>]*id="project"[^>]*>.*?<\/select>/s', $response->getContent(), $selectMatches);
        $this->assertNotEmpty($selectMatches[0], 'Project select element was not found on the create form.');

        preg_match_all('/<option value="([^"]+)"/', $selectMatches[0][0], $optionMatches);

        return $optionMatches[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(string $userProject, string $rabProject): array
    {
        $this->seedDraftDocumentNumber($userProject);

        return [
            'button_type' => 'create',
            'nomor' => '26Q0007001',
            'project' => $rabProject,
            'description' => 'Test RAB description',
            'amount' => 1000,
            'rab_type' => 'periode',
            'usage' => 'user',
            'periode_anggaran' => '2026-07-01',
            'details' => [
                [
                    'description' => 'Line 1',
                    'qty' => 1,
                    'unit' => 'each',
                    'unit_price' => 1000,
                    'amount' => 1000,
                ],
            ],
        ];
    }

    private function seedProjects(): void
    {
        foreach ([
            ['code' => '000H', 'is_active' => true, 'is_selectable' => true],
            ['code' => '001H', 'is_active' => true, 'is_selectable' => true],
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

    private function seedDraftDocumentNumber(string $project): void
    {
        DocumentNumber::create([
            'document_type' => 'draft',
            'project' => $project,
            'year' => (int) date('Y'),
            'last_number' => 1,
        ]);
    }

    private function makeUser(string $project): User
    {
        $department = Department::create([
            'department_name' => 'Test Department',
            'akronim' => 'TD',
            'sap_code' => 'TD',
        ]);

        return User::factory()->create([
            'project' => $project,
            'department_id' => $department->id,
        ]);
    }
}
