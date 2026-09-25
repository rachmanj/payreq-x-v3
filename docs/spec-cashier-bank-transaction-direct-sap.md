# Spek: Submit langsung ke SAP dari menu Bank Transaction (kasir)

Status: **hasil grill 25 Sep 2026 — belum diimplementasikan**
Sumber keputusan: Iwan (rachmanj), ronde 1 & 2 grill-me.

## 1. Goal

Kasir (role `cashier`/`cashier_*`) mencatat **pindah buku bank → Petty Cash (PC)** dalam **satu kegiatan**: buat transaksi di menu Bank Transaction, tekan Submit, dan jurnalnya **langsung terkirim ke SAP** — tanpa langkah validasi terpisah oleh Accounting. Saldo PC di aplikasi naik **saat jurnal SAP berhasil**, sehingga aplikasi dan SAP tidak pernah berbeda.

Sasaran tambahan: menghilangkan peran `validated_by` sebagai penghambat (data: 51 dari 77 validasi bank ditangani Rifka Annisa).

## 2. Scope

### In scope
- Tombol **Submit** di halaman `cashier.bank-transactions.show` (`/cashier/bank-transactions/{id}`) — submit tetap di halaman Bank Transaction, tidak ada halaman baru.
- Hanya untuk Verification Journal `type = 'bank'`.
- Hanya untuk pola baris: **tepat satu akun bank di sisi kredit** + seluruh sisi debit memakai akun yang ada di daftar izin.
- Kasir memilih **jenis transaksi** di form: `Pindah buku ke PC` / `Biaya admin bank` / `Bunga bank` — pilihan ini memfilter akun yang boleh dipakai.
- **Ambang nominal** per parameter (default Rp 100.000.000).
- Incoming + saldo PC dibuat **setelah** jurnal SAP sukses.
- Audit: jejak siapa yang submit + penanda validasi otomatis oleh kasir.
- Aturan CRITICAL di `.cursorrules` diperbarui: validasi wajib **dikecualikan** untuk pola bank→PC dengan nominal ≤ ambang.

### Out of scope
- VJ tipe lain (`verification`, `cash`) tetap dua langkah: draft → validasi Accounting → submit SAP.
- Posting dari bank ke akun beban/aset lain (mis. Dr `61211099`, Dr akun vendor) **tidak diizinkan** di form ini → diarahkan ke prosedur payreq/realization.
- Kasir **tidak** mendapat izin `cancel_sap_journal`; koreksi tetap storno oleh Accounting.
- Tidak mengubah alur Cash In Journal / cashonhand.

## 3. Keputusan (verbatim hasil grill)

| # | Pertanyaan | Keputusan |
|---|---|---|
| Q1 | Gerbang validasi | (a) submit kasir = validasi otomatis + langsung kirim SAP, untuk (c) hanya pola bank→PC; submit tetap di halaman Bank Transaction; lewat izin baru |
| Q2 | Cakupan jenis | Biaya admin bank & bunga bank ikut; transaksi bank ke akun lain diblokir (lewat payreq/realization). Pembayaran Part / Pajak Kendaraan **bukan** pengecualian — terverifikasi sebagai bank→PC murni, remark saja yang berbeda |
| Q3 | Timing saldo aplikasi | Incoming dibuat **hanya setelah** jurnal SAP sukses |
| Q4 | Batas nominal | **Rp 100.000.000** per transaksi (configurable) |
| Q5 | Salah input sudah terposting | Kasir tidak boleh storno; Accounting yang menstorno; modal konfirmasi tegas sebelum kirim |
| Q1 r2 | Daftar akun | Setuju; dibuat **configurable** |
| Q3 r2 | Jenis transaksi | Dipilih kasir (dropdown), bukan dideteksi sistem |
| Q5 r2 | >ambang | Saldo PC tetap menunggu SAP berhasil |

## 4. Parameter (tabel `parameters`, diatur lewat menu Admin → Advance Parameters)

Mengikuti pola yang sudah ada (`max_submitted_payreq/ALL/5`, `dashboard_clearing_accounts/ALL/13101020,13101021,11101020`).

| name1 | name2 | param_value awal | Arti |
|---|---|---|---|
| `cashier_vj_sap_limit` | `ALL` | `100000000` | Ambang nominal (rupiah) yang boleh langsung diposting kasir |
| `cashier_vj_sap_accounts` | `ALL` | `11101005,11101008,11101010,11101004,11101006,71201001,71201006,71201007,71201002,71101001` | Akun yang boleh muncul di sisi debit jalur langsung |

Sisi kredit **wajib** satu akun bank (prefix `11201`) = akun yang dipilih di field Bank Account.

## 5. Perubahan teknis

1. **`Cashier\BankTransactionController::submit`**
   - Tetap membuat nomor/status, tapi: jika pola baris memenuhi syarat (1 bank di kredit + semua debit di daftar izin) **dan** amount ≤ `cashier_vj_sap_limit` **dan** user punya izin → set `validation_status = validated`, `validated_by = user id`, `validated_at = now()`, tandai `auto_validated_by_cashier = true`, lalu kirim ke SAP lewat `JournalEntrySubmissionService`/`SapJournalSubmissionService` yang sudah dipakai modul VJ.
   - Jika SAP menolak → VJ kembali `submitted` (bukan posted), tampilkan pesan error SAP apa adanya; **tidak** membuat Incoming.
   - Jika amount > ambang atau pola tidak memenuhi syarat → perilaku lama (menunggu validasi Accounting).
