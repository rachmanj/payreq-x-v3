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
        Schema::create('sap_business_partners', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name')->nullable();
            $table->string('type', 10)->nullable(); // C=Customer, S=Supplier, L=Lead
            $table->boolean('active')->default(true);
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->boolean('vat_liable')->default(false);
            $table->string('federal_tax_id', 50)->nullable(); // NPWP
            $table->decimal('credit_limit', 18, 2)->nullable();
            $table->decimal('balance', 18, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('type');
            $table->index('active');
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_business_partners');
    }
};
