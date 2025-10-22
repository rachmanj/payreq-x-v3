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
        Schema::table('realizations', function (Blueprint $table) {
            $table->boolean('modified_by_approver')->default(false)->after('flag');
            $table->timestamp('modified_by_approver_at')->nullable()->after('modified_by_approver');
            $table->foreignId('modified_by_approver_id')->nullable()->after('modified_by_approver_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realizations', function (Blueprint $table) {
            $table->dropColumn(['modified_by_approver', 'modified_by_approver_at', 'modified_by_approver_id']);
        });
    }
};
