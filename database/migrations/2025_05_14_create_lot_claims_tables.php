<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel utama lot_claims
        Schema::create('lot_claims', function (Blueprint $table) {
            $table->id();
            $table->string('lot_no', 50);
            $table->date('claim_date');
            $table->string('project');
            $table->decimal('advance_amount', 15, 2);
            $table->text('claim_remarks')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('accommodation_total', 15, 2);
            $table->decimal('travel_total', 15, 2);
            $table->decimal('meal_total', 15, 2);
            $table->decimal('total_claim', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->enum('is_claimed', ['yes', 'no'])->default('no');
            $table->timestamps();
        });

        // Tabel untuk akomodasi
        Schema::create('lot_claim_accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_claim_id')->constrained('lot_claims')->onDelete('cascade');
            $table->string('description');
            $table->decimal('accommodation_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tabel untuk perjalanan
        Schema::create('lot_claim_travels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_claim_id')->constrained('lot_claims')->onDelete('cascade');
            $table->string('description');
            $table->decimal('travel_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tabel untuk makan
        Schema::create('lot_claim_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_claim_id')->constrained('lot_claims')->onDelete('cascade');
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'other']);
            $table->integer('people_count');
            $table->decimal('per_person_limit', 15, 2);
            $table->integer('frequency');
            $table->decimal('meal_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Then drop tables
        Schema::dropIfExists('lot_claim_meals');
        Schema::dropIfExists('lot_claim_travels');
        Schema::dropIfExists('lot_claim_accommodations');
        Schema::dropIfExists('lot_claims');
    }
};
