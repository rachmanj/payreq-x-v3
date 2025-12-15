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
        // Step 1: Convert string values to integers (safe - all values are numeric)
        DB::statement('UPDATE approval_stages SET department_id = CAST(department_id AS UNSIGNED)');

        // Step 2: Change column type to BIGINT UNSIGNED
        Schema::table('approval_stages', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->change();
        });

        // Step 3: Add foreign key constraint
        Schema::table('approval_stages', function (Blueprint $table) {
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_stages', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        Schema::table('approval_stages', function (Blueprint $table) {
            $table->string('department_id')->change();
        });
    }
};
