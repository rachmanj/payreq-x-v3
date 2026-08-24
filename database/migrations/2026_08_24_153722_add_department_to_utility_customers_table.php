<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_customers', function (Blueprint $table) {
            $table->string('department', 20)->nullable()->after('project');
        });
    }

    public function down(): void
    {
        Schema::table('utility_customers', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
