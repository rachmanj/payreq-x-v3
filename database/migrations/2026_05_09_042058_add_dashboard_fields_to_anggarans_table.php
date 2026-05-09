<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggarans', function (Blueprint $table) {
            $table->unsignedTinyInteger('warning_threshold')->default(80)->after('persen');
            $table->string('fund_status', 20)->default('pending')->after('warning_threshold');
            $table->timestamp('fund_pooled_at')->nullable()->after('fund_status');
            $table->foreignId('fund_pooled_by')->nullable()->after('fund_pooled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('anggarans', function (Blueprint $table) {
            $table->dropForeign(['fund_pooled_by']);
            $table->dropColumn([
                'warning_threshold',
                'fund_status',
                'fund_pooled_at',
                'fund_pooled_by',
            ]);
        });
    }
};
