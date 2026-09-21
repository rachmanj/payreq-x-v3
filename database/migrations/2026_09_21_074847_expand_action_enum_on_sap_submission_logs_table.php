<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLE = 'sap_submission_logs';

    private const COLUMN = 'action';

    private const EXPANDED_ENUM = "ENUM('submission','reversal','cancellation','sync') NOT NULL DEFAULT 'submission'";

    private const LEGACY_ENUM = "ENUM('submission','reversal') NOT NULL DEFAULT 'submission'";

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if ($this->columnTypeIncludes('cancellation')) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s',
            self::TABLE,
            self::COLUMN,
            self::EXPANDED_ENUM
        ));
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table(self::TABLE)
            ->where(self::COLUMN, 'cancellation')
            ->update([self::COLUMN => 'reversal']);

        DB::table(self::TABLE)
            ->where(self::COLUMN, 'sync')
            ->update([self::COLUMN => 'submission']);

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s',
            self::TABLE,
            self::COLUMN,
            self::LEGACY_ENUM
        ));
    }

    private function columnTypeIncludes(string $enumValue): bool
    {
        $columnType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('COLUMN_NAME', self::COLUMN)
            ->value('COLUMN_TYPE');

        if ($columnType === null) {
            return false;
        }

        return str_contains(strtolower((string) $columnType), "'{$enumValue}'");
    }
};
