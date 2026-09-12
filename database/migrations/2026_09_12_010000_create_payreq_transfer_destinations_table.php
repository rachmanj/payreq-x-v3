<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payreq_transfer_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payreq_id')->constrained('payreqs')->cascadeOnDelete();
            $table->foreignId('transfer_account_id')->constrained('transfer_accounts');
            $table->bigInteger('planned_amount')->nullable();
            $table->string('remark', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('payreq_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payreq_transfer_destinations');
    }
};
