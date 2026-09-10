<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realization_details', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('rab_id')->constrained('activities')->nullOnDelete();
            $table->boolean('activity_excluded')->default(false)->after('activity_id');
            $table->index('activity_id');
        });
    }

    public function down(): void
    {
        Schema::table('realization_details', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropIndex(['activity_id']);
            $table->dropColumn(['activity_id', 'activity_excluded']);
        });
    }
};
