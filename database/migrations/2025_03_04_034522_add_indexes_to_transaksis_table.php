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
        Schema::table('transaksis', function (Blueprint $table) {
            // Add indexes to improve query performance
            $table->index('account_id');
            $table->index(['account_id', 'id']);
            $table->index('posting_date');
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['account_id']);
            $table->dropIndex(['account_id', 'id']);
            $table->dropIndex(['posting_date']);
            $table->dropIndex(['document_type']);
        });
    }
};
