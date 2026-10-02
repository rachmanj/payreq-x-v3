<?php

namespace Tests\Unit;

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
use Tests\TestCase;

class ReconciliationMatchingOrientationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(OpenRouterService::class, function ($mock) {
            $mock->shouldNotReceive('chat');
        });
    }

    protected function createReconciliation(): BankReconciliation
    {
        $bankId = DB::table('banks')->insertGetId([
            'name' => 'Mandiri',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $giro = Giro::query()->create([
            'acc_no' => '1270011024484',
            'acc_name' => 'Test',
            'bank_id' => $bankId,
            'project' => '021C',
        ]);

        $user = User::factory()->create(['project' => '021C']);

        return BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'periode' => '2026-01-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);
    }

    public function test_direct_orientation_pairs_opposite_column_polarity(): void
    {
        $reconciliation = $this->createReconciliation();

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-05-04',
            'debit' => '500.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-04',
            'debit' => '0.00',
            'credit' => '500.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => ReconciliationMatchGroup::TYPE_AUTO_EXACT,
        ]);
    }

    public function test_mirrored_column_case_recon_34_style_finds_pair(): void
    {
        $reconciliation = $this->createReconciliation();

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-01-02',
            'description' => 'Transfer ATM - KE ELIN HERLINA',
            'debit' => '14050000.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-02',
            'description' => 'Pembayaran Makan Staff, Visitor',
            'debit' => '0.00',
            'credit' => '14050000.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);

        $firstLine = $reconciliation->bankStatementLines()->orderBy('line_order')->first();
        $this->assertStringContainsString('[auto-match] orientation=direct', (string) $firstLine->line_notes);
    }

    public function test_same_amount_same_side_polarity_is_not_paired(): void
    {
        $reconciliation = $this->createReconciliation();

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-01-03',
            'debit' => '1000.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-03',
            'debit' => '1000.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(0, $matched);
        $this->assertDatabaseMissing('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
        ]);
    }

    public function test_orientation_with_fewer_preview_pairs_is_not_selected(): void
    {
        $reconciliation = $this->createReconciliation();

        foreach ([1, 2] as $order) {
            BankStatementLine::query()->create([
                'bank_reconciliation_id' => $reconciliation->id,
                'transaction_date' => '2026-01-10',
                'debit' => (string) ($order * 100).'.00',
                'credit' => '0.00',
                'matched_status' => BankStatementLine::MATCH_UNMATCHED,
                'line_order' => $order,
                'is_ai_extracted' => false,
            ]);
        }

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-01-11',
            'debit' => '300.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 3,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-10',
            'debit' => '0.00',
            'credit' => '100.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-10',
            'debit' => '0.00',
            'credit' => '200.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-11',
            'debit' => '300.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(2, $matched);

        $firstLine = $reconciliation->bankStatementLines()->orderBy('line_order')->first();
        $this->assertStringContainsString('orientation=direct', (string) $firstLine->line_notes);
        $this->assertStringContainsString('direct=2', (string) $firstLine->line_notes);
        $this->assertStringContainsString('mirrored=0', (string) $firstLine->line_notes);
    }
}
