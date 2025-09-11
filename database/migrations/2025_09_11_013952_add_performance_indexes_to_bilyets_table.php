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
        Schema::table('bilyets', function (Blueprint $table) {
            // Add performance indexes for frequently queried fields
            $table->index(['status', 'project'], 'idx_bilyets_status_project');
            $table->index(['bilyet_date', 'status'], 'idx_bilyets_date_status');
            $table->index(['giro_id', 'status'], 'idx_bilyets_giro_status');
            $table->index(['prefix', 'nomor'], 'idx_bilyets_prefix_nomor');
            $table->index(['created_at', 'project'], 'idx_bilyets_created_project');
            $table->index(['type', 'status'], 'idx_bilyets_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilyets', function (Blueprint $table) {
            // Drop performance indexes
            $table->dropIndex('idx_bilyets_status_project');
            $table->dropIndex('idx_bilyets_date_status');
            $table->dropIndex('idx_bilyets_giro_status');
            $table->dropIndex('idx_bilyets_prefix_nomor');
            $table->dropIndex('idx_bilyets_created_project');
            $table->dropIndex('idx_bilyets_type_status');
        });
    }
};
