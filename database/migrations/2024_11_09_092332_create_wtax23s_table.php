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
        Schema::create('wtax23s', function (Blueprint $table) {
            $table->id();
            $table->date('create_date')->nullable();
            $table->date('posting_date')->nullable();
            $table->integer('duration')->nullable();
            $table->string('doc_num')->nullable();
            $table->string('doc_type')->nullable();
            $table->string('project')->nullable();
            $table->string('account')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('user_code')->nullable();
            $table->string('bupot_no')->nullable();
            $table->date('bupot_date')->nullable();
            $table->string('bupot_by')->nullable();
            $table->timestamp('bupot_at')->nullable();
            $table->string('filename')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->integer('batch_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wtax23s');
    }
};
