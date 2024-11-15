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
        Schema::table('wtax23s', function (Blueprint $table) {
            $table->string('vendor_code')->nullable()->after('amount');
            $table->string('source_doc')->nullable()->after('doc_type'); // from outgoing or incoming document
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wtax23s', function (Blueprint $table) {
            //
        });
    }
};
