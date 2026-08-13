<?php

namespace Tests\Feature;

use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VerificationIndexDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_verifications_data(): void
    {
        $this->getJson(route('verifications.data'))
            ->assertUnauthorized();
    }

    public function test_admin_data_paginates_and_marks_account_completeness(): void
    {
        $admin = $this->makeAdmin();
        $requestor = User::factory()->create(['name' => 'Rina Requestor', 'project' => '000H']);

        $complete = $this->makeRealization($requestor, 'VER-COMPLETE-1', 'PRQ-COMPLETE-1', '000H');
        RealizationDetail::query()->create([
            'realization_id' => $complete->id,
            'description' => 'Fuel',
            'amount' => 1000,
            'account_id' => 1,
        ]);

        $incomplete = $this->makeRealization($requestor, 'VER-INCOMPLETE-1', 'PRQ-INCOMPLETE-1', '000H');
        RealizationDetail::query()->create([
            'realization_id' => $incomplete->id,
            'description' => 'Hotel',
            'amount' => 2000,
            'account_id' => null,
        ]);

        $journaled = $this->makeRealization($requestor, 'VER-JOURNALED-1', 'PRQ-JOURNALED-1', '000H', [
            'verification_journal_id' => 99,
        ]);
        RealizationDetail::query()->create([
            'realization_id' => $journaled->id,
            'description' => 'Already journaled',
            'amount' => 500,
            'account_id' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('verifications.data').'?'.$this->datatablesQuery());

        $response->assertOk();

        $rows = collect($response->json('data'));
        $this->assertTrue($rows->contains('realization_no', 'VER-COMPLETE-1'));
        $this->assertTrue($rows->contains('realization_no', 'VER-INCOMPLETE-1'));
        $this->assertFalse($rows->contains('realization_no', 'VER-JOURNALED-1'));

        $completeRow = $rows->firstWhere('realization_no', 'VER-COMPLETE-1');
        $this->assertStringContainsString('COMPLETE', $completeRow['is_complete']);
        $this->assertSame('Rina Requestor', $completeRow['requestor']);
        $this->assertSame('PRQ-COMPLETE-1', $completeRow['payreq_no']);

        $incompleteRow = $rows->firstWhere('realization_no', 'VER-INCOMPLETE-1');
        $this->assertStringContainsString('INCOMPLETE', $incompleteRow['is_complete']);
    }

    public function test_admin_can_search_by_realization_number(): void
    {
        $admin = $this->makeAdmin();
        $requestor = User::factory()->create(['project' => '000H']);

        $this->makeRealization($requestor, 'VER-KEEP-1', 'PRQ-KEEP-1', '000H');
        $this->makeRealization($requestor, 'VER-FIND-ME', 'PRQ-FIND-1', '000H');

        $response = $this->actingAs($admin)
            ->getJson(route('verifications.data').'?'.$this->datatablesQuery('VER-FIND-ME'));

        $response->assertOk();

        $rows = collect($response->json('data'));
        $this->assertCount(1, $rows);
        $this->assertSame('VER-FIND-ME', $rows->first()['realization_no']);
    }

    public function test_cashier_only_sees_000h_and_aps_projects(): void
    {
        $cashier = $this->makeRoleUser('cashier', '000H');
        $requestor = User::factory()->create(['project' => '000H']);

        $this->makeRealization($requestor, 'VER-000H', 'PRQ-000H', '000H');
        $this->makeRealization($requestor, 'VER-APS', 'PRQ-APS', 'APS');
        $this->makeRealization($requestor, 'VER-022C', 'PRQ-022C', '022C');

        $response = $this->actingAs($cashier)
            ->getJson(route('verifications.data').'?'.$this->datatablesQuery());

        $response->assertOk();

        $numbers = collect($response->json('data'))->pluck('realization_no');
        $this->assertTrue($numbers->contains('VER-000H'));
        $this->assertTrue($numbers->contains('VER-APS'));
        $this->assertFalse($numbers->contains('VER-022C'));
    }

    public function test_regular_user_only_sees_own_project(): void
    {
        $user = User::factory()->create(['project' => '017C']);
        $requestor = User::factory()->create(['project' => '017C']);

        $this->makeRealization($requestor, 'VER-017C', 'PRQ-017C', '017C');
        $this->makeRealization($requestor, 'VER-000H', 'PRQ-000H', '000H');

        $response = $this->actingAs($user)
            ->getJson(route('verifications.data').'?'.$this->datatablesQuery());

        $response->assertOk();

        $numbers = collect($response->json('data'))->pluck('realization_no');
        $this->assertTrue($numbers->contains('VER-017C'));
        $this->assertFalse($numbers->contains('VER-000H'));
    }

    private function makeAdmin(): User
    {
        return $this->makeRoleUser('admin', '000H');
    }

    private function makeRoleUser(string $roleName, string $project): User
    {
        Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['guard_name' => 'web']
        );

        $user = User::factory()->create(['project' => $project]);
        $user->assignRole($roleName);

        return $user;
    }

    private function makeRealization(User $requestor, string $nomor, string $payreqNo, string $project, array $overrides = []): Realization
    {
        $payreq = Payreq::query()->create([
            'user_id' => $requestor->id,
            'nomor' => $payreqNo,
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'project' => $project,
            'remarks' => 'Test payreq',
        ]);

        return Realization::query()->create(array_merge([
            'nomor' => $nomor,
            'payreq_id' => $payreq->id,
            'user_id' => $requestor->id,
            'project' => $project,
            'status' => 'approved',
            'verification_journal_id' => null,
        ], $overrides));
    }

    private function datatablesQuery(string $search = ''): string
    {
        $params = [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => $search, 'regex' => 'false'],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'realization_no', 'name' => 'realization_no', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'date', 'name' => 'date', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'payreq_no', 'name' => 'payreq_no', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'requestor', 'name' => 'requestor', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'project', 'name' => 'project', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'is_complete', 'name' => 'is_complete', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
            ],
        ];

        return http_build_query($params);
    }
}
