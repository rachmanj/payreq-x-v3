<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('remarks');
            $table->foreignId('transfer_account_id')->nullable()->after('payment_method')
                ->constrained('transfer_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfer_account_id');
            $table->dropColumn('payment_method');
        });
    }
};
