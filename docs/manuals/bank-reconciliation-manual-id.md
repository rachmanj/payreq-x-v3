# Rekonsiliasi bank (Cashier)

Modul ini mencocokkan **baris laporan bank** (dari PDF Rekening Koran) dengan **baris GL SAP** untuk rekening bank (Giro) dan bulan tertentu.

## Siapa yang dapat membukanya

Buka menu **Cashier** di bilah atas, lalu **Bank Reconciliation**. Anda juga memerlukan hak akses **akses_koran** (satu area dengan **Rekening Koran**). Peran yang lebih luas (misalnya **admin**, **superadmin**, **cashier**) dapat melihat semua rekonsiliasi; pengguna lain hanya melihat rekonsiliasi untuk rekening bank di **project** mereka.

## Prasyarat

- Data **Giro** (rekening bank) sudah ada di sistem.
- Dokumen **Rekening Koran** (**tipe** `koran`) sudah diunggah untuk Giro tersebut di **Rekening Koran** (Cashier → Rekening Koran), untuk periode yang ingin direkonsiliasi.

## Memulai rekonsiliasi baru

1. Buka **Cashier** → **Bank Reconciliation**.  
2. Gunakan aksi untuk membuka **Create** (path URL: `/cashier/bank-reconciliation/create`).  
3. Pilih **Giro** (rekening bank).  
4. Pilih **periode** (bulan).  
5. Pilih dokumen **Rekening Koran** (`koran`) untuk Giro tersebut.  
6. Simpan. Sistem membuat rekonsiliasi berstatus **processing** dan mengantrekan:
   - parsing PDF laporan bank menjadi **bank statement lines**;
   - pengambilan **SAP GL lines** untuk akun/periode tersebut.

Tunggu pekerja antrean (queue worker) selesai. Muat ulang halaman detail rekonsiliasi untuk melihat jumlah baris terbaru.

## Layar detail rekonsiliasi

Di halaman **show** rekonsiliasi Anda dapat:

- **Re-parse statement** — mengantrekan ulang job parsing PDF (jika dokumen Koran terlampir).  
- **Fetch SAP lines** — mengantrekan pengambilan SAP GL sekali lagi.  
- **Auto-match** — mengantrekan pencocokan otomatis baris bank dengan baris SAP.  
- **Manual match** — memilih satu atau lebih baris laporan bank dan satu atau lebih baris SAP GL untuk membentuk **match group**.  
- **Unmatch** — menghapus match group yang ada (tidak tersedia setelah rekonsiliasi selesai).

Gunakan endpoint **status** atau muat ulang UI untuk memastikan jumlah baris bank, baris SAP, dan match group bertambah sesuai kemajuan job.

## Menyelesaikan rekonsiliasi

Setelah pencocokan memuaskan, gunakan **Complete** untuk menandai rekonsiliasi **completed**. Setelah itu:

- Aksi auto-match, manual match, dan unmatch tidak dapat dilakukan lagi.  
- Anda diarahkan ke tampilan **report** untuk rekonsiliasi ini.

## Hak akses dan penanganan masalah

Jika **Bank Reconciliation** tidak tampil, minta administrator memberi **akses_koran** (dan akses Cashier yang sesuai). Jika hasil parsing atau SAP tetap kosong, periksa PDF Koran valid, queue worker berjalan, dan pengaturan SAP bridge untuk pengambilan GL.
