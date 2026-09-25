<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            if (! Schema::hasColumn('verification_journals', 'auto_validated_by_cashier')) {
                $table->boolean('auto_validated_by_cashier')->default(false)->after('validated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            if (Schema::hasColumn('verification_journals', 'auto_validated_by_cashier')) {
                $table->dropColumn('auto_validated_by_cashier');
            }
        });
    }
};
