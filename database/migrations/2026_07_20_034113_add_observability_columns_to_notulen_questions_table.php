<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notulen_questions', function (Blueprint $table) {
            $table->string('model')->nullable()->after('sources');
            $table->float('top_score')->nullable()->after('model');
            $table->unsignedInteger('latency_ms')->nullable()->after('top_score');
            $table->boolean('not_found')->default(false)->after('latency_ms');
        });
    }

    public function down(): void
    {
        Schema::table('notulen_questions', function (Blueprint $table) {
            $table->dropColumn(['model', 'top_score', 'latency_ms', 'not_found']);
        });
    }
};
