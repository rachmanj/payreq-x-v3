<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realizations', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('department_id')->constrained('activities')->nullOnDelete();
            $table->index('activity_id');
        });
    }

    public function down(): void
    {
        Schema::table('realizations', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropIndex(['activity_id']);
            $table->dropColumn('activity_id');
        });
    }
};
