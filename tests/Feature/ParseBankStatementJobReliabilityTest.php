<?php

namespace Tests\Feature;

use App\Jobs\ParseBankStatementJob;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Dokumen;
use App\Models\Giro;
use App\Models\User;
use App\Services\BankStatementParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParseBankStatementJobReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function createCashier(): User
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

    public function test_missing_dokumen_marks_failed_without_throwing(): void
    {
        $user = $this->createCashier();
        $reconciliation = BankReconciliation::query()->create([
            'giro_id' => $this->createGiro()->id,
            'dokumen_id' => null,
            'periode' => '2026-02-01',
            'source_mode' => BankReconciliation::SOURCE_AI,
            'status' => BankReconciliation::STATUS_PROCESSING,
            'created_by' => $user->id,
        ]);

        (new ParseBankStatementJob($reconciliation->id))->handle(app(BankStatementParserService::class));

        $reconciliation->refresh();
        $this->assertSame(BankReconciliation::STATUS_FAILED, $reconciliation->status);
        $this->assertStringContainsString('no koran PDF', (string) $reconciliation->notes);
    }

    public function test_parser_exception_preserves_existing_lines_and_marks_failed(): void
    {
        $user = $this->createCashier();
        $giro = $this->createGiro();
        $dokumen = Dokumen::query()->create([
            'filename1' => 'koran_test.pdf',
            'giro_id' => $giro->id,
            'type' => 'koran',
            'project' => '000H',
            'periode' => '2026-02-01',
            'created_by' => $user->id,
        ]);

        $reconciliation = BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'dokumen_id' => $dokumen->id,
            'periode' => '2026-02-01',
            'source_mode' => BankReconciliation::SOURCE_AI,
            'status' => BankReconciliation::STATUS_PROCESSING,
            'created_by' => $user->id,
        ]);

        $existing = BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
            'description' => 'Keep me',
        ]);

        $this->mock(BankStatementParserService::class, function ($mock) {
            $mock->shouldReceive('parseAndPersist')
                ->once()
                ->andThrow(new RuntimeException('AI timeout'));
        });

        try {
            (new ParseBankStatementJob($reconciliation->id))->handle(app(BankStatementParserService::class));
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $exception) {
            $this->assertSame('AI timeout', $exception->getMessage());
        }

        $reconciliation->refresh();
        $this->assertSame(BankReconciliation::STATUS_FAILED, $reconciliation->status);
        $this->assertStringContainsString('PDF parse failed', (string) $reconciliation->notes);
        $this->assertDatabaseHas('bank_statement_lines', [
            'id' => $existing->id,
            'description' => 'Keep me',
        ]);
    }
}
