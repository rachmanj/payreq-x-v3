<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->string('currency', 3)->default('IDR')->after('amount');
            $table->decimal('fc_amount', 18, 2)->nullable()->after('currency');
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('fc_amount');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->boolean('has_foreign_currency')->default(false)->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropColumn(['currency', 'fc_amount', 'exchange_rate']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('has_foreign_currency');
        });
    }
};
