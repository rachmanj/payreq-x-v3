<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payreq_anggaran_allocations', function (Blueprint $table) {
            $table->foreignId('transfer_account_id')
                ->nullable()
                ->after('anggaran_id')
                ->constrained('transfer_accounts')
                ->nullOnDelete();
            $table->bigInteger('planned_amount')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payreq_anggaran_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfer_account_id');
            $table->dropColumn('planned_amount');
        });
    }
};
