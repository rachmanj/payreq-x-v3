<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('banks')->where('name', 'Bank BRI')->exists();

        if (! $exists) {
            DB::table('banks')->insert(['name' => 'Bank BRI']);
        }
    }

    public function down(): void
    {
        $bank = DB::table('banks')->where('name', 'Bank BRI')->first();

        if (! $bank) {
            return;
        }

        // Jangan hapus bank yang masih dipakai rekening transfer.
        if (DB::table('transfer_accounts')->where('bank_id', $bank->id)->exists()) {
            return;
        }

        DB::table('banks')->where('id', $bank->id)->delete();
    }
};
