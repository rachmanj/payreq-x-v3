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
        Schema::table('bilyets', function (Blueprint $table) {
            $table->enum('purpose', ['loan_payment', 'operational', 'other'])->nullable()->default('operational')->after('loan_id');
            
            $table->index('purpose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilyets', function (Blueprint $table) {
            $table->dropIndex(['purpose']);
            $table->dropColumn('purpose');
        });
    }
};
