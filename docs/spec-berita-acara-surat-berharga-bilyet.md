# Spek: Berita Acara Pemeriksaan Surat Berharga Bank (BAPSB) — Bilyet Giro / Cek / LOA

Status: **hasil grill 28 Sep 2026 — siap implementasi**
Sumber keputusan: Iwan (rachmanj), ronde 1–3.

## 1. Goal

Setiap **bulan**, tiap unit yang memegang rekening **giro** (HO `000H`, BO `001H`, site `021C`, `022C`, `025C`) membuat **Berita Acara Pemeriksaan Surat Berharga Bank** atas **bilyet giro / cek / LOA** yang fisiknya ada: memeriksa kesesuaian fisik dengan data sistem, menandatangani dokumen, mengunggah scan ke aplikasi, lalu **divalidasi tim Accounting HO** — dengan pemantauan kepatuhan bulanan (**peringatan + daftar tunggakan**), meniru pola **PCBC** yang sudah berjalan.

Rekening **tabungan** (mis. Tab Bisnis Mandiri IDR, Mandiri IDR 0005 Berau) tidak menerbitkan surat berharga → **tidak perlu BAPSB**.

## 2. Scope

**In scope**
- Penanda **`needs_bilyet`** pada master `giros`: default `ya` untuk jenis `giro`, `tidak` untuk `tabungan`; Admin dapat mengubah per rekening untuk pengecualian.
- Form BAPSB **bulanan per project**: daftar bilyet ditarik **otomatis** dari master (bukan ketik ulang).
- Per baris bilyet: **Fisik: Ada / Tidak Ada** + **lokasi penyimpanan** + catatan/keterangan selisih.
- Lokasi penyimpanan: pilihan standar (**Brankas Site / Lemari Besi Accounting HO / Brankas BO / Safe Deposit Box Bank / Lainnya**) + kolom catatan bebas; dipakai per baris.
- Ringkasan: total nominal & jumlah per jenis (Bilyet Giro / Cek / LOA) + **mutasi bulan itu** (berapa cair, berapa void).
- **Nomor otomatis** berita acara (pola `BAPSB-0001/025C/09-2026`) + tanggal.
- **Cetak/print** berita acara dengan blok tanda tangan: **Dibuat oleh** (penyusun, otomatis dari user yang input), **Diperiksa oleh 1** dan **Diperiksa oleh 2** (nama diisi di form), **Disetujui oleh** → **kolom teks yang diketik penyusun** (opsional; site boleh dikosongkan karena validasi akhir di Accounting HO).
- Unggah **PDF yang sudah ditandatangani** → `dokumens` type baru **`bapsb`** (status `pending`).
- **Validasi** oleh tim Accounting HO (Iwan/Herry/Rifka/Prana/Elma) → status `validated`, memakai permission baru **`validate_bapsb_report`** (pola `validate_pcbc_report`).
- **Kepatuhan**: periode bulanan, batas submit **tanggal 5** bulan berikutnya; lewat batas → **peringatan** di dashboard unit + **daftar tunggakan** terpusat untuk Accounting HO. **Tidak memblokir** aksi kasir.

**Out of scope**
- Jenis **Debit** tidak diikutkan (auto-debit sistem bank — tidak ada bilyet fisik).
- Rekening **tabungan** tidak masuk BAPSB.
- Tidak mengirim apa pun ke SAP; tidak mengubah status/jumlah bilyet dari form ini.
- Tidak memblokir transaksi kasir (beda dari PCBC yang memblokir).
- Tidak ada email/notifikasi otomatis (mengikuti kebiasaan on-demand Iwan).

## 3. Tech decisions

- Meniru pola PCBC: **tabel data** + **dokumen PDF** di `dokumens` + **service kepatuhan** + **permission validasi** terpisah.
- BAPSB butuh **header + baris** (PCBC hanya satu baris hitungan, BAPSB memuat banyak bilyet) → dua tabel baru.
- Data bilyet diambil dari master `bilyets` yang sudah ada (kolom: `prefix`, `nomor`, `type` (`cek`/`bg`/`LOA`), `bilyet_date`, `cair_date`, `amount`, `status` (`onhand`/`release`/`cair`/`void`), `giro_id`, `project`) — **tidak ada duplikasi data**; baris BAPSB menyimpan **hasil pemeriksaan fisik**, bukan salinan nominal.
- Relasi bank: `bilyets.giro_id` → `giros.id`; `giros.sap_account` = kode akun bank. (Catatan 28 Sep 2026: `sap_account` untuk giro 017C `11201028` dan 022C `11201006` baru dilengkapi; 023C belum karena project nonaktif.)
- Jenis yang masuk daftar: bilyet dengan status **On Hand + Release** (fisik seharusnya masih ada) pada akhir periode. `Cair`/`Void` masuk ringkasan mutasi.

## 4. DB changes

