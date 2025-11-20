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
        Schema::create('sap_submission_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_journal_id');
            $table->foreignId('user_id');
            $table->enum('status', ['success', 'failed']);
            $table->text('error_message')->nullable();
            $table->text('sap_response')->nullable();
            $table->string('sap_journal_number')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->timestamps();

            $table->foreign('verification_journal_id')->references('id')->on('verification_journals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('verification_journal_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_submission_logs');
    }
};
