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
        Schema::table('approval_plans', function (Blueprint $table) {
            $table->timestamp('requestor_remarks_updated_at')->nullable()->after('requestor_remarks');
            $table->timestamp('approver_read_requestor_reply_at')->nullable()->after('requestor_remarks_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_plans', function (Blueprint $table) {
            $table->dropColumn(['requestor_remarks_updated_at', 'approver_read_requestor_reply_at']);
        });
    }
};
