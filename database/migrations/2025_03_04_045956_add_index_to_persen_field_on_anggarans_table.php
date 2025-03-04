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
        Schema::table('anggarans', function (Blueprint $table) {
            // Add index to persen field for faster sorting and filtering
            $table->index('persen');
            
            // Add composite index for common query patterns involving persen
            $table->index(['is_active', 'persen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anggarans', function (Blueprint $table) {
            // Drop indexes if migration is rolled back
            $table->dropIndex(['persen']);
            $table->dropIndex(['is_active', 'persen']);
        });
    }
};
