<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realizations', function (Blueprint $table) {
            $table->index(
                ['verification_journal_id', 'status', 'project'],
                'idx_realizations_verification_list'
            );
        });

        Schema::table('realization_details', function (Blueprint $table) {
            $table->index(
                ['realization_id', 'account_id'],
                'idx_realization_details_account_complete'
            );
        });
    }

    public function down(): void
    {
        Schema::table('realizations', function (Blueprint $table) {
            $table->dropIndex('idx_realizations_verification_list');
        });

        Schema::table('realization_details', function (Blueprint $table) {
            $table->dropIndex('idx_realization_details_account_complete');
        });
    }
};
