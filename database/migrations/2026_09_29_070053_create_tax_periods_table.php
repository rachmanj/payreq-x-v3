<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->char('masa_pajak', 7);
            $table->enum('tax_type', ['ppn', 'pph'])->default('ppn');
            $table->string('project')->nullable();
            $table->enum('status', ['open', 'prepared', 'approved', 'filed', 'locked'])->default('open');
            $table->decimal('pk_total', 20, 2)->nullable();
            $table->decimal('pm_total', 20, 2)->nullable();
            $table->decimal('kb_lb', 20, 2)->nullable();
            $table->decimal('diff_sap_app', 20, 2)->nullable();
            $table->decimal('diff_coretax_app', 20, 2)->nullable();
            $table->decimal('diff_pk_pm', 20, 2)->nullable();
            $table->json('snapshot_json')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('filed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['masa_pajak', 'tax_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_periods');
    }
};
