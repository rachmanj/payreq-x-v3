# Spek: Transfer Multi Rekening (Payreq)

Status: **disetujui Iwan 2026-09-11** (grill 2 ronde, semua keputusan di bawah final). Implementasi **belum dimulai** — menunggu aba-aba.

## 1. Masalah

Satu PR hanya boleh punya **satu** rekening tujuan (`payreqs.transfer_account_id`). Akibatnya, ketika kasir harus mentransfer satu PR ke beberapa rekening berbeda (mis. sebagian ke rekening pribadi requestor, sebagian langsung ke vendor), yang terjadi:
- PR harus dipecah jadi beberapa dokumen (approval berulang, total terbelah), atau
- transfer kedua dicatat di luar sistem (tidak ada baris `outgoing`, tidak ada bukti transfer yang terverifikasi).

Kasus nyata: keluhan Anisa (kasir) — pembayaran satu PR ke lebih dari satu rekening.

## 2. Fakta kode saat ini (hasil recon, bukan asumsi)

- `payreqs.payment_method` (`cash|transfer`) + `transfer_account_id` (FK nullable) — **satu** tujuan per dokumen.
- `outgoings` sudah punya: `amount`, `account_id` (akun kas/bank **sumber**), `payment_method`, `transfer_account_id` — tetapi `transfer_account_id` diisi dengan **menyalin** nilai dari payreq (`CashierApprovedController::auto_outgoing` & `store_pay`).
- Split payment **sudah ada**: 1 PR → N `outgoing` dengan nominal berbeda; halaman `/cashier/approveds/{id}/pay` menampilkan "Tujuan Transfer" dari header saja, tanpa pilihan tujuan per baris.
- Verifikasi AI bukti transfer (`VerifyTransferProofJob`) mengambil rekening tujuan dari **header** (`$outgoing->payreq->transferAccount`) untuk dibandingkan dengan hasil ekstraksi resi.
- `transfer_accounts` (20 baris) = daftar rekening milik user (`user_id` NOT NULL): bank, nomor, nama, label. **Tidak ada** master rekening bank vendor di DB (`creditors`/`utility_vendors` tidak menyimpan rekening bank).
- Ownership: rekening tujuan wajib milik requestor (`PayreqPaymentMethod::assertTransferAccountOwnership`).
- Payreq transfer **tidak** menghasilkan OP SAP (`SapVendorPaymentBuilder` hanya dipakai utilities/BPJS/installment/DDS) → fitur ini murni sisi aplikasi, SAP tetap menerima jurnal VJ seperti sekarang.

## 3. Keputusan (final)

**Ronde 1**
1. Daftar tujuan boleh **diusulkan requestor** di form PR (opsional) dan **ditentukan/diubah kasir** saat bayar (penentu akhir).
2. Memakai **mekanisme split yang sudah ada** (1 PR → N outgoing), ditambah pilihan rekening tujuan per baris. Tidak ada dokumen pembayaran baru.
3. Requestor tetap **hanya** boleh memakai rekening miliknya (+ vendor yang dia daftarkan); **kasir boleh menambah rekening vendor baru** dari halaman bayar, dengan jejak siapa menambahkan.
4. Approver **melihat daftar tujuan + nominal (read-only)** di halaman approval kalau requestor mengisinya; kalau kosong, approval cukup total seperti sekarang.
5. **Satu bukti transfer per tujuan**, dan verifikasi AI dibandingkan ke rekening tujuan **baris outgoing** (bukan header). PR lama wajib berperilaku identik.

**Ronde 2**
6. Rekening vendor baru dari kasir disimpan di tabel `transfer_accounts` yang ada, **pemilik = kasir** yang menambahkan, label diawali `Vendor – …`. Tidak ada tabel master vendor bank (untuk sekarang).
7. Alokasi aktual yang berbeda dari usulan requestor **tidak perlu approval ulang**, selama total tidak melebihi amount PR; perubahan komposisi tercatat di baris `outgoing` (+ remark kasir).
8. Print/PDF PR menampilkan daftar tujuan + nominal **hanya bila requestor mengisinya** (PR lama tampil seperti sekarang).
9. Tambahan UI kasir: kolom **"Rekening Tujuan" per baris** di `/cashier/outgoings` + tombol **"Isi dari rencana"** di halaman split (prefill baris dari daftar tujuan requestor).
10. Rilis **dua fase**: Fase A (DB minim + jalur kasir + verifikasi per baris + print/laporan), Fase B (form requestor mengusulkan daftar tujuan).

## 4. Desain

