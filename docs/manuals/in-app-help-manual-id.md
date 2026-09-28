# Bantuan Dalam Aplikasi (HELP)

Panel **Help** menjawab pertanyaan cara pakai aplikasi langsung dari dalam layar kerja, memakai potongan-potongan manual resmi di `docs/manuals/` dan petunjuk navigasi `docs/help-navigation.json`.

## Apa itu panel HELP dan di mana membukanya

- Klik ikon tanda tanya (**?**) di bilah atas untuk membuka panel **Help** sebagai modal.
- Panel punya dua tab: **How-to** (pertanyaan cara pakai) dan **Report / request** (laporan bug dan permintaan fitur).
- Jawaban disusun dari isi manual yang sudah diindeks. Bila topik tidak ada di manual, HELP akan mengatakannya terus terang alih-alih menebak.

## Cara bertanya di tab How-to

1. Buka panel **Help** → tab **How-to**.
2. Tulis pertanyaan di kotak **Your question** (maksimal 4000 karakter), mis. *Bagaimana cara membuat BAPSB bulanan?* atau *Kenapa dropdown Cheque / Bilyet kosong?*
3. Klik **Ask**. Jawaban muncul di bawah tombol, diikuti daftar sumber manual yang dipakai.
4. Bila jawaban kurang lengkap, tulis ulang pertanyaan dengan menyebut nama menu/tombol yang persis terlihat di layar (mis. **Cashier → BAPSB**, **+ Bilyet**, **Submit for validation**).

## Mengirim laporan bug atau permintaan fitur (tab Report / request)

1. Buka panel **Help** → tab **Report / request**.
2. Isi **Type** (**Bug** atau **Feature request**), **Title**, **Description**, dan **Steps to reproduce (optional)**.
3. Klik **Submit**. Laporan tersimpan di aplikasi dan dikirim ke alamat notifikasi yang dikonfigurasi administrator (`HELP_FEEDBACK_NOTIFY_EMAIL`); tanpa konfigurasi itu, laporan tetap tersimpan tanpa email.

## Siapa yang bisa memakai HELP (permission `akses_help`)

Parameter `help.ask` dan `help.feedback` memerlukan permission **`akses_help`** dan dibatasi maksimal 30 permintaan per menit per pengguna. Bila panel tidak menjawab (atau muncul *Access Denied*), minta administrator memberikan **`akses_help`**.

## Jika jawaban HELP kosong atau kurang tepat

- Bila jawaban berbunyi *This topic is not covered in the indexed manuals…*, artinya topik itu memang belum ada di manual — bukan berarti fiturnya tidak ada.
- Cek apakah manual terbaru sudah diindeks: administrator perlu menjalankan `php artisan help:reindex` setelah ada perubahan dokumentasi.
- Untuk topik yang sudah ada manualnya tetapi jawabannya meleset, sebutkan istilah yang muncul di manual (nama menu, tombol, kolom tabel, kode status) supaya pencocokan lebih tepat.
- Laporkan kekurangannya lewat tab **Report / request** agar manual diperbaiki.

## Untuk administrator: memperbarui pengetahuan HELP (`help:reindex`)

1. Perbarui atau tambahkan berkas manual di `docs/manuals/` (pasangan `*-id.md` dan `*-en.md`) dan, bila perlu, `docs/help-navigation.json`.
2. Jalankan `php artisan help:reindex` di server.
3. Perintah itu membaca seluruh berkas `docs/manuals/*.md` dan `docs/help-navigation.json`, memecahnya per judul `##`, lalu menyimpan hasilnya sebagai pengetahuan HELP (batch mengikuti `HELP_REINDEX_BATCH_SIZE`, default 20).

Konfigurasi terkait ada di `config/help.php`: `HELP_SIMILARITY_THRESHOLD` (default 0.22), `HELP_TOP_K` (default 6), `HELP_REINDEX_BATCH_SIZE`, `HELP_FEEDBACK_NOTIFY_EMAIL`, plus boost kecil untuk manual yang cocok dengan locale pengguna.

## Menulis manual agar mudah ditemukan HELP

- Simpan manual di `docs/manuals/`, selalu sebagai pasangan `*-id.md` (Bahasa Indonesia) dan `*-en.md` (English).
- Gunakan satu `#` untuk judul dan `##` untuk membagi bagian: setiap bagian `##` menjadi satu potongan pencarian.
- Tulis judul bagian memakai kata kunci yang benar-benar dicari pengguna (nama menu, tombol, kode status).
- Sebut label menu dan tombol persis seperti tampil di aplikasi — HELP tidak boleh menebak nama tampilan.
- Tambahkan entri di `docs/help-navigation.json` untuk pertanyaan “di mana letak menunya?” (menu path, route, permission, keywords).
- Setelah menambah manual baru, daftarkan di `README.md` folder ini.

## Daftar manual di folder ini (rujukan manual terkait)

| Topik | English | Bahasa Indonesia |
|-------|---------|------------------|
| Memulai aplikasi | `getting-started-en.md` | `getting-started-id.md` |
| Rekonsiliasi bank | `bank-reconciliation-manual-en.md` | `bank-reconciliation-manual-id.md` |
| RAB / Anggaran | `anggaran-manual-en.md` | `anggaran-manual-id.md` |
| Realisasi — scan nota BBM (AI) | `realization-fuel-receipt-scan-manual-en.md` | `realization-fuel-receipt-scan-manual-id.md` |
| Manual Journal Entry | `manual-journal-entry-manual-en.md` | `manual-journal-entry-manual-id.md` |
| SAP Sync — validasi VJ sebelum posting | `sap-sync-vj-validation-manual-en.md` | `sap-sync-vj-validation-manual-id.md` |
| **Administrasi Bilyet & BAPSB** (Bilyet Giro / Cek / LOA, pemeriksaan surat berharga bulanan) | `bilyet-administration-manual-en.md` | `bilyet-administration-manual-id.md` |
| Bantuan dalam aplikasi (HELP) | `in-app-help-manual-en.md` | `in-app-help-manual-id.md` |

## File terkait teknis (referensi developer)

- Rute HELP: `routes/help.php` (`help.ask`, `help.feedback`, middleware `permission:akses_help`, `throttle:30,1`).
- Controller: `app/Http/Controllers/Help/HelpController.php`; layanan: `app/Services/Help/` (`HelpAssistantService`, `HelpManualChunker`, klien penyedia AI).
- Perintah indeks: `php artisan help:reindex` (`app/Console/Commands/HelpReindexCommand.php`).
- Konfigurasi: `config/help.php`. Panel UI: `resources/views/templates/partials/help-panel.blade.php`.
