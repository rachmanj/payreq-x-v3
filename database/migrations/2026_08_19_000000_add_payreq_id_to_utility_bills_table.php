<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('payreq_id')->nullable()->after('utility_customer_id')->index();
            $table->foreign('payreq_id')->references('id')->on('payreqs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropForeign(['payreq_id']);
            $table->dropColumn('payreq_id');
        });
    }
};
