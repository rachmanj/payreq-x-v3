<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->text('cancel_reason')->nullable()->after('cancelled_by');

            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bpjs_ap_invoices MODIFY je_status ENUM('pending', 'success', 'failed', 'skipped', 'reversed') NULL");
        } elseif (DB::getDriverName() === 'sqlite') {
            Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
                $table->dropColumn('je_status');
            });

            Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
                $table->string('je_status', 20)->nullable()->after('journal_entry_id');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bpjs_ap_invoices MODIFY je_status ENUM('pending', 'success', 'failed', 'skipped') NULL");
        } elseif (DB::getDriverName() === 'sqlite') {
            Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
                $table->dropColumn('je_status');
            });

            Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
                $table->enum('je_status', ['pending', 'success', 'failed', 'skipped'])->nullable()->after('journal_entry_id');
            });
        }

        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancel_reason']);
        });
    }
};
