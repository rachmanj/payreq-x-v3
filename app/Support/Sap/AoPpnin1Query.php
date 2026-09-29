<?php

namespace App\Support\Sap;

/**
 * Query PPN Masukan (baris debit akun 11603001) untuk didaftarkan ke SAP lewat SQLQueries.
 *
 * PELAJARAN 29 Sep 2026 (hasil uji langsung ke SAP produksi):
 * - Gaya yang DITERIMA = persis gaya query yang sudah jalan di produksi (`AO_OPGL1`):
 *   nama tabel & kolom POLOS dengan alias (`T1.Account`, `FROM OJDT T0`), parameter `:namaParam`.
 *   SAP menormalkan sendiri saat menyimpan (menambahkan kurung siku pada salinannya).
 * - Konstruksi yang DITOLAK parser: **subquery di dalam JOIN** dan **COALESCE**.
 *   Karena itu project diambil lewat LEFT JOIN PCH1 biasa — penggandaan barisnya tidak
 *   berbahaya karena upsert di aplikasi berkunci (trans_id, line_id).
 * - Filter periode memakai tanggal posting jurnal (`T0.RefDate`), sederhana dan terbukti.
 *   Tanggal faktur pajak tetap DIAMBIL (U_MIS_FPDate) untuk penentuan masa pajak di aplikasi.
 */
class AoPpnin1Query
{
    public const CODE = 'AO_PPNIN1';

    public const NAME = 'AccountingOne PPN Masukan (11603001 debit lines)';

    public static function sqlText(): string
    {
        return 'SELECT T0.TransId AS trans_id, T1.Line_ID AS line_id, T0.BaseRef AS document_no,'
            .' T0.CreateDate AS creation_date, T0.RefDate AS posting_date, T2.U_MIS_FPDate AS faktur_date,'
            .' T2.CardCode AS vendor_code, T2.CardName AS vendor_name, T2.U_MIS_FPNum AS faktur_no,'
            .' T1.Debit AS amount, T3.Project AS project_code,'
            .' CASE T1.TransType'
            ." WHEN '-2' THEN 'Opening Balance'"
            ." WHEN '13' THEN 'AR Invoice'"
            ." WHEN '14' THEN 'AR Credit Memo'"
            ." WHEN '203' THEN 'AR DP'"
            ." WHEN '15' THEN 'Material Issue'"
            ." WHEN '16' THEN 'Material Return'"
            ." WHEN '18' THEN 'AP Invoice'"
            ." WHEN '19' THEN 'AP Credit Memo'"
            ." WHEN '204' THEN 'AP DP'"
            ." WHEN '20' THEN 'Goods Receipt PO'"
            ." WHEN '202' THEN 'Production Order'"
            ." WHEN '21' THEN 'Goods Return'"
            ." WHEN '24' THEN 'Incoming Payments'"
            ." WHEN '30' THEN 'Journal Entry'"
            ." WHEN '46' THEN 'Outgoing Payments'"
            ." WHEN '59' THEN 'Goods Receipt'"
            ." WHEN '60' THEN 'Goods Issue'"
            ." WHEN '67' THEN 'InventoryTransfer'"
            ." WHEN '69' THEN 'Landed Costs'"
            ." WHEN '321' THEN 'Intenal Reconciliation'"
            ." WHEN '162' THEN 'Inventory Revaluation'"
            .' END AS doc_type,'
            .' T0.Memo AS remark, T4.USER_CODE AS sap_user, T2.NumAtCard AS invoice_no, T2.Comments AS invoice_remarks'
            .' FROM OJDT T0'
            .' INNER JOIN JDT1 T1 ON T0.TransId = T1.TransId'
            .' LEFT JOIN OPCH T2 ON T0.BaseRef = T2.DocNum'
            .' LEFT JOIN PCH1 T3 ON T2.DocEntry = T3.DocEntry'
            .' LEFT JOIN OUSR T4 ON T0.UserSign = T4.USERID'
            .' WHERE T1.Account = \'11603001\''
            .' AND T1.Debit <> 0'
            .' AND T0.RefDate >= :startDate'
            .' AND T0.RefDate <= :endDate'
            .' ORDER BY T0.BaseRef DESC, T0.TransId, T1.Line_ID';
    }
}
