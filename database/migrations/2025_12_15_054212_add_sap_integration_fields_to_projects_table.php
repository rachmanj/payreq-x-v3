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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('name')->nullable()->after('code');
            $table->text('description')->nullable()->after('name');
            $table->string('sap_code', 20)->nullable()->after('description');
            $table->boolean('is_selectable')->default(true)->after('is_active');
            $table->timestamp('synced_at')->nullable()->after('is_selectable');
            $table->softDeletes()->after('updated_at');
        });

        // Populate existing data
        DB::statement('UPDATE projects SET name = code WHERE name IS NULL');
        DB::statement('UPDATE projects SET sap_code = code WHERE sap_code IS NULL');
        DB::statement('UPDATE projects SET is_selectable = true WHERE is_selectable IS NULL');

        // Make name NOT NULL after populating
        Schema::table('projects', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        // Add indexes
        Schema::table('projects', function (Blueprint $table) {
            $table->index('sap_code', 'idx_projects_sap_code');
            $table->index('code', 'idx_projects_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_sap_code');
            $table->dropIndex('idx_projects_code');
            $table->dropColumn(['name', 'description', 'sap_code', 'is_selectable', 'synced_at', 'deleted_at']);
        });
    }
};
