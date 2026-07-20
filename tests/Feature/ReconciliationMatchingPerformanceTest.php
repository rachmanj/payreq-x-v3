<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Giro;
use App\Models\ReconciliationMatchGroup;
use App\Models\SapGlLine;
use App\Models\User;
use App\Services\OpenRouterService;
use App\Services\ReconciliationMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReconciliationMatchingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function createPreparer(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        return $user;
    }

    protected function createGiro(): Giro
    {
        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Giro::query()->create([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => '000H',
        ]);
    }

    protected function createReconciliation(User $preparer): BankReconciliation
    {
        return BankReconciliation::query()->create([
            'giro_id' => $this->createGiro()->id,
            'periode' => '2026-05-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $preparer->id,
        ]);
    }

    public function test_auto_split_matches_many_sap_to_one_bank(): void
    {
        $preparer = $this->createPreparer();
        $this->actingAs($preparer);
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-05-10',
            'description' => 'Combined transfer',
            'debit' => '0.00',
            'credit' => '300.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-10',
            'description' => 'Part A',
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-11',
            'description' => 'Part B',
            'debit' => '200.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => ReconciliationMatchGroup::TYPE_AUTO_SPLIT,
        ]);
        $this->assertSame(0, $reconciliation->bankStatementLines()->where('matched_status', BankStatementLine::MATCH_UNMATCHED)->count());
        $this->assertSame(0, $reconciliation->sapGlLines()->where('matched_status', SapGlLine::MATCH_UNMATCHED)->count());
    }

    public function test_fuzzy_match_uses_text_similarity_before_ai(): void
    {
        $preparer = $this->createPreparer();
        $this->actingAs($preparer);
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-05-12',
            'description' => 'Payment to Vendor ABC Invoice 99',
            'debit' => '500.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-15',
            'description' => 'Payment to Vendor ABC Invoice 99',
            'debit' => '0.00',
            'credit' => '500.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $this->mock(OpenRouterService::class, function ($mock) {
            $mock->shouldNotReceive('chat');
        });

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => ReconciliationMatchGroup::TYPE_AUTO_FUZZY,
        ]);
    }

    public function test_fuzzy_ai_evaluates_top_ranked_candidates(): void
    {
        $preparer = $this->createPreparer();
        $this->actingAs($preparer);
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-05-12',
            'description' => 'ZZZ unrelated bank narrative',
            'reference' => 'REF-1',
            'debit' => '750.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-16',
            'description' => 'AAA distant sap text',
            'doc_num' => '100',
            'debit' => '0.00',
            'credit' => '750.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-14',
            'description' => 'BBB closer sap candidate',
            'doc_num' => '200',
            'debit' => '0.00',
            'credit' => '750.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $this->mock(OpenRouterService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->atLeast()->once()
                ->andReturn([
                    'choices' => [
                        ['message' => ['content' => '{"match":true,"confidence":0.9}']],
                    ],
                ]);
        });

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => ReconciliationMatchGroup::TYPE_AUTO_FUZZY,
        ]);
    }
}
