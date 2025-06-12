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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_from', 3);
            $table->string('currency_to', 3);
            $table->decimal('exchange_rate', 15, 6);
            $table->date('effective_date');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['currency_from', 'currency_to', 'effective_date'], 'unique_currency_date');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('currency_from')->references('currency_code')->on('currencies');
            $table->foreign('currency_to')->references('currency_code')->on('currencies');

            $table->index(['currency_from', 'currency_to'], 'idx_currency_pair');
            $table->index('effective_date', 'idx_effective_date');
            $table->index('created_by', 'idx_created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
