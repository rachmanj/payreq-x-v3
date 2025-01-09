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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('delivery_number')->unique();
            $table->date('document_date');
            $table->date('sent_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('origin'); // will be project code dropdown
            $table->string('destination'); // will be project code dropdown
            $table->string('recipient_name');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->text('remarks')->nullable(); // Optional remarks for the delivery
            $table->text('feedback')->nullable(); // Optional feedback from receiver
            $table->string('status'); // pending = belum dikirim, sent = dikirim, delivered = diterima oleh penerima
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
