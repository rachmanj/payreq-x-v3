<?php

namespace App\Support\Sap;

/**
 * Query PPN Masukan (baris debit akun 11603001) untuk didaftarkan ke SAP lewat SQLQueries.
 *
 * PENTING — pelajaran 29 Sep 2026: parser SAP B1 menolak identifier tanpa kurung siku
 * ("Invalid SQL syntax"). Query yang sudah terbukti jalan di produksi (AO_OPEN3) memakai
 * bentuk `FROM [JDT1] WHERE [Account] = :paramStack`. Karena itu semua nama tabel/kolom di
 * sini WAJIB dikurung siku. Alias tabel (T0/T1/...) tanpa kurung siku, sama seperti query
 * asli tim pajak yang berjalan normal di SAP.
 *
 * Hindari juga konstruksi yang tidak dipakai query tim: subquery di JOIN dan COALESCE.
 * Penggandaan baris akibat join ke PCH1 (beberapa baris invoice) tidak berbahaya karena
 * upsert di aplikasi berkunci (trans_id, line_id).
 */
class AoPpnin1Query
{
    public const CODE = 'AO_PPNIN1';

    public const NAME = 'AccountingOne PPN Masukan (11603001 debit lines)';

    public static function sqlText(): string
    {
        return 'SELECT [T0].[TransId] AS [trans_id], [T1].[Line_ID] AS [line_id],'
            .' [T0].[BaseRef] AS [document_no], [T0].[CreateDate] AS [creation_date],'
            .' [T0].[RefDate] AS [posting_date], [T2].[U_MIS_FPDate] AS [faktur_date],'
            .' [T2].[CardCode] AS [vendor_code], [T2].[CardName] AS [vendor_name],'
            .' [T2].[U_MIS_FPNum] AS [faktur_no], [T1].[Debit] AS [amount],'
            .' [T3].[Project] AS [project_code],'
            .' CASE [T1].[TransType]'
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
            .' END AS [doc_type],'
            .' [T0].[Memo] AS [remark], [T4].[USER_CODE] AS [sap_user],'
            .' [T2].[NumAtCard] AS [invoice_no], [T2].[Comments] AS [invoice_remarks]'
            .' FROM [OJDT] T0'
            .' INNER JOIN [JDT1] T1 ON [T0].[TransId] = [T1].[TransId]'
            .' LEFT JOIN [OPCH] T2 ON [T0].[BaseRef] = [T2].[DocNum]'
            .' LEFT JOIN [PCH1] T3 ON [T2].[DocEntry] = [T3].[DocEntry]'
            .' LEFT JOIN [OUSR] T4 ON [T0].[UserSign] = [T4].[USERID]'
            .' WHERE (([T2].[U_MIS_FPDate] >= :startDate AND [T2].[U_MIS_FPDate] <= :endDate)'
            .' OR ([T2].[U_MIS_FPDate] IS NULL AND [T0].[RefDate] >= :startDate AND [T0].[RefDate] <= :endDate))'
            ." AND [T1].[Account] = '11603001'"
            .' AND [T1].[Debit] <> 0'
            .' ORDER BY [T0].[BaseRef] DESC, [T0].[TransId], [T1].[Line_ID]';
    }
}
