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
        Schema::table('realization_details', function (Blueprint $table) {
            $table->index(['unit_no', 'expense_date'], 'realization_details_unit_no_expense_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realization_details', function (Blueprint $table) {
            $table->dropIndex('realization_details_unit_no_expense_date_index');
        });
    }
};
