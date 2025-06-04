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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->text('content'); // Announcement content
            $table->date('start_date'); // Start display date
            $table->integer('duration_days'); // Duration in days
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status
            $table->json('target_roles'); // Target roles as JSON array
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Creator
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
