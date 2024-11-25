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
        Schema::table('fakturs', function (Blueprint $table) {
            $table->string('user_code', '20')->nullable()->after('status'); // user code who created the document
            $table->string('doc_num')->nullable()->after('status'); // SAP document number
            $table->string('account', 50)->nullable()->after('status'); // SAP account
            $table->integer('batch_no')->nullable()->after('status'); // faktur batch number
            $table->date('posting_date')->nullable()->after('status'); // faktur posting date
            $table->date('create_date')->nullable()->after('status'); // faktur creation date
            $table->string('type', 20)->nullable()->after('status'); // faktur sales / faktur purchase
            $table->foreignId('uploaded_by')->nullable(); // user id who uploaded the document
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropColumn('user_code');
            $table->dropColumn('doc_num');
            $table->dropColumn('account');
            $table->dropColumn('batch_no');
            $table->dropColumn('posting_date');
            $table->dropColumn('create_date');
            $table->dropColumn('type');
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn('uploaded_by');
        });
    }
};
