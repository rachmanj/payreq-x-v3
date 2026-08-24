<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_ap_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_utilitas', 20);
            $table->foreignId('sap_business_partner_id')->constrained('sap_business_partners');
            $table->string('num_at_card');
            $table->string('tax_code', 20);
            $table->string('periode_summary')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'posted', 'failed'])->default('pending');
            $table->string('sap_doc_num')->nullable();
            $table->unsignedBigInteger('sap_doc_entry')->nullable();
            $table->text('sap_error_message')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('num_at_card');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_ap_invoices');
    }
};
