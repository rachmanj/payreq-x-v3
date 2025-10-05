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
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->string('kmk_number', 100)->nullable()->after('effective_date');
            $table->date('kmk_effective_from')->nullable()->after('kmk_number');
            $table->date('kmk_effective_to')->nullable()->after('kmk_effective_from');
            $table->string('source', 20)->default('manual')->after('updated_by');
            $table->decimal('change_from_previous', 15, 6)->nullable()->after('exchange_rate');
            $table->timestamp('scraped_at')->nullable()->after('updated_at');

            $table->index(['source'], 'idx_exchange_rates_source');
            $table->index(['kmk_number'], 'idx_exchange_rates_kmk_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropIndex('idx_exchange_rates_source');
            $table->dropIndex('idx_exchange_rates_kmk_number');

            $table->dropColumn([
                'kmk_number',
                'kmk_effective_from',
                'kmk_effective_to',
                'source',
                'change_from_previous',
                'scraped_at',
            ]);
        });
    }
};
