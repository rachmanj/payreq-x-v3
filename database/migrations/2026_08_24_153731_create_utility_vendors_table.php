<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_utilitas', 20)->unique();
            $table->foreignId('sap_business_partner_id')->nullable()->constrained('sap_business_partners')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        foreach (['pln', 'pdam', 'telkom'] as $jenis) {
            DB::table('utility_vendors')->insert([
                'jenis_utilitas' => $jenis,
                'sap_business_partner_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_vendors');
    }
};
