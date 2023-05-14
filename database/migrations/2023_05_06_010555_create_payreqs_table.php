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
        Schema::create('payreqs', function (Blueprint $table) {
            $table->id();
            $table->string('payreq_no')->nullable();
            $table->foreignId('user_id');
            $table->string('type')->nullable();
            $table->double('amount')->nullable();
            $table->text('remarks')->nullable();
            $table->date('due_date')->nullable();
            $table->string('project', 20)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('rab_id')->nullable();
            $table->string('status')->nullable(); // draft / approved / rejected / outgoing / realized / verified
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payreqs');
    }
};
