<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giro_id')->constrained('giros')->cascadeOnDelete();
            $table->foreignId('dokumen_id')->nullable()->constrained('dokumens')->nullOnDelete();
            $table->date('periode');
            $table->string('status', 32)->default('draft');
            $table->decimal('opening_balance_bank', 18, 2)->nullable();
            $table->decimal('closing_balance_bank', 18, 2)->nullable();
            $table->decimal('opening_balance_book', 18, 2)->nullable();
            $table->decimal('closing_balance_book', 18, 2)->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['giro_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
