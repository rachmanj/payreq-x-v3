<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            // Add critical indexes for performance
            $table->index('status', 'idx_payreqs_status');
            $table->index('created_at', 'idx_payreqs_created_at');
            $table->index('user_id', 'idx_payreqs_user_id');
            $table->index('type', 'idx_payreqs_type');
            $table->index('nomor', 'idx_payreqs_nomor');

            // Composite indexes for common query patterns
            $table->index(['status', 'created_at'], 'idx_payreqs_status_created_at');
            $table->index(['status', 'type'], 'idx_payreqs_status_type');
        });

        Schema::table('realizations', function (Blueprint $table) {
            // Add indexes for join performance
            $table->index('payreq_id', 'idx_realizations_payreq_id');
            $table->index('user_id', 'idx_realizations_user_id');
            $table->index('nomor', 'idx_realizations_nomor');
        });

        Schema::table('users', function (Blueprint $table) {
            // Add index for name field used in search
            $table->index('name', 'idx_users_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            $table->dropIndex('idx_payreqs_status');
            $table->dropIndex('idx_payreqs_created_at');
            $table->dropIndex('idx_payreqs_user_id');
            $table->dropIndex('idx_payreqs_type');
            $table->dropIndex('idx_payreqs_nomor');
            $table->dropIndex('idx_payreqs_status_created_at');
            $table->dropIndex('idx_payreqs_status_type');
        });

        Schema::table('realizations', function (Blueprint $table) {
            $table->dropIndex('idx_realizations_payreq_id');
            $table->dropIndex('idx_realizations_user_id');
            $table->dropIndex('idx_realizations_nomor');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
        });
    }
};
