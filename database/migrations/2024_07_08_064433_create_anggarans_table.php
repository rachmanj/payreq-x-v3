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
        Schema::create('anggarans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->nullable(); // created by system
            $table->string('draft_no', 30)->nullable();
            $table->string('rab_no')->nullable();
            $table->integer('old_rab_id')->nullable();
            $table->date('date')->nullable();
            $table->string('description')->nullable();
            $table->string('project', 20); // project of creator
            $table->string('rab_project', 20)->nullable(); // rab for project, project yg menikmati manfaat dari rab ini
            $table->foreignId('department_id');
            $table->string('type', 20); // periode / buc / event
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('periode_anggaran')->nullable();
            $table->date('periode_ofr')->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->decimal('balance', 20, 2)->default(0);
            $table->string('usage', 20)->default('user'); // budget usage by user / department / project
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submit_at')->nullable();
            $table->string('filename')->nullable();
            $table->boolean('editable')->default(true);
            $table->boolean('deletable')->default(true);
            $table->boolean('printable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add indexes for frequently queried columns
            $table->index('is_active');
            $table->index('status');
            $table->index('project');
            $table->index('date');
            $table->index('created_by');
            
            // Add composite indexes for common query patterns
            $table->index(['is_active', 'status']);
            $table->index(['project', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggarans');
    }
};
