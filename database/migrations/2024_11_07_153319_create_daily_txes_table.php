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
        Schema::create('daily_txes', function (Blueprint $table) {
            $table->id();
            $table->date('create_date')->nullable();
            $table->date('posting_date')->nullable();
            $table->integer('duration')->nullable();
            $table->string('doc_num')->nullable();
            $table->string('doc_type')->nullable();
            $table->string('project')->nullable();
            $table->string('account')->nullable();
            $table->decimal('debit', 15, 2)->nullable();
            $table->decimal('credit', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('user_code')->nullable();
            $table->string('table_type')->nullable();
            $table->boolean('will_delete')->default(false);
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_txes');
    }
};

// **
// create_date	posting_date	tx_num	doc_num	doc_type	project_code	department	account	debit	credit	fc_debit	fc_credit	remarks	user_code
