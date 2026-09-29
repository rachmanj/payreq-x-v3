# Spec: Sinkronisasi otomatis data PPN Masukan dari SAP ke AccountingOne

> Status: **DISETUJUI Iwan 29 Sep 2026** ("ikut rekomendasi") — 4 keputusan: harian + lookback 60 hari · tabel baru · unggahan manual tetap ada (ditandai sumber) · gagal = tercatat + penanda merah.
> Melengkapi: `docs/spec-ppn-monitoring.md` (monitoring PPN).

## 1. Goal

Menghapus ketergantungan pada langkah manual (jalankan query SQL di SAP → ekspor Excel → unggah ke aplikasi) yang **terbukti bisa berhenti tanpa terdeteksi**: unggahan terakhir 1 Juli 2026, sehingga data PPN Masukan aplikasi kosong Juli–September padahal nilai nyata di SAP ±Rp 2,4 miliar/bulan.

Setelah fitur ini: data PPN Masukan **tertarik sendiri setiap hari** dari SAP, tersimpan permanen, dan kalau gagal langsung terlihat.

## 2. Scope

**Di dalam:** penarikan baris **akun `11603001` (PPN Masukan) sisi debit** dari `JDT1`/`OJDT` + `OPCH` (UDF faktur pajak) + `OUSR`, lalu disalurkan ke `fakturs`. Termasuk: pendaftaran query bernama di SAP, command terjadwal, tabel penyimpanan, tombol tarik manual, panel status, perbaikan cacat query lama.

**Di luar:** akun staging lain (`21101001`, `21101031`, `51106011`, dll — tetap lewat unggahan manual sampai ada kebutuhan lain), impor Coretax (P2 terpisah), dan menarik data **keluaran** (PPN Keluaran sudah punya jalur sendiri).

## 3. Tech decisions

- **Mekanisme**: ulangi pola yang sudah terbukti di `SapService` — `ensureSqlQuery(sqlCode, name, sqlText)` (daftarkan sekali, `POST SQLQueries`) + `executeSqlQuery(sqlCode, params)` (`GET SQLQueries('<code>')/List`). SQL jalan langsung ke `JDT1`/`OJDT`, sama seperti query manual tim.
- **Query baru**: `AO_PPNIN1` — versi rapih dari query tim, dengan tiga perbaikan:
  1. **Periode dari tanggal faktur pajak** (`OPCH.U_MIS_FPDate`), fallback `OJDT.RefDate` bila FP date kosong — **bukan** `OJDT.CreateDate` (cacat lama).
  2. **Fan-out `PCH1` dirapikan**: ambil satu project per dokumen (`MIN(PCH1.Project)` + `GROUP BY`) supaya satu jurnal tidak menjadi beberapa baris.
  3. **Sertakan kunci identitas baris** `OJDT.TransId` + `JDT1.Line_ID` untuk upsert idempoten.
  - Parameter: `:startDate`, `:endDate` (bukan hardcode).
- **Penjadwalan**: command `ppn:sync-input-vat`, dijadwalkan **harian 05:00 WITA** (= `0 21 * * *` UTC di hari sebelumnya) dengan **lookback 60 hari** untuk menangkap dokumen yang telat diposting. Tanpa `withoutOverlapping` → pakai.
- **Penyimpanan**: tabel baru `ppn_input_sync` — hasil tarikan mentah tersimpan permanen (tidak bisa hilang seperti `daily_txes` yang bisa di-`truncate`), dengan jejak batch & waktu tarik.
- **Penyaluran ke `fakturs`**: pakai perhitungan yang sudah teruji (`FakturPpnCalculationService` + `copyToFakturs`), ditambah kolom penanda sumber.
- **Unggahan manual dipertahankan** sebagai cadangan; setiap baris ditandai sumbernya.

## 4. DB changes

