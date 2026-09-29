<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koreksi atas migrasi 2026_09_29_070053: unique index (type, faktur_no) TIDAK boleh dipasang.
 *
 * Alasan (terukur di data produksi 29 Sep 2026): dari 16.959 baris `fakturs`, banyak nomor faktur
 * dipakai berulang secara SAH (satu faktur pajak menutup beberapa dokumen internal) — contoh
 * `00000` 17 baris, `010.010-24.85750948` 13 baris, `04002500253834582` 12 baris. Unique index akan
 * menggagalkan migrasi di produksi. Duplikat tetap perlu diawasi, tapi lewat daftar periksa,
 * bukan larangan tingkat database.
 *
 * PENTING: migrasi ini dipakai juga oleh suite test (SQLite :memory:). Karena itu DILARANG memakai
 * `information_schema` maupun fungsi SQL khusus MySQL. Cek index memakai try/catch dan backfill
 * dijalankan dari sisi PHP (chunk) supaya jalan di MySQL maupun SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Buang unique index bila ada (kondisi dev). Try/catch: aman kalau index tidak ada.
        try {
            Schema::table('fakturs', function (Blueprint $table) {
                $table->dropUnique('fakturs_type_faktur_no_unique');
            });
        } catch (\Throwable $e) {
            // index tidak ada — abaikan
        }

        // Pastikan index biasa untuk pencarian tersedia (bila belum ada).
        try {
            Schema::table('fakturs', function (Blueprint $table) {
                $table->index(['type', 'faktur_no'], 'fakturs_type_faktur_no_index');
            });
        } catch (\Throwable $e) {
            // index sudah ada — abaikan
        }

        $this->backfillMasaPajak();
    }

    public function down(): void
    {
        try {
            Schema::table('fakturs', function (Blueprint $table) {
                $table->dropIndex('fakturs_type_faktur_no_index');
            });
        } catch (\Throwable $e) {
            // index tidak ada — abaikan
        }

        DB::table('fakturs')->whereNotNull('masa_pajak')->update(['masa_pajak' => null]);
    }

    /**
     * Isi `masa_pajak` (YYYY-MM) dari `faktur_date` untuk baris lama.
     * Dihitung di PHP supaya tidak bergantung fungsi tanggal MySQL. Baris tanpa `faktur_date`
     * sengaja dibiarkan kosong agar muncul di daftar periksa, bukan ditebak.
     */
    private function backfillMasaPajak(): void
    {
        $filled = 0;

        DB::table('fakturs')
            ->whereNull('masa_pajak')
            ->whereNotNull('faktur_date')
            ->select(['id', 'faktur_date'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$filled) {
                foreach ($rows as $row) {
                    $raw = (string) $row->faktur_date;
                    $masa = substr($raw, 0, 7);

                    if (preg_match('/^\d{4}-\d{2}$/', $masa) !== 1) {
                        continue;
                    }

                    DB::table('fakturs')
                        ->where('id', $row->id)
                        ->update(['masa_pajak' => $masa]);

                    $filled++;
                }
            });

        $leftover = DB::table('fakturs')->whereNull('masa_pajak')->count();

        if ($filled > 0 || $leftover > 0) {
            logger()->info('tax monitoring: backfill masa_pajak selesai', [
                'terisi' => $filled,
                'masih_kosong' => $leftover,
            ]);
        }
    }
};