### 4.1 Fase A — jalur kasir (nyeri utama)
- `CashierApprovedController::store_pay` menerima `transfer_account_id` per baris pembayaran (default: header / tujuan rencana) dan menyimpannya ke `outgoing.transfer_account_id` (**tidak lagi menyalin buta** dari header). `auto_outgoing` tetap memakai header (perilaku lama).
- Halaman split: dropdown rekening tujuan per baris (opsional = tujuan header), tombol "Isi dari rencana" (Fase B, kalau daftar rencana ada), tombol tambah rekening vendor (modal, `POST /user-payreqs/transfer-accounts` dengan pemilik kasir).
- Validasi server-side: rekening tujuan harus milik requestor **atau** milik kasir yang membayar (jangan sampai user lain); nominal per baris > 0 dan akumulasi ≤ amount PR (aturan lama tetap).
- `VerifyTransferProofJob`: rekening tujuan dibaca dari `$outgoing->transfer_account_id`, **fallback** ke `$outgoing->payreq->transfer_account_id` bila NULL (data lama) → perilaku PR satu tujuan tidak berubah sama sekali.
- List `/cashier/outgoings`: tambah kolom "Rekening Tujuan" (label rekening per baris) — kolom metode pembayaran sudah ada.
- Print/PDF: bagian tujuan bersifat kondisional (baru muncul di Fase B saat ada daftar rencana).

### 4.2 Fase B — usulan requestor
- Tabel baru `payreq_transfer_destinations`: `id`, `payreq_id` (FK, cascade), `transfer_account_id` (FK), `planned_amount` (bigint, nullable — uang disimpan sebagai integer sesuai aturan repo), `remark` (nullable), `created_by`, `timestamps`.
- `payreqs.transfer_account_id` **tetap** diisi = tujuan utama (destinasi pertama) untuk kompatibilitas kode/tampilan lama; daftar lengkap hidup di tabel baru.
- Form advance & reimburse: tambah blok "Daftar Tujuan Transfer" (boleh 1 baris saja = perilaku sekarang), validasi jumlah rencana ≤ amount PR, ownership requestor.
- Halaman approval payreq & print PDF: menampilkan daftar tujuan + nominal (read-only, kondisional).
- Aturan edit: daftar tujuan mengikuti aturan metode pembayaran yang ada (bisa diubah saat `draft`/`revise`, read-only setelahnya).

### 4.3 Non-goals (tidak dikerjakan sekarang)
- Master rekening bank vendor bersama antar-cashier.
- Perubahan alur/route approval, stage approver, atau basis approval (tetap total dokumen).
- Kirim tujuan transfer ke SAP (tidak relevan: payreq transfer tidak membuat OP SAP).
- Multi-rekening **sumber** (akun kas/bank pengirim sudah bisa dipilih per baris sekarang).
- Perubahan pada reimburse `type=other`/`advance` di luar form & tampilan yang disebut di atas.

## 5. Regresi yang wajib dijaga

1. PR dengan **satu** tujuan (semua data lama) → seluruh alur (kasir, split, verifikasi bukti, print, laporan) berperilaku **identik**.
2. `outgoings.transfer_account_id` NULL (outgoing manual) tidak boleh bikin verifikasi bukti error.
3. Verifikasi AI tetap memakai toleransi yang sudah terbukti: suffix-match nomor rekening ter-mask, nominal harus sama.
4. Status PR: `split` saat pembayaran belum penuh, `paid`/`close` saat penuh — tidak berubah.
5. Route/permission tidak berubah (kasir yang boleh membayar tetap seperti sekarang: `akses_payreq_cashier` dsb.).

## 6. Uji yang akan dijalankan (sebelum minta deploy)

- Unit/feature: split 2 tujuan sekali bayar (2 outgoing, masing-masing rekening tujuan benar), total > amount PR ditolak, rekening milik user lain ditolak, kasir tambah rekening vendor → tersimpan dengan `user_id` kasir.
- Verifikasi bukti: job memakai rekening tujuan baris; kasus data lama (outgoing tanpa tujuan sendiri) tetap pakai header; mismatch/verified seperti sebelumnya.
- Regresi: PR satu tujuan → hasil `outgoing` & verifikasi sama seperti sebelum perubahan; print PR lama tidak berubah.
- Uji UI di dev dengan data dev (bukan prod), termasuk halaman split dan list outgoings.
- Tidak ada perubahan DB produksi sampai deploy disetujui; migrasi Fase B aman dijalankan (tabel baru).

## 7. Risiko

- **Kontrol approval** melemah kalau kasir bisa mengubah komposisi tujuan: dimitigasi dengan (a) total tetap basis approval, (b) jejak `created_by` untuk tujuan baru, (c) baris outgoing merekam tujuan aktual.
- **Duplikasi bukti transfer**: satu transfer besar untuk beberapa tujuan tidak didukung — tiap tujuan tetap butuh buktinya sendiri (keputusan #5).
- **Rekening vendor liar**: kasir bisa menambah rekening apa pun → dikurangi dengan label standar `Vendor – …` dan jejak pembuat; kalau nanti perlu, tambah validasi approval admin.
