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
        Schema::table('fakturs', function (Blueprint $table) {
            $table->date('je_posting_date')->nullable()->after('sap_submitted_by');
            $table->date('je_tax_date')->nullable()->after('je_posting_date');
            $table->date('je_due_date')->nullable()->after('je_tax_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropColumn(['je_posting_date', 'je_tax_date', 'je_due_date']);
        });
    }
};
