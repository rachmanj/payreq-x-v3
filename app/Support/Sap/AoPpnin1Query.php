<?php

namespace App\Support\Sap;

/**
 * Query PPN Masukan (baris debit akun 11603001) untuk didaftarkan ke SAP lewat SQLQueries.
 *
 * PELAJARAN 29 Sep 2026 — ditemukan lewat uji langsung ke SAP produksi (dua kali ditolak):
 * - Konstruksi yang DITOLAK parser `SQLQueries`: **ekspresi CASE**, **subquery di JOIN**,
 *   **COALESCE**, dan join ke OUSR + ORDER BY pada query multi-join ini.
 * - Bentuk yang DITERIMA: mengikuti gaya query produksi `AO_OPGL1` — `alias.kolom` polos,
 *   join sederhana, dan pemetaan nilai dilakukan di aplikasi (bukan di SQL).
 * - Karena itu `TransType` diambil MENTAH (`trans_type`) dan diterjemahkan ke nama jenis
 *   dokumen di `PpnInputVatSyncService` (self::TRANS_TYPES).
 * - Penggandaan baris dari LEFT JOIN PCH1 tidak berbahaya: upsert berkunci (trans_id, line_id).
 *
 * JANGAN menambah CASE/COALESCE/subquery/OUSR/ORDER BY ke query ini — sudah terbukti bikin
 * SAP menolak dengan "Invalid SQL syntax". Ada test yang menegakkan aturan ini.
 */
class AoPpnin1Query
{
    public const CODE = 'AO_PPNIN1';

    public const NAME = 'AccountingOne PPN Masukan (11603001 debit lines)';

    public static function sqlText(): string
    {
        return 'SELECT T0.TransId AS trans_id, T1.Line_ID AS line_id, T0.BaseRef AS document_no,'
            .' T0.RefDate AS posting_date, T1.TransType AS trans_type, T1.Debit AS amount,'
            .' T2.CardCode AS vendor_code, T2.CardName AS vendor_name, T2.U_MIS_FPNum AS faktur_no,'
            .' T2.U_MIS_FPDate AS faktur_date, T2.NumAtCard AS invoice_no, T3.Project AS project_code'
            .' FROM OJDT T0'
            .' INNER JOIN JDT1 T1 ON T0.TransId = T1.TransId'
            .' LEFT JOIN OPCH T2 ON T0.BaseRef = T2.DocNum'
            .' LEFT JOIN PCH1 T3 ON T2.DocEntry = T3.DocEntry'
            .' WHERE T1.Account = \'11603001\''
            .' AND T1.Debit <> 0'
            .' AND T0.RefDate >= :startDate'
            .' AND T0.RefDate <= :endDate';
    }
}
