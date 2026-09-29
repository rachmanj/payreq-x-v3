# Spec: Monitoring PPN (PPN Masukan & Keluaran) di AccountingOne

> Status: **DRAF untuk diperiksa Iwan** — belum ada kode yang ditulis.
> Tanggal: 29 Sep 2026 · Dasar: hasil grilling (2 ronde, semua keputusan disetujui Iwan) + riset Coretax DJP + inventaris aplikasi + penyisiran SAP B1 produksi.

## 1. Goal

Membuat tim pajak/accounting bisa **memonitor PPN setiap masa pajak** dengan tiga hal yang sekarang tidak ada:

1. **Rekonsiliasi 3 arah**: nilai PPN di **SAP** (`VatSum` dokumen) ↔ data faktur di **aplikasi** (tabel `fakturs`) ↔ daftar **prepopulasi PPN Masukan dari Coretax**.
2. **Eksposur PPN Masukan yang belum diterima**: daftar AP invoice yang sudah ada di SAP tapi faktur pajaknya belum diterima dari supplier (per supplier, dengan umur) — supaya tidak hangus karena lewat jendela kredit.
3. **Kunci masa pajak + gerbang persetujuan** sebelum SPT Masa PPN dilaporkan, sehingga angka yang dilaporkan punya jejak siapa menyiapkan dan siapa menyetujui.

Manfaat yang dituju: PPN Masukan tidak ada yang hangus (tidak overpay), faktur tidak valid cepat ketahuan (terhindar sanksi 2% DPP), dan pelaporan SPT punya dasar tertelusur.

## 2. Scope

**Di dalam lingkup (P1–P2):**
- Register dokumen pajak **per masa pajak** (`YYYY-MM`) — dirancang siap menampung PPh, tapi **P1 fokus PPN**.
- Rekonsiliasi otomatis + tampilan tiga selisih.
- Daftar eksposur "AP ada, faktur belum diterima" + ekspor Excel per supplier.
- Perbaikan perhitungan DPP: hapus hardcode 11% di `DailyTxController::copyToFakturs`, ganti tarif per faktur + dukungan DPP nilai lain (11/12).
- Validasi tarif per baris + penanda baris yang perlu diperiksa manusia.
- Masa pajak: penutupan + kunci + tombol buka-kunci berizin + persetujuan satu level.
- Dashboard PPN + ekspor Excel + berkas cetak/PDF untuk arsip SPT.
- 3 izin baru (lihat/kelola/setujui-tutup).

**Di luar lingkup (sengaja tidak dikerjakan):**
- Integrasi langsung ke Coretax (tidak ada API publik untuk PKP umum — jalur resmi hanya impor XML massal atau PJAP).
- Menjadi PJAP / host-to-host.
- Pengiriman permintaan faktur ke supplier secara otomatis (cukup ekspor bahan untuk tim pajak).
- Mengubah alur penerbitan faktur pajak keluaran ke Coretax (tetap seperti sekarang; hanya dipantau statusnya).
- Modul PPh lengkap (hanya kolom/tabel yang disiapkan).

## 3. Tech decisions

- **Stack**: Laravel 10 + Blade/AdminLTE (mengikuti `payreq-x-v3`, bukan Inertia/AntD).
- **Sumber nilai PPN**: dibaca dari **SAP B1 Service Layer** (read-only) — `PurchaseInvoices`/`Invoices` (`VatSum`, `DocTotal`, `DocDate`) dan bagan akun (`11603001`, `21701002`). Tidak ada penulisan ke SAP dari fitur ini.
- **Data Coretax**: **impor manual bulanan** (Excel/XML hasil ekspor dari portal Coretax) ke tabel staging — meniru kebiasaan upload Excel GL yang sudah jalan. Format kolom ekspor Coretax **perlu verifikasi** saat implementasi (riset hanya memastikan adanya template XML + converter Excel).
- **Tabel `fakturs` dipertahankan** dan diperluas (bukan diganti), supaya alur faktur keluaran yang sudah terhubung SAP tidak rusak.
- **Rekonsiliasi dijalankan on-demand** (tombol "Rekonsiliasi masa pajak") dan hasilnya disimpan sebagai snapshot per masa pajak — bukan job terjadwal (sesuai preferensi on-demand).
- **Jembatan data yang sudah ada** dipakai sebagai kunci utama: `fakturs.doc_num` (↔ dokumen SAP) dan `fakturs.faktur_no` (↔ Coretax).

## 4. DB changes (migrasi)

