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
            $table->string('nomor')->nullable();
            $table->string('draft_no')->nullable();
            $table->foreignId('user_id');
            $table->string('type')->nullable();
            $table->double('amount')->nullable();
            $table->text('remarks')->nullable();
            $table->date('due_date')->nullable();
            $table->string('project', 20)->nullable();
            $table->foreignId('department_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submit_at')->nullable();
            $table->foreignId('rab_id')->nullable();
            $table->boolean('editable')->default(true);
            $table->boolean('deletable')->default(true);
            $table->boolean('printable')->default(false);
            $table->string('status', 20)->nullable(); // draft / submitted / approved / rejected / paid / realized / verified / canceled
            $table->timestamp('canceled_at')->nullable();
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
