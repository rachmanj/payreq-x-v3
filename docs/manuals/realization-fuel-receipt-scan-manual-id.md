# Scan nota BBM untuk realisasi (My PayReqs)

Fitur ini memakai AI (OpenRouter / model vision) untuk membaca **nota BBM Pertamina SPBU** dari foto dan membuat baris **detail realisasi** lebih cepat. Hanya untuk **Nota Pembelian Fuel**, bukan invoice umum atau jenis biaya lain.

## Siapa yang boleh memakai

Pengguna yang dapat membuka **My PayReqs** dan menambah detail realisasi pada realisasi **draft** (hak akses sama dengan **Add Detail** di halaman detail realisasi). Server memastikan Anda pemilik realisasi (atau **superadmin**).

## Prasyarat

- Payreq berstatus **paid** dengan realisasi **draft** dibuka di **Add realization details** (`user-payreqs/realizations/{id}/add_details`).
- **`OPENROUTER_API_KEY`** dikonfigurasi di server (administrator). Tanpa ini, permintaan scan gagal.
- Foto jelas: satu nota per gambar paling andal; satu foto berisi **banyak nota** dapat menghasilkan **beberapa baris** setelah **Scan All**.

## Membuka halaman detail realisasi

1. Buka **My PayReqs** di menu atas.
2. Buka payreq Anda dan lanjutkan **realisasi** sampai halaman **Add realization details** (tabel detail, tombol **Add Detail**, **Scan Fuel Receipts**, **Submit Realization**).

## Scan Fuel Receipts (bulk — disarankan)

Gunakan tombol kuning **Scan Fuel Receipts** di header kartu (subteks: **Hanya Nota Pembelian Fuel**).

1. Klik **Scan Fuel Receipts**.
2. Di modal, pilih satu atau lebih **gambar nota** (JPEG/PNG; di HP bisa pakai kamera).
3. Klik **Scan All**. Sistem mengirim setiap gambar ke AI **satu per satu** dan menampilkan progres (**X / N**).
4. Tinjau tabel: satu baris per nota yang terbaca. Kolom: **Description**, **Amount**, **Date**, **HM**, **Unit**, **Nopol**, **Qty**. Ubah sel sebelum simpan jika perlu.
5. Hapus baris salah dengan ikon tempat sampah.
6. Klik **Save All** untuk menyimpan semua baris valid ke tabel detail realisasi.
7. Periksa total dan selisih di footer, lalu **Submit Realization** jika sudah benar.

Jika satu gambar berisi **beberapa nota** (misalnya foto banyak struk di meja), **Scan All** seharusnya menambah **satu baris per nota** yang terdeteksi.

## Add Detail tanpa scan bulk

Anda tetap bisa menambah baris manual lewat **Add Detail** (deskripsi, jumlah, tanggal bon, field armada). **Scan Receipt with AI** di dalam modal Add/Edit bisa **disembunyikan** lewat konfigurasi; jika aktif, mengisi form dari satu foto nota.

## Field yang diisi AI

| Field | Sumber di nota |
|--------|----------------|
| **Description** | mis. `BBM Pertamax - SPBU 6476112`, atau **Fuel Kendaraan** jika grade/SPBU tidak terbaca |
| **Amount** | Total (Rupiah) |
| **Expense date** | Tanggal transaksi |
| **HM** (`km_position`) | KM / odometer tulisan tangan atau cetak |
| **Unit No** | Kode tulisan tangan seperti **VA 057**, **VA 083** (dua huruf, spasi, tiga digit) |
| **Nopol** | Plat cetak jika ada (diabaikan jika **Not Entered**) |
| **Qty** | Liter |
| **Type** / **UOM** | **fuel** / **liter** untuk nota SPBU |

**Unit No** harus ada di daftar equipment (dropdown **Unit No**). Jika AI membaca **VA 057** tetapi unit tidak ada di daftar, pilih unit yang benar manual sebelum simpan.

## Setelah menyimpan hasil scan

- Tabel detail di-refresh otomatis.
- **Submit Realization** tetap nonaktif sampai ada minimal satu detail.
- Aturan armada tetap berlaku (tanggal bon tidak boleh setelah hari ini, HM monotonic per unit, dll.). Perbaiki error validasi di tabel review atau edit baris setelah simpan.

## Pemecahan masalah

- **Scan gagal / OpenRouter tidak dikonfigurasi** — hubungi IT untuk **`OPENROUTER_API_KEY`** dan **`OPENROUTER_MODEL`** di `.env`.
- **Hanya satu baris dari banyak nota** — coba model vision lebih kuat (mis. **`google/gemini-2.5-pro`**) atau foto per nota; perbaiki pencahayaan.
- **Unit salah** — koreksi kolom **Unit** di tabel review; tulisan tangan harus cocok dengan master equipment.
- **Jawaban HELP usang** — administrator menjalankan `php artisan help:reindex` setelah perubahan manual di `docs/manuals/`.
