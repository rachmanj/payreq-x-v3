<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_outgoing_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('general_outgoing_payment_id');
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('sap_account', 50);
            $table->string('account_name');
            $table->unsignedBigInteger('amount');
            $table->string('description')->nullable();
            $table->string('profit_center', 50)->nullable();
            $table->timestamps();

            $table->foreign('general_outgoing_payment_id', 'gop_accounts_gop_id_foreign')
                ->references('id')
                ->on('general_outgoing_payments')
                ->cascadeOnDelete();
            $table->index('general_outgoing_payment_id', 'gop_accounts_gop_id_idx');
            $table->index('account_id', 'gop_accounts_account_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_outgoing_payment_accounts');
    }
};