2. **Incoming** dibuat di dalam transaksi yang sama **setelah** respons SAP sukses (`sap_journal_no` terisi), dengan `receive_date = now()`, `will_post = 1`, `nomor` = nomor VJ, `description` = `Bank Transaction: <nomor> - <deskripsi>`.
3. **Permission baru** `cashier_submit_vj_to_sap` lewat migrasi + seeder (pola `ValidateVjPermissionSeeder`): di-seed ke role `cashier`, diberikan ke user 81 (Alwi Hafizhan, 021C), 130, 141.
4. **Validasi form**: dropdown Jenis transaksi memfilter daftar akun (PC vs biaya/bunga bank); server menolak kombinasi akun di luar daftar izin dengan pesan yang mengarahkan ke payreq/realization.
5. **Modal konfirmasi** sebelum submit: "Jurnal langsung posted di SAP B1, tidak bisa diedit; koreksi lewat storno oleh Accounting."
6. **Audit tampilan**: kolom/penanda "divalidasi otomatis oleh kasir" pada halaman show & daftar VJ; filter/izin `see_vj_not_posted` tetap berlaku.
7. **`.cursorrules`**: tambahkan pengecualian tertulis pada CRITICAL rule #3 (validasi wajib) untuk pola bank→PC ≤ ambang dengan izin `cashier_submit_vj_to_sap`.

## 6. DB changes

- `verification_journals`: kolom baru `auto_validated_by_cashier` (boolean, default false) — migrasi baru, bukan ALTER manual.
- `parameters`: 2 baris baru (lihat §4) — via seeder/insert idempotent.
- `permissions` + `role_has_permissions` + `model_has_permissions`: 1 izin baru (migrasi + seeder).

## 7. UI/UX

- Halaman `cashier.bank-transactions.create` & `edit`: tambah select **Transaction Type** (English, mengikuti bahasa halaman) + filter akun; catatan kecil di bawah form tentang ambang dan bahwa submit langsung memposting ke SAP.
- Halaman `show`: tombol Submit dengan modal konfirmasi; setelah sukses tampilkan nomor jurnal SAP + link ke halaman VJ.
- Tidak ada style baru — ikuti soft-UI yang sudah dipakai modul kasir.

## 8. Route / endpoint

Tidak ada route baru. Yang berubah perilakunya: `POST /cashier/bank-transactions/{id}/submit` (`cashier.bank-transactions.submit`).

## 9. Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Salah input sudah terposting di SAP (jurnal tidak bisa dihapus) | Modal konfirmasi tegas, audit jelas, koreksi lewat storno Accounting, izin dibatasi per user |
| Saldo aplikasi vs SAP berbeda | Incoming dibuat hanya setelah SAP sukses |
| Kasir memilih akun yang salah | Whitelist akun (parameter) + filter per jenis transaksi + server menolak akun di luar daftar |
| SAP error / down saat submit | VJ tetap `submitted`, pesan error tampil, saldo tidak berubah, bisa submit ulang |
| Kontrol internal melemah (validasi dilewati) | Izin per user, ambang nominal, audit "auto validated", aturan proyek diperbarui eksplisit |
| Kasir memakai menu ini untuk pembayaran vendor | Akun beban non-daftar ditolak + pesan mengarah ke payreq/realization |

## 10. Fakta produksi yang jadi dasar (terverifikasi 25 Sep 2026)

- Gerbang saat ini: `PreparesVerificationJournalShow::canManageSapInfoForVj()` mensyaratkan `validation_status = VALIDATION_VALIDATED`.
- 588 VJ `type='bank'`, seluruhnya `posted` + `validated` (tidak ada tunggakan).
- Validasi bank ditangani: Rifka Annisa 51×, Administrator 15×, SysAdmin 6×.
- Pola akun nyata: PC `11101005/11101008/11101010/11101004/11101006`; bank `11201005/11201006/11201072/11201028`; biaya `71201001/71201006/71201007/71201002`; pendapatan `71101001`.
- Contoh end-to-end: VJ `26062100153` (021C, 22-09-2026, Rp 5.000.000) → SAP `267690705` (JdtNum 304460), baris Cr `11201005` / Dr `11101005`, keduanya proj 021C & cc 30; Incoming id 4718 ikut memuat nomor jurnal SAP yang sama.
- "Pembayaran Part / Pembelian Part / Pembayaran Pajak Kendaraan / Sparepart" = bank→PC murni (12+ contoh: Cr `11201005` ↔ Dr `11101005`, nominal identik) — remark saja yang berbeda.

## 11. Yang masih terbuka

- Label dropdown jenis transaksi: usul Dea English (`Transfer to Petty Cash`, `Bank Admin Fee`, `Bank Interest`) mengikuti bahasa halaman kasir.
- Daftar user penerima izin bisa ditambah setelah implementasi (Dea sebutkan 81/130/141; sisanya menyusul bila perlu).
