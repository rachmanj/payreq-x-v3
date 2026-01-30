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
        Schema::table('creditors', function (Blueprint $table) {
            $table->unsignedBigInteger('sap_business_partner_id')->nullable()->after('name');
            $table->foreign('sap_business_partner_id')
                  ->references('id')
                  ->on('sap_business_partners')
                  ->onDelete('set null');
            $table->index('sap_business_partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creditors', function (Blueprint $table) {
            $table->dropForeign(['sap_business_partner_id']);
            $table->dropIndex(['sap_business_partner_id']);
            $table->dropColumn('sap_business_partner_id');
        });
    }
};
