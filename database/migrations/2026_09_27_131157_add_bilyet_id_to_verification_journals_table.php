<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            if (! Schema::hasColumn('verification_journals', 'bilyet_id')) {
                $table->foreignId('bilyet_id')
                    ->nullable()
                    ->after('bank_account')
                    ->constrained('bilyets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            if (Schema::hasColumn('verification_journals', 'bilyet_id')) {
                $table->dropForeign(['bilyet_id']);
                $table->dropColumn('bilyet_id');
            }
        });
    }
};
