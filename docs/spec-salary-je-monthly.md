# Spec (PENDING) — JE Pembebanan Biaya Gaji Karyawan Bulanan

**Status: DITUNDA (24 Sep 2026)** — menunggu keputusan antar departemen (Payroll / Accounting / SAP).
Diminta Iwan: "create JE pembebanan biaya gaji karyawan setiap bulan", ditaruh di **Journal Entries / JE Templates**.
Gril (grill-me) sudah dijalankan 2 ronde; ronde 1 disetujui penuh, ronde 2 belum dijawab karena ditunda.

---

## 1. Goal

Membuat JE pembebanan biaya gaji bulanan ke SAP B1 secara otomatis dari file Excel rekap dari Payroll Dept,
menggantikan input manual. Dipakai tim accounting tiap bulan (dan untuk pembayaran gajinya).

## 2. Scope

**In:** import Excel rekap Payroll → pemetaan kategori×site → preview → submit JE ke SAP → riwayat per periode.
**Out (untuk sekarang):** perhitungan payroll itu sendiri (dari Payroll Dept), pembuatan file rekap, perubahan master karyawan.

## 3. Fakta dari SAP (bukti, bukan asumsi)

### a. JE pembebanan acuan 2025 — SAP **257600116** "Salary Apr 2025" (JdtNum 146800, tanggal 04-Apr-2025, CC 160)
- Debit `61201001` **Employee - Salary** — 8 baris per project (satu baris per project):
  000H 897.142.426 · 001H 622.296.757 · 017C 2.334.516.046 · 021C 1.480.347.516 · 022C 2.816.394.963 · 023C 2.199.412.119 · APS 560.383.054 · 025C 2.870.000
- Debit `61201013` **Employee - Wages** — 6 baris per project:
  000H 53.355.000 · 001H 3.000.000 · 017C 878.769 · 021C 5.665.000 · 023C 807.692 · 025C 13.625.000
- **Kredit satu baris** `13101009` (akun Salaries, belum terdaftar di master akun aplikasi) = **10.990.694.342**, CC 160, project 000H.
- Total debit = kredit ✓; angka = Total Salary di Excel April 2025 ✓.

### b. Pemetaan kategori → akun (dibuktikan aritmetika, cocok persis di semua site)
- `61201001` Employee - Salary = **Staff + Non Staff + Daily Casual**
  (contoh 000H: 822.010.461 + 44.804.965 + 30.327.000 = 897.142.426 ✓; 001H: 594.401.207 + 19.745.550 + 8.150.000 = 622.296.757 ✓)
- `61201013` Employee - Wages = **Magang + ATAP + ARKA QQ KOP**
  (contoh 000H: 3.480.000 + 39.875.000 + 10.000.000 = 53.355.000 ✓)
- Kolom site Excel 2025 → project: HO→000H · BO→001H · 017C · 021C · 022C · 023C · 025C · APS→APS

### c. Kondisi 2026 (hasil sisir SAP)
- 23 JE bermemo "Salary" sepanjang 2026; polanya `Salary <Bulan> <Tahun> & Kompensasi` bertanggal ~tanggal 5 bulan berikutnya, dan semuanya **berbentuk pembayaran**:
  - 267685144 "Salary Juli 2026 & Kompensasi" (05-08-2026): **2 baris** — debit `13101009` 9.365.407.237 → kredit `11201023` BCA IDR.
  - 267685145 "Salary Juni 2026 & Kompensasi" (10-07-2026): debit `13101009` 10.640.183.949 → kredit `11201023` BCA.
- **TIDAK ADA satu pun JE pembebanan 2026** yang memakai `61201001`/`61201013` (disisir untuk semua JE 01-Jun s/d 01-Sep 2026).
  → pertanyaan terbuka: apakah pembebanan 2026 memang belum pernah dibuat (fitur ini yang akan memulai), atau dibuat tim SAP di luar aplikasi dengan akun lain.
- Akun `13101009` ada di SAP tetapi **belum ada di master akun aplikasi** (`accounts`) → perlu didaftarkan saat implementasi.

## 4. Format Excel Payroll

### Format 2025 — `Report Salary April 2025 (Revisi 19.05.2025).xls`, sheet `04.2025`
- Judul: "Rekapitulasi Salary dan Premi", "Periode : April 2025".
- Baris = kategori: Staff, Non Staff, Daily Casual, Magang, ATAP, ARKA QQ KOP.
- Kolom = site (2 sub-kolom: Kary = jumlah orang, Nilai = rupiah): HO, BO, 017C, 021C, 022C, 023C, 025C, APS, Total.
- Total: 1.163 karyawan / **Rp 10.990.694.342**.
- Blok "RINCIAN PEMBAYARAN": VIA BANK, ARKA Life 10.000.000, 1.445.772.198, **Total Pembayaran 10.990.694.345** vs **Total Salary 10.990.694.342**, Selisih 3.