**A. Perluasan `fakturs`** (satu migrasi `add_tax_monitoring_fields_to_fakturs_table`):
- `masa_pajak` char(7) — `YYYY-MM`, diturunkan dari `faktur_date` (nullable; baris tanpa tanggal ditandai "belum bertanggal" dan muncul di daftar periksa).
- `ppn_rate` decimal(5,2) nullable — tarif yang dipakai faktur itu (0/1/1,1/1,2/7/10/11/12).
- `dpp_calculated` decimal(20,2) nullable + `dpp_source` enum(`gl`,`manual`,`formula`) — menandai DPP dihitung dari tarif faktur, bukan hasil `debit/0.11`.
- `npwp_lawan` varchar(25) nullable — NPWP/NITKU supplier, sumber: master Business Partner SAP.
- `validation_status` enum(`belum_diperiksa`,`valid`,`tidak_valid`,`diganti`) default `belum_diperiksa`.
- `coretax_status` enum(`belum_diketahui`,`approved`,`reject`,`diganti`) default `belum_diketahui`.
- `matched_doc_num` varchar(30) nullable + `matched_at` timestamp nullable — hasil pencocokan ke dokumen SAP.
- Unique index `(type, faktur_no)` (faktur_no nullable-safe) — mencegah faktur ganda yang sekarang bisa lolos.

**B. Tabel baru `tax_periods`** (`create_tax_periods_table`):
- `id`, `masa_pajak` char(7), `tax_type` enum(`ppn`,`pph`) default `ppn`, `project` nullable
- `status` enum(`open`,`prepared`,`approved`,`filed`,`locked`) default `open`
- `pk_total`, `pm_total`, `kb_lb` decimal(20,2) nullable (hasil snapshot rekonsiliasi)
- `diff_sap_app`, `diff_coretax_app`, `diff_pk_pm` decimal(20,2) nullable (tiga selisih)
- `snapshot_json` json nullable (rincian hasil rekonsiliasi saat snapshot diambil)
- `prepared_by`/`prepared_at`, `approved_by`/`approved_at`, `filed_at`, `closed_by`/`closed_at`, `notes`
- unique `(masa_pajak, tax_type)`

**C. Tabel baru `coretax_input_vat`** (`create_coretax_input_vat_table`) — staging prepopulasi PPN Masukan:
- `id`, `masa_pajak` char(7), `import_batch` varchar(40) (nomor batch + waktu impor), `npwp` varchar(25), `supplier_name` varchar(150)
- `faktur_no` varchar(30) (unik per masa), `faktur_date` date nullable, `dpp` decimal(20,2), `ppn` decimal(20,2), `status_faktur` varchar(30) nullable (approved/reject/diganti, bila tersedia dari ekspor)
- `match_status` enum(`unmatched`,`matched`,`manual`,`ignored`) default `unmatched`, `matched_faktur_id` nullable FK → `fakturs`
- `imported_by`, timestamps

**Yang TIDAK diubah**: alur submit faktur keluaran ke SAP, builder AP invoice, dan struktur `daily_txes` (hanya cara pembacaan DPP-nya yang diperbaiki).

## 5. UI/UX

Menu baru di bawah grup **Accounting → Pajak** (izin `view_tax_monitoring`):

| Halaman | Isi |
|---|---|
| `/accounting/tax/ppn` | Dashboard per masa pajak: kartu PK, PM, KB/LB; tiga selisih (SAP↔app, Coretax↔app, PK↔PM); daftar masa pajak dengan status (open/prepared/…/locked); tombol "Rekonsiliasi" & "Tutup masa" |
| `/accounting/tax/ppn/masukan` | Register PPN Masukan per masa pajak: faktur_no, tanggal, NPWP/nama supplier, DPP, PPN, tarif, status validasi, status Coretax, kecocokan SAP (hijau/kuning/merah) |
| `/accounting/tax/ppn/keluaran` | Register PPN Keluaran: nomor FP, tanggal, customer, DPP, PPN, status kirim SAP (AR/JE), status Coretax, umur hari |
| `/accounting/tax/ppn/belum-diterima` | Eksposur faktur pajak yang belum diterima — **aturan presisi: AP invoice dengan `VatSum > 0` DAN `U_MIS_FPNum` kosong** (invoice tanpa PPN dikecualikan karena memang tak butuh faktur). Dikelompokkan per supplier + umur + nominal PPN, dengan tombol ekspor Excel. Baseline terukur Jul–Sep 2026: hanya 2 invoice / PPN Rp 41,2 jt, jadi halaman ini berperan sebagai *guard rail* harian, bukan pembersihan besar |
| `/accounting/tax/ppn/import-coretax` | Unggah ekspor Excel/XML Coretax → pratinjau → simpan batch (menampilkan jumlah cocok/tidak cocok) |
| `/accounting/tax/ppn/periksa` | Daftar baris yang butuh keputusan manusia: tarif tidak dikenali, DPP tak bisa dihitung, faktur ganda, tanpa tanggal |
| `/accounting/tax/ppn/{masa}/cetak` | Berkas cetak/PDF ringkasan masa pajak untuk arsip SPT |

