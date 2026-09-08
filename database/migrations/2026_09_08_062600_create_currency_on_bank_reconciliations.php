<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bank_reconciliations ADD COLUMN currency VARCHAR(5) NOT NULL DEFAULT 'idr'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bank_reconciliations DROP COLUMN currency');
    }
};
