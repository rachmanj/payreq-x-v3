# Spec — Jurnal Akrual otomatis untuk AP Invoice BPJS Ketenagakerjaan

Pemicu: permintaan Iwan 14 Sep 2026 (grill ronde 1 dijawab 14 Sep 2026).
Konteks: invoice BPJS TK yang terbit bulan September 2026 mengacu perhitungan tenaga kerja bulan **Agustus 2026**, sehingga bebannya harus diakui di Agustus (posting date **31 Agu 2026**) lewat jurnal umum sebagai pasangan AP Invoice BPJS TK tersebut.

## 1. Goal

Saat user submit **AP Invoice BPJS Ketenagakerjaan** ke SAP, aplikasi otomatis membuat **SAP Journal Entry (jurnal akrual)** dengan posting date akhir bulan sebelum periode. **BPJS Kesehatan TIDAK termasuk.**

## 2. Keputusan (grill 14 Sep 2026)

| Q | Keputusan |
|---|---|
| Q1 | JE akrual = **Dr 61201003** (CC 20, ProjectCode = unit) / **Cr 21601001** "Biaya Yang Masih Harus Dibayar". Sisi AP Invoice BPJS TK diubah jadi **Dr 21601001 / Cr AP** supaya beban tidak dobel. Kesehatan tetap Dr 61201004 / Cr AP. |
| Q2 | JE **tidak memakai PPN** — satu nominal = nilai biaya pada AP Invoice (tanpa baris/VAT di JE). VAT/PPN tetap hanya muncul di AP Invoice. |
| Q3 | Posting date JE default = **hari terakhir bulan sebelum `periode`** (periode 2026-09 → 31 Agu 2026), berbasis field `periode` (bukan DocDate), **bisa di-override** di form, ditampilkan di preview. |
| Q4 | Checkbox **"Buat jurnal akrual BPJS TK" default ON**. Urutan: **AP Invoice dulu → JE sesudahnya**. JE gagal → **AP tetap tersimpan** (posted), JE tercatat gagal + tombol **Retry JE**. |
| Q5 | JE disimpan lewat **modul Journal Entry yang sudah ada** (`journal_entries` + lines, post via `JournalEntrySubmissionService`) + kolom penghubung di `bpjs_ap_invoices`. |

## 3. Scope

**In:** BPJS Ketenagakerjaan (create + submit + preview + retry JE + tampilan index), reuse modul JE.
**Out:** BPJS Kesehatan (tidak berubah), mekanisme akrual untuk modul lain (utilities/DDS), reversal otomatis (JE reversal tetap manual lewat modul Journal Entry), perubahan skema SAP.

## 4. DB changes (`bpjs_ap_invoices`, additive nullable)

| Kolom | Tipe | Catatan |
|---|---|---|
| `auto_je` | boolean default true | centang dari form (hanya berlaku utk TK) |
| `je_posting_date` | date nullable | default akhir bulan sebelum periode (override dari form) |
| `journal_entry_id` | FK `journal_entries` nullOnDelete | penghubung ke modul JE |
| `je_status` | enum(`pending`,`success`,`failed`,`skipped`) nullable | skipped = checkbox tidak dicentang / bukan TK |
| `je_error` | text nullable | pesan gagal dari SAP/validasi |
| `je_submitted_at`, `je_submitted_by` | timestamp, FK users | audit |

Akun: `BpjsApInvoice::ACCOUNT_CODES[kesehatan] = 61201004` (tetap), `ACCOUNT_CODES[ketenagakerjaan] = 21601001` (BARU), tambahan `EXPENSE_ACCOUNT_CODES[ketenagakerjaan] = 61201003` untuk sisi debit JE akrual, dan konstanta akun akrual `21601001`.

## 5. Alur

1. **Form create:** pilih jenis. Kalau **Ketenagakerjaan** → muncul checkbox "Buat jurnal akrual (default ON)" + field "Tanggal posting jurnal" (default = akhir bulan sebelum periode, ikut berubah saat periode diubah, boleh diedit). Jenis Kesehatan → dua field itu disembunyikan/tidak dikirim.
2. **Preview:** menampilkan payload AP Invoice **dan** ringkasan payload JE akrual (akun, debit/kredit, posting date) bila akan dibuat.
3. **Submit:** (a) posting AP Invoice ke SAP → sukses → status `posted` + log; (b) bila TK & `auto_je` → buat baris JE lokal + post ke SAP → sukses: `je_status=success` + `journal_entry_id` + `sap_journal_no` (di baris JE) + log; gagal: `je_status=failed` + `je_error`, **AP tetap posted**.
4. **Index:** kolom "Jurnal Akrual" (No. JE SAP + badge status; tombol **Retry JE** saat failed, tombol buka detail JE saat success).
5. **Retry JE:** hanya membuat/memposting JE yang gagal; **idempoten** — kalau `journal_entry_id` sudah ada dan JE-nya success, tidak boleh membuat JE kedua.

## 6. Risiko & penjagaan

- **Beban dobel** kalau sisi AP Invoice TK tetap Dr 61201003 → wajib pindah ke 21601001 (Q1).
- **JE dobel**: satu AP invoice maksimum satu JE akrual (guard `journal_entry_id`/`je_status`); retry hanya saat failed.
- **JE tidak boleh ada tanpa AP**: JE dibuat setelah AP sukses; tidak ada jalur "JE dulu".
- Posting SAP tidak bisa dihapus (hanya reversal) → validasi akun/tanggal/nominal sebelum submit, dan tampilkan di preview.
- `21601001` harus ada di master akun SAP (terverifikasi ada: "Biaya Yang Masih Harus Dibayar").
- BPJS Kesehatan tidak boleh ikut membuat JE (guard di service, bukan hanya di UI).

## 7. Verifikasi wajib

- Builder AP: TK → akun baris **21601001**; Kesehatan → **61201004** (test unit).
- JE akrual TK: Dr **61201003** / Cr **21601001**, nominal = `amount` AP, `date` = akhir bulan sebelum periode (mis. periode 2026-09 → 2026-08-31), project = unit, cost_center = 20.
- Periode Januari → posting date 31 Desember tahun sebelumnya.
- Override tanggal posting ikut tersimpan & dipakai di JE.
- Checkbox OFF → `je_status=skipped`, tidak ada JE dibuat.
- Kesehatan → tidak pernah membuat JE.
- JE gagal → AP tetap `posted`, `je_status=failed`, `je_error` terisi; Retry JE sukses → `je_status=success` dan **tidak** menghasilkan JE kedua.
- Preview menampilkan ringkasan JE sebelum submit.
- Full suite tidak menambah kegagalan baru (baseline repo: 3 gagal pre-existing).

## 8. Pertanyaan terbuka (untuk dikonfirmasi saat pilot)

- Nominal yang diinput di AP Invoice BPJS TK: apakah DPP (tanpa PPN) atau total tagihan? Payload AP saat ini mengirim `VatGroup B100`; JE akrual memakai nominal yang sama tanpa PPN (sesuai Q2). Bila ternyata BPJS TK seharusnya tanpa PPN sama sekali, payload AP perlu ditinjau terpisah.
- Siapa yang berhak submit (permission `submit_sap_ap_invoice_bpjs` — acc-team/manager/superadmin) tetap sama.
