<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumens', function (Blueprint $table) {
            $table->string('validation_status', 20)->default('validated')->after('dokumen_date');
            $table->timestamp('validated_at')->nullable()->after('validation_status');
            $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('validated_by');
        });
    }

    public function down(): void
    {
        Schema::table('dokumens', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'validation_status',
                'validated_at',
                'validated_by',
                'rejection_reason',
            ]);
        });
    }
};
