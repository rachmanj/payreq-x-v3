<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('sap_code');
            $table->boolean('is_active')->default(true)->after('description');
            $table->boolean('is_selectable')->default(true)->after('is_active');
            $table->unsignedBigInteger('parent_id')->nullable()->after('is_selectable');
            $table->timestamp('synced_at')->nullable()->after('parent_id');
            $table->softDeletes()->after('updated_at');
        });

        // Populate existing data
        DB::statement('UPDATE departments SET is_active = true WHERE is_active IS NULL');
        DB::statement('UPDATE departments SET is_selectable = true WHERE is_selectable IS NULL');

        // Add foreign key for parent_id
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('departments')
                ->onDelete('set null');
        });

        // Add indexes
        Schema::table('departments', function (Blueprint $table) {
            $table->index('parent_id', 'idx_departments_parent_id');
            $table->index('is_active', 'idx_departments_is_active');
            $table->index('sap_code', 'idx_departments_sap_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('idx_departments_parent_id');
            $table->dropIndex('idx_departments_is_active');
            $table->dropIndex('idx_departments_sap_code');
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['description', 'is_active', 'is_selectable', 'parent_id', 'synced_at', 'deleted_at']);
        });
    }
};
