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
        Schema::create('realization_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realization_id');
            $table->string('project', 10)->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('description')->nullable();
            $table->string('unit_no', 20)->nullable();
            $table->foreignId('account_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('type', 10)->nullable();
            $table->integer('qty')->nullable();
            $table->string('uom', 10)->nullable();
            $table->integer('km_position')->nullable();
            $table->string('flag', 10)->nullable();
            $table->boolean('editable')->default(true);
            $table->boolean('deleteable')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realization_details');
    }
};