Aksi berizin (modal konfirmasi + jejak audit): **Rekonsiliasi**, **Tandai valid/tidak valid**, **Setujui**, **Tutup masa**, **Buka kunci masa** (wajib alasan).

## 6. API endpoints (internal app)

- `GET /accounting/tax/ppn/data` — DataTables dashboard & register (per masa, per status)
- `POST /accounting/tax/ppn/reconcile` — jalankan rekonsiliasi masa pajak → simpan snapshot ke `tax_periods`
- `POST /accounting/tax/ppn/import-coretax` — unggah + simpan staging
- `POST /accounting/tax/ppn/masukan/{id}/validate` — tandai validasi faktur
- `POST /accounting/tax/ppn/periods/{id}/prepare|approve|close|reopen` — alur status masa pajak
- `GET /accounting/tax/ppn/belum-diterima/export` — Excel per supplier
- `GET /accounting/tax/ppn/{masa}/export` — Excel ringkasan masa pajak
- `GET /accounting/tax/ppn/{masa}/cetak` — PDF arsip

## 7. Risks & hal yang perlu diverifikasi

1. **Format ekspor Coretax** (kolom Excel/XML yang benar, termasuk status faktur) — perlu diverifikasi dengan contoh ekspor nyata sebelum implementasi impor.
2. ~~Apakah SAP menyimpan nomor faktur pajak di PurchaseInvoice~~ → **SUDAH DIVERIFIKASI (29 Sep 2026): YA, terisi.** `U_MIS_FPNum` ada di `PurchaseInvoices` dan terisi pada **1.695 dari 2.243** invoice AP Jul–Sep 2026 (contoh nomor e-Faktur: `04002600241059362`). Setelah disaring hanya invoice **ber-PPN** (`VatSum > 0`): **1.678 dari 1.680 (99,9%)** punya nomor faktur; hanya **2 invoice** tanpa nomor (PPN Rp 41.206.622).
   **Konsekuensi desain:** (a) daftar eksposur dihitung dari SAP dengan aturan presisi **`VatSum > 0` DAN `U_MIS_FPNum` kosong** — bukan dari persentase mentah (25% tanpa nomor sebagian besar adalah invoice tanpa PPN yang memang tak perlu faktur); (b) SAP menjadi sumber nomor faktur yang setara `fakturs`, sehingga rekonsiliasi bernilai tertinggi adalah **nomor faktur di SAP ↔ daftar prepopulasi Coretax** (menjawab: apakah setiap faktur yang kita catat benar dilaporkan supplier dan muncul di prepopulasi kita).
3. **Batas pembacaan Service Layer** — jumlah dokumen per bulan terlihat mentok di 300 pada percobaan pertama; implementasi wajib memaginasi (`$top`/`$skip`) dan memverifikasi total terhadap layar SAP sebelum dipakai sebagai angka laporan.
4. **Tarif per baris** (efektif 11% vs 12% mewah, DPP nilai lain, kode transaksi 04/07) — bila tarif tak bisa ditentukan dari data, baris **tidak dihitung** dan masuk daftar periksa manusia (tidak menebak).
5. **Kualitas data lama**: baris `fakturs` tanpa `faktur_date` tidak akan masuk rekap bulanan (perilaku sekarang); perlu ditangani sebagai daftar periksa, bukan diperbaiki otomatis.
6. **`daily_txes` bisa dikosongkan** (`truncate`) dan hanya `fakturs` yang menyimpan hasil salinannya — spec ini tidak mengubahnya, tapi dicatat sebagai risiko kehilangan jejak (kandidat perbaikan terpisah).
7. **Hak akses**: acc-team boleh lihat; penandaan & ekspor terbatas; persetujuan/penutupan hanya pejabat yang ditunjuk (izin baru wajib di-seed ke role superadmin + role terkait di migrasi).

## 8. Rencana bertahap

- **P1 (inti)**: perluasan `fakturs` + tabel `tax_periods` + `coretax_input_vat`; perbaikan DPP/tarif; halaman masukan+keluaran+belum-diterima; rekonsiliasi 2 arah (SAP↔app) berjalan.
- **P2**: impor Coretax + rekonsiliasi 3 arah; dashboard masa pajak; kunci + persetujuan; ekspor Excel & PDF; daftar periksa.
- **P3 (kesiapan PPh)**: mengaktifkan `tax_type=pph` pada tabel yang sama (PPh 23/21/4(2)) tanpa membongkar struktur.

**Kriteria terima P1**: untuk satu masa pajak nyata (mis. Agustus 2026), aplikasi menampilkan PK, PM, selisih SAP↔app, dan daftar eksposur yang bisa dicocokkan manual ke dokumen — dengan angka SAP yang cocok terhadap pemeriksaan di layar SAP.
