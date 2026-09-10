<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Department;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationJournalPrintActivitySummaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['project' => '000H']);
    }

    protected function createAccount(string $number, string $type = 'expense'): Account
    {
        return Account::query()->create([
            'account_number' => $number,
            'account_name' => 'Account '.$number,
            'type' => $type,
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    public function test_print_includes_activity_summary_when_vj_has_tagged_lines(): void
    {
        $department = Department::query()->create([
            'department_name' => 'Finance',
            'akronim' => 'FIN',
            'sap_code' => 'FIN-01',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $activityAccount = $this->createAccount('51112001');
        $activity = Activity::query()->create([
            'code' => 'KEG-2026-201',
            'name' => 'Mobilisasi Excavator',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'account_id' => $activityAccount->id,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $vj = VerificationJournal::query()->create([
            'nomor' => 'VJ-PRINT-001',
            'project' => '000H',
            'date' => '2026-09-05',
            'amount' => 300000,
            'created_by' => $this->user->id,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $vj->id,
            'realization_date' => '2026-09-05',
            'account_code' => $activityAccount->account_number,
            'debit_credit' => 'debit',
            'description' => $activity->name,
            'project' => '000H',
            'cost_center' => $department->sap_code,
            'amount' => 300000,
            'activity_id' => $activity->id,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $vj->id,
            'realization_date' => '2026-09-05',
            'account_code' => '11010001',
            'debit_credit' => 'credit',
            'description' => 'Credit kas',
            'project' => '000H',
            'cost_center' => $department->sap_code,
            'amount' => 300000,
            'activity_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('verifications.journal.print', $vj->id));

        $response->assertOk();
        $response->assertSee('Ringkasan Kegiatan', false);
        $response->assertSee('Mobilisasi Excavator', false);
        $response->assertSee('51112001', false);
        $response->assertSee('300,000.00', false);
        $response->assertSee('>TOTAL</th>', false);
    }

    public function test_print_without_activity_tags_remains_unchanged(): void
    {
        $department = Department::query()->create([
            'department_name' => 'Finance',
            'akronim' => 'FIN',
            'sap_code' => 'FIN-01',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $expense = $this->createAccount('61201001');

        $vj = VerificationJournal::query()->create([
            'nomor' => 'VJ-PRINT-002',
            'project' => '000H',
            'date' => '2026-09-05',
            'amount' => 100000,
            'created_by' => $this->user->id,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $vj->id,
            'realization_date' => '2026-09-05',
            'account_code' => $expense->account_number,
            'debit_credit' => 'debit',
            'description' => 'Meals',
            'project' => '000H',
            'cost_center' => $department->sap_code,
            'amount' => 100000,
            'activity_id' => null,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $vj->id,
            'realization_date' => '2026-09-05',
            'account_code' => '11010001',
            'debit_credit' => 'credit',
            'description' => 'Credit kas',
            'project' => '000H',
            'cost_center' => $department->sap_code,
            'amount' => 100000,
            'activity_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('verifications.journal.print', $vj->id));

        $response->assertOk();
        $response->assertDontSee('Ringkasan Kegiatan', false);
        $response->assertSee('Verification Journal', false);
        $response->assertSee('VJ-PRINT-002', false);
        $response->assertSee('>TOTAL</th>', false);
        $response->assertSee('100,000.00', false);
    }
}