**A. Tabel baru `ppn_input_sync`** (`create_ppn_input_sync_table`):
- `id`, `trans_id` (bigint), `line_id` (int), `document_no` (varchar), `doc_type` (varchar), `creation_date` (date), `posting_date` (date), `faktur_date` (date, nullable), `vendor_code`, `vendor_name`, `faktur_no` (varchar, nullable), `amount` decimal(20,2), `project_code`, `remark`, `sap_user`, `invoice_no`, `invoice_remarks`
- `sync_batch` (varchar), `synced_at` (timestamp), `source` enum(`sap_auto`,`excel_manual`) default `sap_auto`
- `unique(trans_id, line_id)` — dasar upsert idempoten.

**B. Perluasan `fakturs`** (`add_sync_source_to_fakturs_table`):
- `sync_source` enum(`sap_auto`,`excel_manual`,`manual`) default `manual` — supaya jelas asal tiap baris.

**C. Tabel status sinkronisasi** — cukup satu baris kunci di tabel parameter yang ada, atau tabel kecil `ppn_sync_runs`:
- `id`, `started_at`, `finished_at`, `status` enum(`running`,`success`,`failed`), `rows_fetched`, `rows_upserted`, `rows_faktur_created`, `message` (text, nullable), `triggered_by` (nullable).

## 5. Job & alur

1. `ppn:sync-input-vat` — parameter opsional `--days=60` (lookback) dan `--triggered-by=<user id>`.
2. Alur: `ensureSqlQuery('AO_PPNIN1')` → `executeSqlQuery` untuk rentang tanggal → upsert ke `ppn_input_sync` (kunci `trans_id`+`line_id`) → salurkan baris baru ke `fakturs` (via perhitungan tarif/DPP yang ada) → tulis hasil ke `ppn_sync_runs`.
3. Gagal di langkah mana pun: catat status `failed` + pesan, **jangan** menandai sukses, dan jangan menghapus hasil tarikan sebelumnya.
4. Dijadwalkan di `app/Console/Kernel.php` (harian 21:00 UTC = 05:00 WITA), tanpa overlap.

## 6. UI (minimal, agar fitur bisa dipakai)

- `/accounting/tax/ppn/sync` — panel status: tarikan terakhir (waktu, jumlah baris, status), riwayat 20 tarikan terakhir, **tombol "Tarik dari SAP sekarang"** (izin `manage_tax_monitoring`), dan penanda merah bila tarikan terakhir gagal atau lebih dari 2 hari tidak ada tarikan sukses.
- Daftar `fakturs` menampilkan kolom **sumber** (SAP-otomatis / Excel-manual / manual).

## 7. Risiko & catatan

1. **Pendaftaran query = menulis satu objek ke SAP produksi** (`POST SQLQueries`). Ini kelas perubahan yang sudah lazim di aplikasi ini (`AO_OPEN3`, `AO_OPGL1`, `AO_OPHDR2` sudah ada), tapi tetap perubahan di prod → hanya didaftarkan saat fitur dijalankan pertama kali.
2. **Keterbatasan `SQLQueries`**: tabel non-jurnal (`OIGE`/`ODLN`) tidak bisa diakses lewat jalur ini (sudah tercatat di kode) — untuk PPN Masukan tidak diperlukan.
3. **Tanggal FP kosong** pada sebagian dokumen → memakai `RefDate`, dan baris seperti itu ditandai di daftar periksa.
4. **Lookback tumpang tindih** → aman karena upsert berkunci `(trans_id, line_id)`.
5. **Volume**: ±600–800 baris/bulan → jauh di bawah batas paginasi Service Layer.
6. **Duplikat nomor faktur** yang sah (satu faktur menutup beberapa dokumen) tetap diperbolehkan; yang diperbaiki hanya fan-out query-nya.

## 8. Kriteria terima

- Setelah command dijalankan untuk rentang Juli–September 2026, `fakturs` (purchase) berisi ratusan baris per bulan (bukan 0/1), dan jumlah PPN Masukan aplikasi **mendekati** angka GL akun `11603001` di SAP — selisihnya dapat dijelaskan baris per baris.
- Command dijalankan dua kali untuk rentang sama **tidak** menambah baris (idempoten).
- Panel status menampilkan tarikan terakhir dengan benar, termasuk saat command sengaja digagalkan.
