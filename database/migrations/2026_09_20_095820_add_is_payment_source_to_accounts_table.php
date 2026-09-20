<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('accounts', 'is_payment_source')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->boolean('is_payment_source')->default(false);
            });
        }

        DB::table('accounts')
            ->whereIn('account_number', ['13101020', '13101021'])
            ->update(['is_payment_source' => true]);

        DB::table('accounts')
            ->whereIn('account_number', ['13101020', '13101021'])
            ->whereIn('type', ['cash', 'expense'])
            ->update(['type' => 'asset']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('accounts', 'is_payment_source')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('is_payment_source');
            });
        }
    }
};
