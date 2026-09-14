# Spec — Batalkan (Cancel) AP Invoice BPJS + reversal JE otomatis

Pemicu: permintaan Iwan 14 Sep 2026. Keputusan grill Q1–Q5 disetujui 14 Sep 2026.

## 1. Goal

Aplikasi bisa **membatalkan AP Invoice BPJS** yang sudah terposting ke SAP. Kalau yang dibatalkan **BPJS Ketenagakerjaan**, **jurnal akrualnya otomatis di-reverse** juga di SAP. BPJS Kesehatan: hanya AP Invoice-nya (tidak punya JE).

## 2. Keputusan (Q1–Q5)

| Q | Keputusan |
|---|---|
| Q1 | Hanya status **`posted`** dan **belum dibayar** (`paid_amount = 0`). Sebelum eksekusi, aplikasi **cek ke SAP**: dokumen ada, `Cancelled != tYES`, `DocumentStatus = bost_Open`, `PaidToDate = 0`. Kalau tidak memenuhi → **tolak** dengan pesan jelas (mis. "Batalkan outgoing payment-nya dulu"). |
| Q2 | Urutan: **AP Invoice di-cancel dulu → baru JE di-reverse**. AP sukses tapi reversal JE gagal → status lokal **`cancelled`**, `je_status = failed` + pesan error + tombol **Retry Reversal JE**. Tidak boleh ada kondisi AP masih hidup padahal JE sudah di-reverse. |
| Q3 | Permission **baru `cancel_sap_ap_invoice_bpjs`** (di-seed menyalin role dari `submit_sap_ap_invoice_bpjs`), **alasan pembatalan wajib** dan disimpan. |
| Q4 | Cakupan **hanya modul AP Invoice BPJS**. Kesehatan → cancel AP saja. TK → cancel AP + reversal JE. Modul lain tidak disentuh. |
| Q5 | Status lokal baru **`cancelled`** (baris tetap tampil + badge + siapa/kapan/alasan). **Anti-duplikat tidak memblokir** invoice pengganti untuk (jenis, unit, periode) yang sama. `SapSubmissionLog` khusus pembatalan. |

## 3. DB changes

**Migration 1 — kolom pembatalan (`bpjs_ap_invoices`, additive nullable):**
- `cancelled_at` timestamp, `cancelled_by` bigint, `cancel_reason` text
- `je_status` enum ditambah nilai **`reversed`**

**Migration 2 — permission** `cancel_sap_ap_invoice_bpjs` (`firstOrCreate`), role menyalin dari pemegang `submit_sap_ap_invoice_bpjs` (fallback superadmin/manager/acc-team) — pola sama seperti `create_submit_sap_utility_payment_permission`.

## 4. Backend

- `SapService::cancelPurchaseInvoice(string|int $docEntry): array` — `POST PurchaseInvoices({DocEntry})/Cancel` (204) lalu verifikasi `GET PurchaseInvoices({DocEntry})` → `Cancelled = tYES`. Return `{success, message, data}`; jangan menelan error SAP (pesan asli dipakai di UI).
- `BpjsApInvoiceController::cancel(CancelBpjsApInvoiceRequest $request, BpjsApInvoice $invoice, SapService $sap)`:
  1. Guard lokal: status harus `posted`; `paid_amount = 0`; `sap_doc_entry` terisi.
  2. Guard SAP: dokumen ada, `Cancelled != tYES`, `DocumentStatus = bost_Open`, `PaidToDate` ≈ 0 → kalau tidak, tolak dengan pesan spesifik.
  3. `cancelPurchaseInvoice($invoice->sap_doc_entry)` → sukses: `status = cancelled`, `cancelled_at/by`, `cancel_reason`, dan `SapSubmissionLog` (`document_type = bpjs_ap_invoice_cancellation`, status success/failed).
  4. Kalau `jenis = ketenagakerjaan` **dan** ada `journal_entry_id`: `JournalEntrySubmissionService::reverse($je, $user, $reason)` → sukses: `je_status = reversed`; gagal: `je_status = failed` + `je_error` (AP tetap `cancelled`).
- `BpjsApInvoiceController::cancelJe(BpjsApInvoice $invoice)` — hanya jalan bila `status = cancelled` dan `je_status = failed`; memakai ulang `JournalEntrySubmissionService::reverse()`. Idempoten (JE yang sudah reversed tidak di-reverse dua kali — guard sudah ada di service).
- Route (`routes/bpjs.php`, grup permission `cancel_sap_ap_invoice_bpjs`): `POST bpjs-ap-invoices/{bpjsApInvoice}/cancel` (`cancel`) dan `POST bpjs-ap-invoices/{bpjsApInvoice}/cancel-je` (`cancel-je`).
- `CancelBpjsApInvoiceRequest`: `cancel_reason` **required**, min 5 karakter.

## 5. UI

- `partials/action.blade.php`: tombol **"Batalkan"** hanya saat `status = posted`; membuka modal alasan (wajib). Untuk TK, teks konfirmasi SweetAlert menyebut **"jurnal akrual juga akan di-reverse di SAP"**. Tombol **"Retry Reversal JE"** saat `status = cancelled` + `je_status = failed`.
- Kolom status: badge **"Dibatalkan"** (netral/abu) + tooltip/isi kecil siapa & kapan & alasan.
- Kolom "Jurnal Akrual": saat `je_status = reversed` tampilkan badge **"Reversed"**.
- Label Bahasa Indonesia, pakai kelas/style yang sudah ada.

## 6. Risiko & penjagaan

- Cancel SAP **tidak bisa di-undo** → semua guard di atas wajib jalan **sebelum** memanggil SAP, dan pesan error SAP ditampilkan apa adanya.
- Jangan reverse JE sebelum AP sukses; jangan pernah menandai `cancelled` sebelum SAP mengonfirmasi.
- Invoice yang sudah dibayar **tidak boleh** dibatalkan dari fitur ini (harus batalkan OP dulu).
- Pembatalan ganda ditolak (status sudah `cancelled`).
- JE yang sudah di-reverse tidak boleh di-reverse lagi (guard di `JournalEntrySubmissionService::reverse`).

## 7. Verifikasi wajib (test)

1. Cancel invoice **Kesehatan** posted & belum dibayar → SAP cancel dipanggil sekali, status jadi `cancelled`, **tidak** ada pemanggilan reversal JE.
2. Cancel invoice **TK** posted & belum dibayar → SAP cancel dipanggil, lalu reversal JE dipanggil, `je_status = reversed`, baris `journal_entries` tertaut ditandai reversed.
3. Cancel invoice **pending/failed** → ditolak tanpa memanggil SAP.
4. Cancel invoice yang **sudah dibayar** (`paid_amount > 0`) → ditolak.
5. SAP menolak/mengembalikan error (mis. dokumen sudah closed) → status lokal **tetap `posted`**, pesan error tersimpan di log, tidak ada perubahan status.
6. **Reversal JE gagal** → status `cancelled` + `je_status = failed`; **Retry Reversal JE** sukses → `je_status = reversed`, tidak ada double reversal.
7. Cancel dua kali → ditolak.
8. User tanpa permission `cancel_sap_ap_invoice_bpjs` → 403; alasan kosong → error validasi.
9. Anti-duplikat: setelah `cancelled`, invoice baru untuk (jenis, unit, periode) yang sama **bisa** dibuat.
