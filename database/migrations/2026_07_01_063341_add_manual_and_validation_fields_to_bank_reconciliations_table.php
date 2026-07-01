<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->string('source_mode', 16)->default('ai')->after('status');
            $table->string('validation_status', 32)->nullable()->after('source_mode');
            $table->foreignId('submitted_by')->nullable()->after('validation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('validated_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->text('rejection_reason')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn([
                'source_mode',
                'validation_status',
                'submitted_at',
                'validated_at',
                'rejection_reason',
            ]);
        });
    }
};
