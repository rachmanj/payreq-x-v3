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
        Schema::create('realizations', function (Blueprint $table) {
            $table->id();
            $table->string('nomor');
            $table->string('draft_no')->nullable();
            $table->foreignId('payreq_id');
            $table->foreignId('user_id');
            $table->string('project', 10)->nullable();
            $table->foreignId('department_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('submit_at')->nullable();
            $table->string('status', 20)->nullable(); // draft / approved / reject / cancel / pending (wait approve) / verified
            $table->boolean('editable')->default(true);
            $table->boolean('deletable')->default(true);
            $table->boolean('printable')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('verification_journal_id')->nullable();
            $table->string('flag', 50)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realizations');
    }
};
