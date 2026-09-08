<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('principal_amount', 16, 2)->nullable()->after('bilyet_amount');
            $table->decimal('interest_amount', 16, 2)->nullable()->after('principal_amount');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->unique(['loan_id', 'angsuran_ke'], 'installments_loan_id_angsuran_ke_unique');
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropUnique('installments_loan_id_angsuran_ke_unique');
            $table->dropColumn(['principal_amount', 'interest_amount']);
        });
    }
};
