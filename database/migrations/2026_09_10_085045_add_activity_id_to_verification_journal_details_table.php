<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_journal_details', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id')->nullable()->after('cost_center');
            $table->index('activity_id');
        });
    }

    public function down(): void
    {
        Schema::table('verification_journal_details', function (Blueprint $table) {
            $table->dropIndex(['activity_id']);
            $table->dropColumn('activity_id');
        });
    }
};
