<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('adm_amount', 15, 2)->nullable()->after('interest_amount');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->string('ref_vendor_label', 50)->nullable()->after('kode_unit');
            $table->unsignedInteger('sap_series')->nullable()->after('costing_code');
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn('adm_amount');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['ref_vendor_label', 'sap_series']);
        });
    }
};
