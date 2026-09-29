<?php

namespace App\Support\Sap;

/**
 * Query PPN Masukan (baris debit akun 11603001) untuk didaftarkan ke SAP lewat SQLQueries.
 *
 * PELAJARAN 29 Sep 2026 (semua ditemukan lewat uji langsung ke SAP produksi):
 * - Yang DITOLAK parser `SQLQueries`: ekspresi **CASE**, **subquery di JOIN**, **COALESCE**,
 *   dan join ke **OUSR**.
 * - Yang DITERIMA: `alias.kolom` polos (gaya query produksi `AO_OPGL1`), `SELECT DISTINCT`,
 *   dan `ORDER BY`.
 * - **ORDER BY itu WAJIB ADA di sini.** SAP memotong hasil List per ~300 baris dan aplikasi
 *   memaginasi dengan `$skip`; tanpa urutan tetap, halaman bisa bergeser sehingga ada baris
 *   terlewat dan tersampel ulang — terbukti nyata (tiga sinkronisasi berturut-turut menyisakan
 *   543, lalu 120 baris yang tidak pernah lengkap).
 * - Join `PCH1` (project per baris invoice) SENGAJA TIDAK dipakai: ia menggandakan baris
 *   (satu invoice banyak baris) tanpa menambah informasi pajak. Project bisa dilengkapi
 *   belakangan lewat OData `PurchaseInvoices`, sama seperti pola `unit_no` di aplikasi.
 * - Penerjemahan `TransType` → nama jenis dokumen dilakukan di `PpnInputVatSyncService`,
 *   bukan di SQL (expressi CASE ditolak).
 */
class AoPpnin1Query
{
    public const CODE = 'AO_PPNIN1';

    public const NAME = 'AccountingOne PPN Masukan (11603001 debit lines)';

    public static function sqlText(): string
    {
        return 'SELECT DISTINCT T0.TransId AS trans_id, T1.Line_ID AS line_id, T0.BaseRef AS document_no,'
            .' T0.RefDate AS posting_date, T1.TransType AS trans_type, T1.Debit AS amount,'
            .' T2.CardCode AS vendor_code, T2.CardName AS vendor_name, T2.U_MIS_FPNum AS faktur_no,'
            .' T2.U_MIS_FPDate AS faktur_date, T2.NumAtCard AS invoice_no'
            .' FROM OJDT T0'
            .' INNER JOIN JDT1 T1 ON T0.TransId = T1.TransId'
            .' LEFT JOIN OPCH T2 ON T0.BaseRef = T2.DocNum'
            .' WHERE T1.Account = \'11603001\''
            .' AND T1.Debit <> 0'
            .' AND T0.RefDate >= :startDate'
            .' AND T0.RefDate <= :endDate'
            .' ORDER BY T0.TransId, T1.Line_ID';
    }
}
