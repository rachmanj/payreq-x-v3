<?php

namespace Tests\Feature;

use App\Models\SapSubmissionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SapSubmissionLogActionEnumTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return object{up: callable, down: callable}
     */
    protected function expandActionEnumMigration(): object
    {
        return include database_path('migrations/2026_09_21_074847_expand_action_enum_on_sap_submission_logs_table.php');
    }

    public function test_all_action_values_persist_and_reload_intact(): void
    {
        $user = User::factory()->create();

        $actions = [
            SapSubmissionLog::ACTION_SUBMISSION,
            SapSubmissionLog::ACTION_REVERSAL,
            SapSubmissionLog::ACTION_CANCELLATION,
            SapSubmissionLog::ACTION_SYNC,
        ];

        foreach ($actions as $action) {
            $log = SapSubmissionLog::query()->create([
                'status' => 'success',
                'action' => $action,
                'document_type' => 'journal_entry',
                'submitted_by' => $user->id,
                'user_id' => $user->id,
            ]);

            $log->refresh();

            $this->assertSame($action, $log->action);
            $this->assertNotSame('', $log->action);
        }
    }

    public function test_expand_action_enum_migration_is_idempotent(): void
    {
        $migration = $this->expandActionEnumMigration();

        $migration->up();
        $migration->up();

        if (DB::getDriverName() === 'mysql') {
            $columnType = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'sap_submission_logs')
                ->where('COLUMN_NAME', 'action')
                ->value('COLUMN_TYPE');

            $this->assertNotNull($columnType);
            $lower = strtolower((string) $columnType);

            foreach (['submission', 'reversal', 'cancellation', 'sync'] as $value) {
                $this->assertStringContainsString("'{$value}'", $lower);
            }
        } else {
            $this->assertTrue(true);
        }
    }
}
