<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bilyets', function (Blueprint $table) {
            $table->string('sap_doc_num', 50)->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('bilyets', function (Blueprint $table) {
            $table->dropColumn('sap_doc_num');
        });
    }
};
