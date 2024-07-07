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
        Schema::create('cashier_modals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitter')->nullable();
            $table->foreignId('receiver')->nullable();
            $table->string('project', 10);
            $table->date('date');
            $table->string('type'); // begin, end
            $table->decimal('submit_amount', 15, 2)->nullable();
            $table->decimal('receive_amount', 15, 2)->nullable();
            $table->decimal('tx_in', 15, 2)->nullable();
            $table->decimal('tx_out', 15, 2)->nullable();
            $table->string('receiver_remarks')->nullable();
            $table->string('submitter_remarks')->nullable();
            $table->string('status')->default('open'); // open = belum diterima oleh receiver, close = sudah diterima oleh receiver
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_modals');
    }
};
