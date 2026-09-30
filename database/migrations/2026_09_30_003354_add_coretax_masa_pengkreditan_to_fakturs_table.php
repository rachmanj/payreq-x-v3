<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->char('coretax_masa_pengkreditan', 7)->nullable()->after('coretax_status');
        });
    }

    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropColumn('coretax_masa_pengkreditan');
        });
    }
};
