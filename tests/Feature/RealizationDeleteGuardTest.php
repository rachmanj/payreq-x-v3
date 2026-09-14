<?php

namespace Tests\Feature;

use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealizationDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    private const NOT_FOUND_MESSAGE = 'Realisasi tidak ditemukan atau sudah dihapus.';

    public function test_destroy_nonexistent_realization_redirects_with_error_without_500(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->delete(route('user-payreqs.realizations.destroy', 99999));

        $response->assertRedirect(route('user-payreqs.realizations.index'));
        $response->assertSessionHas('error', self::NOT_FOUND_MESSAGE);
        $response->assertStatus(302);
    }

    public function test_regular_user_cannot_delete_other_users_realization(): void
    {
        $owner = $this->makeUser();
        $otherUser = $this->makeUser();
        $realization = $this->makeDraftRealization($owner);

        $response = $this->actingAs($otherUser)->delete(route('user-payreqs.realizations.destroy', $realization->id));

        $response->assertRedirect(route('user-payreqs.realizations.index'));
        $response->assertSessionHas('error', self::NOT_FOUND_MESSAGE);
        $this->assertDatabaseHas('realizations', ['id' => $realization->id]);
        $this->assertDatabaseHas('realization_details', ['realization_id' => $realization->id]);
    }

    public function test_owner_can_delete_own_realization_and_restore_payreq_status(): void
    {
        $user = $this->makeUser();
        $realization = $this->makeDraftRealization($user);
        $payreqId = $realization->payreq_id;

        Payreq::query()->whereKey($payreqId)->update(['status' => 'realization']);

        $response = $this->actingAs($user)->delete(route('user-payreqs.realizations.destroy', $realization->id));

        $response->assertRedirect(route('user-payreqs.realizations.index'));
        $response->assertSessionHas('success', 'Realisasi berhasil dihapus.');
        $this->assertDatabaseMissing('realizations', ['id' => $realization->id]);
        $this->assertDatabaseMissing('realization_details', ['realization_id' => $realization->id]);
        $this->assertDatabaseHas('payreqs', [
            'id' => $payreqId,
            'status' => 'paid',
        ]);
    }

    public function test_cancel_nonexistent_realization_redirects_with_error_without_500(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->delete(route('user-payreqs.realizations.cancel', 99999));

        $response->assertRedirect(route('user-payreqs.realizations.index'));
        $response->assertSessionHas('error', self::NOT_FOUND_MESSAGE);
        $response->assertStatus(302);
    }

    public function test_regular_user_cannot_cancel_other_users_realization(): void
    {
        $owner = $this->makeUser();
        $otherUser = $this->makeUser();
        $realization = $this->makeDraftRealization($owner);
        $payreqId = $realization->payreq_id;

        Payreq::query()->whereKey($payreqId)->update(['status' => 'realization']);

        $response = $this->actingAs($otherUser)->delete(route('user-payreqs.realizations.cancel', $realization->id));

        $response->assertRedirect(route('user-payreqs.realizations.index'));
        $response->assertSessionHas('error', self::NOT_FOUND_MESSAGE);
        $this->assertDatabaseHas('realizations', ['id' => $realization->id]);
        $this->assertDatabaseHas('realization_details', ['realization_id' => $realization->id]);
        $this->assertDatabaseHas('payreqs', [
            'id' => $payreqId,
            'status' => 'realization',
            'cancel_count' => 0,
        ]);
    }

    public function test_owner_can_cancel_own_realization_and_increment_cancel_count(): void
    {
        $user = $this->makeUser();
        $realization = $this->makeDraftRealization($user);
        $payreqId = $realization->payreq_id;

        Payreq::query()->whereKey($payreqId)->update(['status' => 'realization']);

        $response = $this->actingAs($user)->delete(route('user-payreqs.realizations.cancel', $realization->id));

        $response->assertRedirect(route('user-payreqs.realizations.index'));
        $response->assertSessionHas('success', 'Realisasi berhasil dihapus.');
        $this->assertDatabaseMissing('realizations', ['id' => $realization->id]);
        $this->assertDatabaseMissing('realization_details', ['realization_id' => $realization->id]);
        $this->assertDatabaseHas('payreqs', [
            'id' => $payreqId,
            'status' => 'paid',
            'cancel_count' => 1,
        ]);
    }

    public function test_superadmin_can_cancel_other_users_realization(): void
    {
        Role::query()->firstOrCreate(['name' => 'superadmin'], ['guard_name' => 'web']);

        $owner = $this->makeUser();
        $superadmin = $this->makeUser();
        $superadmin->assignRole('superadmin');

        $realization = $this->makeDraftRealization($owner);
        $payreqId = $realization->payreq_id;

        Payreq::query()->whereKey($payreqId)->update(['status' => 'realization']);

        $response = $this->actingAs($superadmin)->delete(route('user-payreqs.realizations.cancel', $realization->id));

        $response->assertRedirect(route('user-payreqs.realizations.index'));
        $response->assertSessionHas('success', 'Realisasi berhasil dihapus.');
        $this->assertDatabaseMissing('realizations', ['id' => $realization->id]);
        $this->assertDatabaseHas('payreqs', [
            'id' => $payreqId,
            'status' => 'paid',
            'cancel_count' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeUser(array $overrides = []): User
    {
        $departmentId = \DB::table('departments')->insertGetId([
            'department_name' => 'Test Dept',
            'akronim' => 'TD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::factory()->create(array_merge([
            'project' => '000H',
            'department_id' => $departmentId,
        ], $overrides));
    }

    private function makeDraftRealization(User $user): Realization
    {
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-'.fake()->unique()->numerify('####'),
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'approved_at' => now(),
            'remarks' => 'Test advance',
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'REAL-'.fake()->unique()->numerify('####'),
            'payreq_id' => $payreq->id,
            'user_id' => $user->id,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'status' => 'draft',
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'description' => 'Expense line',
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
        ]);

        return $realization;
    }
}
