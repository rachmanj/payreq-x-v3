<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coretax_input_vat', function (Blueprint $table) {
            $table->id();
            $table->char('masa_pajak', 7);
            $table->string('import_batch', 40);
            $table->string('npwp', 25);
            $table->string('supplier_name', 150);
            $table->string('faktur_no', 30);
            $table->date('faktur_date')->nullable();
            $table->decimal('dpp', 20, 2);
            $table->decimal('ppn', 20, 2);
            $table->string('status_faktur', 30)->nullable();
            $table->enum('match_status', ['unmatched', 'matched', 'manual', 'ignored'])->default('unmatched');
            $table->foreignId('matched_faktur_id')->nullable()->constrained('fakturs')->nullOnDelete();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['masa_pajak', 'faktur_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coretax_input_vat');
    }
};