### Format 2026 (berlaku mulai 2026) — `Report Salary Agustus 2026 (04.09.2026).xls`, sheet `08.2026`
- Kategori: Staff, Non Staff, Daily Casual, Magang, **BOC/BMC**, **OJT Prasasta**, ARKA QQ KOP, **Bonus Triwulan** (kosong).
- Kolom site **7**: HO, BO, 017C, 021C, 022C, **025C**, APS → **023C tidak ada lagi**.
- Total: 1.020 karyawan / **Rp 10.760.346.356**.
- Blok "RINCIAN PEMBAYARAN" kini memuat rincian **per bank**: Mandiri 1.121.323.885 · BPD 1.933.574.996 · BNI 417.239.453 · BCA 6.255.756.757 (subtotal 9.727.895.091) · Cash HO 382.654.637 · Cash Daily 28.552.000 · Cash APS 82.731.800 · Cash HO Kompensasi 21.628.333 · **Cash HO (Pph 21) 262.981.195** · ARKA Life 10.000.000 (subtotal 1.032.451.263) → Total Pembayaran 10.760.346.354 vs Total Salary 10.760.346.356 (selisih -2).
- Pembaca .xls di box Dea: `python3` + `xlrd` (bukan pandas/libreoffice) — resep: `~/.hermes` tidak perlu, cukup `import xlrd` lalu `xlrd.open_workbook(path)`.

## 5. Keputusan yang SUDAH disetujui (ronde 1 + tambahan Iwan)

1. Parser khusus format rekap; kolom site & baris kategori dari **template yang bisa diatur**; file dengan struktur tak dikenal **ditolak** dengan pesan; file asli disimpan sebagai lampiran.
2. Pemetaan kategori → akun jadi bagian template (nilai awal = pemetaan di §3b); kategori tak terpetakan **ditolak**, bukan masuk akun default.
3. Struktur baris pembebanan: 1 baris per (akun × project), **cost center selalu diisi** (default 160, bisa diubah), memo `Salary <Bulan> <Tahun>`, project header 000H.
4. Anti dobel: kunci per periode (YYYY-MM) — satu JE aktif per periode; upload ulang hanya setelah JE lama reversed atau statusnya gagal; halaman riwayat menampilkan periode + nomor JE SAP.
5. Angka yang dipakai = **Total Salary**; selisih dengan Total Pembayaran ditampilkan sebagai **peringatan**, bukan error.
6. **Tanggal JE pembebanan = akhir bulan periode beban** (gaji beban April 2025 → JE 30-Apr-2025), walaupun pelaporan/pembayarannya terjadi bulan berikutnya (Mei 2025).
7. **Baris bank mengikuti tanggal transaksi bank** — ada input tanggal transaksi bank (pembayaran tidak selalu sebulan dengan pembebanan).
8. Fitur mengikuti **format 2026** (file Agustus 2026 sebagai acuan bentuk baru).

## 6. Keputusan yang MASIH TERBUKA (gril ronde 2 — belum dijawab)

1. Apakah pembebanan 2026 sudah pernah dibuat di luar aplikasi (akun lain), atau fitur ini yang memulai?
2. Akun untuk kategori baru 2026: BOC/BMC, OJT Prasasta (± `61201013` Wages) dan Bonus Triwulan (± `61201002` Employee - Insentive).
3. Apakah fitur membuat **dua JE** (1: pembebanan akhir bulan debit beban/kredit `13101009`; 2: pembayaran debit `13101009`/kredit per bank+kas, tanggal = tanggal transaksi bank) atau fase 1 hanya pembebanan.
4. Perlakuan baris **Cash HO (Pph 21)** Rp 262.981.195 — kredit akun hutang PPh 21 (bukan kas) + nomor akunnya.
5. Parser dinamis untuk kolom site (2026 tanpa 023C) — setuju/tidak.
6. **Antar departemen** (alasan penundaan): pemetaan kategori→akun final, perlakuan Pph 21/kompensasi/ARKA Life, siapa pemilik proses pembayaran (bank mana dipakai, kapan), serta format rekap yang akan dipakai konsisten mulai kapan.

## 7. Rencana teknis (draft, belum dikerjakan)

- **DB:** manfaatkan `journal_entry_templates` + `journal_entry_template_lines` (sudah ada) dengan jenis template baru (mis. `salary_monthly`); tambah tabel riwayat periode (periode YYYY-MM, file, status, `sap_journal_no`, jdt_num, submitted_by/at) + kolom tanggal transaksi bank pada JE pembayaran.
- **Parser:** service baru (mis. `SalaryReportParser`) khusus layout rekap (judul + baris kategori + kolom site + blok Rincian Pembayaran); validasi: total kolom = baris Total, Total Salary vs Total Pembayaran (peringatan), kategori/site harus terpetakan.
- **SAP:** reuse `JournalEntryBuilder`/`SapJournalEntryBuilder` + `JournalEntrySubmissionService` (pola preview → submit → log ke `sap_submission_logs`).
- **UI:** menu **Journal Entries → JE Templates** (halaman baru: upload → preview baris → submit), bahasa mengikuti halaman JE yang ada, gaya VJ Soft UI.
- **Permission:** permission baru (usul `submit_salary_je`) untuk role accounting/acc-team, dibuat lewat migrasi + seeder.
- **Akun:** daftarkan `13101009` (Salaries) + akun bank/kas/PPh21 yang dipakai di blok pembayaran ke master `accounts`.

## 8. Risks

- JE di SAP **tidak bisa diedit** setelah posted → wajib preview + konfirmasi sebelum submit; koreksi hanya lewat reversal.
- Format rekap bisa berubah lagi antar periode → parser harus memberi pesan jelas, bukan menebak.
- Perbedaan total (± Rp 2–3) dan baris non-gaji (Pph 21, ARKA Life, kompensasi) mudah salah akun bila pemetaan tidak disetujui antar departemen.
- Data master akun belum lengkap (13101009 dll.) → bisa gagal validasi kalau tidak didaftarkan lebih dulu.
