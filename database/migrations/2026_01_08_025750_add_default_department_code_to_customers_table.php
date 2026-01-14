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
        Schema::table('customers', function (Blueprint $table) {
            // Default department code for Journal Entry (Option B)
            if (!Schema::hasColumn('customers', 'default_department_code')) {
                $table->string('default_department_code', 10)->nullable()->after('project');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'default_department_code')) {
                $table->dropColumn('default_department_code');
            }
        });
    }
};
