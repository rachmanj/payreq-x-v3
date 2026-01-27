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
        Schema::table('pcbcs', function (Blueprint $table) {
            $table->index(['project', 'pcbc_date']);
            $table->index('created_by');
            $table->index('pcbc_date');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcbcs', function (Blueprint $table) {
            $table->dropIndex(['project', 'pcbc_date']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['pcbc_date']);
            $table->dropIndex(['deleted_at']);
        });
    }
};
