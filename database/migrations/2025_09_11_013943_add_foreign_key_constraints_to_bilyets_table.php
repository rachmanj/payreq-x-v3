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
            // Add foreign key constraints
            $table->foreign('giro_id')->references('id')->on('giros')->onDelete('cascade');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilyets', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['giro_id']);
            $table->dropForeign(['loan_id']);
            $table->dropForeign(['created_by']);
        });
    }
};
