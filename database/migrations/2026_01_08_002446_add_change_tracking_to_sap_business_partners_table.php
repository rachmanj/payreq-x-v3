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
        Schema::table('sap_business_partners', function (Blueprint $table) {
            $table->string('previous_name')->nullable()->after('name');
            $table->boolean('previous_active')->nullable()->after('active');
            $table->timestamp('name_changed_at')->nullable()->after('previous_name');
            $table->timestamp('status_changed_at')->nullable()->after('previous_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sap_business_partners', function (Blueprint $table) {
            $table->dropColumn([
                'previous_name',
                'previous_active',
                'name_changed_at',
                'status_changed_at',
            ]);
        });
    }
};
