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
        Schema::table('installments', function (Blueprint $table) {
            $table->foreignId('bilyet_id')->nullable()->after('account_id')->constrained('bilyets')->onDelete('set null');
            $table->enum('payment_method', ['bilyet', 'auto_debit', 'cash', 'transfer', 'other'])->nullable()->after('bilyet_id');
            
            $table->index('bilyet_id');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropForeign(['bilyet_id']);
            $table->dropIndex(['bilyet_id']);
            $table->dropIndex(['payment_method']);
            $table->dropColumn(['bilyet_id', 'payment_method']);
        });
    }
};