- `giros.needs_bilyet` — boolean, default mengikuti jenis (`giro`=1, `tabungan`=0).
- Tabel baru **`bapsbs`** (header): `id`, `nomor`, `period` (`YYYY-MM`), `project`, `bapsb_date`, `prepared_by`, `checker1`, `checker2`, `approved_by` (nullable — dikosongkan untuk site), `total_bg`, `total_cek`, `total_loa`, `count_bg`, `count_cek`, `count_loa`, `count_cair`, `count_void`, `validation_status` (`pending`/`validated`), `validated_by`, `validated_at`, `dokumen_id`, `submitted_at`, `created_at`, `updated_at`, `deleted_at`.
- Tabel baru **`bapsb_lines`**: `id`, `bapsb_id`, `bilyet_id`, `type`, `nomor` (`prefix`+`nomor`), `bank_account`, `bilyet_date`, `cair_date`, `amount`, `status`, `physical_present` (bool), `location`, `location_note`, `remarks`, timestamps.
- Permission baru `akses_bapsb` (menu) + `validate_bapsb_report` (validasi) — dibuat via migrasi/seeder; `validate_bapsb_report` diberikan ke user id **13, 45, 11, 17, 112** (Iwan, Herry, Rifka, Prana, Elma) dan role `head_cashier`.
- `dokumens.type` bertambah nilai **`bapsb`** (kolom enum → perluasan enum di dalam migrasi).

## 5. UI/UX

- Menu **Cashier → BAPSB**: daftar per unit (DataTables, gaya yang sudah ada) + tombol **"+ BAPSB"** (permission `akses_bapsb`, dan hanya untuk project yang punya rekening `needs_bilyet`).
- Form BAPSB: pilih **Periode** (bulan) → daftar bilyet muncul otomatis (On Hand + Release, dikelompokkan per rekening bank) → tiap baris: **Fisik Ada/Tidak Ada**, **Lokasi**, **Catatan** → isi **Diperiksa oleh 1 & 2** → ringkasan total per jenis + mutasi bulan → **Simpan draft** → **Cetak** → **Unggah PDF** → **Submit**.
- Halaman detail: ringkasan, hasil validasi, tautan ke PDF dan ke tiap bilyet (riwayat bilyet sudah ada).
- Halaman **validasi** (HO): tampil antrian `pending` + tombol validasi (pola PCBC) + catatan.
- **Dashboard**: peringatan bila periode berjalan sudah lewat tanggal 5 dan BAPSB unit belum ada; daftar tunggakan terpusat untuk HO.
- Bahasa halaman mengikuti halaman cashier yang ada (English), gaya **VJ Soft UI**.

## 6. Route / endpoint

Tidak ada API eksternal. Route internal (prefix `cashier/bapsb`, name `cashier.bapsb.*`):
- `GET /` (index) + `GET /data` (DataTables)
- `GET /create`, `POST /` (store draft), `GET /{id}` (detail), `GET /{id}/edit`, `PUT /{id}`
- `GET /{id}/print` (berita acara siap tanda tangan)
- `POST /{id}/upload` (unggah PDF → `dokumens` type `bapsb`)
- `POST /{id}/submit` (kirim untuk validasi)
- `PUT /{id}/validate` (permission `validate_bapsb_report`)
- `GET /outstanding` (daftar tunggakan untuk HO)

## 7. Risks

| Risiko | Mitigasi |
|---|---|
| Bilyet di site belum terdaftar di master (data saat ini: bilyet hanya ada di 000H & 001H) | Form menampilkan peringatan bila sebuah rekening belum punya bilyet terdaftar + panduan import Excel di halaman Bilyets |
| `needs_bilyet` salah set → unit wajib/tidak wajib BAPSB | Default mengikuti jenis rekening + Admin bisa ubah; daftar tunggakan dihitung dari penanda ini |
| Bilyet bergerak setelah BA dibuat (cair/void) | BA memotret posisi **akhir periode**; setelah submit, baris tidak bisa diubah (hanya bisa dibatalkan lalu dibuat ulang) |
| Lokasi penyimpanan tidak seragam antar unit | Pilihan standar + catatan bebas + opsi "Lainnya" |
| Validasi HO menumpuk | Daftar tunggakan terpusat + status jelas per unit |
| Perubahan enum `dokumens.type` | Perluasan enum di migrasi (pola yang sudah dipakai repo ini), diuji di MySQL sebelum deploy |

## 8. Yang masih terbuka

- ~~Nama approver di dokumen untuk HO/BO~~ → **DIPUTUSKAN: kolom "Disetujui oleh" diketik penyusun** (opsional; HO/BO mengisi mas Herry / mba Ria, site boleh dikosongkan).
- **Rekap kepatuhan antar bulan** (unit mana yang telat, berapa kali) — **menyusul** setelah versi pertama berjalan.
