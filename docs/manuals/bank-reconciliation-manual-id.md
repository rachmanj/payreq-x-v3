# Rekonsiliasi bank (Cashier)

Modul **Bank Reconciliation** mencocokkan **baris laporan bank** (dari PDF Rekening Koran) dengan **baris GL SAP** untuk rekening bank (**Giro**) dan bulan tertentu.

## Cara menggunakan fitur Bank Reconciliation

Ikuti alur berikut dari awal sampai rekonsiliasi tervalidasi:

1. **Upload Rekening Koran (PDF)** — buka **Cashier** → **Rekening Koran**. Di **Dashboard**, klik sel bulan pada baris rekening yang kosong. Modal upload terbuka; rekening dan bulan sudah terkunci. Pilih file PDF, lalu unggah (perlu permission **`upload_koran`**).
2. **Mulai rekonsiliasi** — setelah PDF terupload, klik sel bulan yang sama (sel terisi). Di modal, bagian **Bank Reconciliation** → tombol **Mulai rekonsiliasi**. Atau buka **Cashier** → **Bank Reconciliation** → **Create**.
3. **Isi form Create** — pilih **Giro**, **periode** (bulan), dan dokumen **Rekening Koran** untuk periode tersebut. Simpan. Status awal **processing**; sistem mengantrekan parsing PDF dan pengambilan baris SAP GL.
4. **Tunggu queue worker** — pastikan `php artisan queue:work` berjalan di server. Muat ulang halaman **detail rekonsiliasi** (**Review**) sampai jumlah baris bank dan SAP muncul.
5. **Cocokkan baris** — di halaman review gunakan **Auto-match** untuk pencocokan otomatis, atau centang baris bank + baris SAP lalu **Match selected** untuk pencocokan manual. Gunakan **Unmatch** jika perlu membatalkan satu grup cocok.
6. **Periksa saldo** — bilah total di layar review harus seimbang (selisih mendekati nol) sebelum diajukan.
7. **Ajukan validasi** — klik **Submit for validation**. Setelah itu rekonsiliasi terkunci untuk diedit sampai validator menyetujui atau menolak.
8. **Validator** (permission **`validate_bank_reconciliation`**) — buka **Bank Reconciliation** → tab **Pending validation**, atau kartu **Bank reconciliation pending validation** di dashboard utama. Klik **Validate** → di halaman review pilih **Validate** (setujui) atau **Reject** (tolak dengan alasan).
9. **Laporan** — setelah **Validate**, status **completed** dan tervalidasi. Buka **Report** / **Lihat laporan rekonsiliasi** untuk mencetak ringkasan.

**Singkat:** Upload Koran → Create reconciliation → Auto-match / manual match → **Submit for validation** → validator **Validate** → **Report**.

## Siapa yang dapat membukanya

Buka menu **Cashier** di bilah atas, lalu **Bank Reconciliation**. Anda juga memerlukan hak akses **`akses_koran`** (satu area dengan **Rekening Koran**). Peran yang lebih luas (**admin**, **superadmin**, **cashier**, **approver_bo**, **cashier_bo**, **corsec**) dapat melihat semua rekonsiliasi; pengguna lain hanya melihat rekonsiliasi untuk rekening bank di **project** mereka.

Anda juga dapat mengetik **Bank Reconciliation** atau **rekonsiliasi** di kolom **Search Menu here** di bilah atas (jika punya **`akses_koran`**).

## Prasyarat

- Data **Giro** (rekening bank) sudah ada di sistem.
- Dokumen **Rekening Koran** (**tipe** `koran`) sudah diunggah untuk Giro dan bulan yang ingin direkonsiliasi.

## Upload Rekening Koran dari dashboard

Buka **Cashier** → **Rekening Koran** (halaman **Dashboard**). Klik sel bulan pada baris rekening:

- **Sel kosong** — modal upload; rekening dan bulan terkunci (perlu **`upload_koran`**). Upload ganda untuk rekening/bulan yang sama ditolak.
- **Sel terisi** — lihat tanggal upload, buka PDF, lanjut ke rekonsiliasi bank, atau hapus PDF (perlu **`delete_koran`**; tombol hapus dinonaktifkan jika rekonsiliasi menunggu validasi atau sudah selesai).

Ikon kecil pada sel menunjukkan status rekonsiliasi (belum dimulai, memproses, dalam review, menunggu validasi, selesai).

## Memulai rekonsiliasi baru

**Dari dashboard Koran:** klik sel bulan yang sudah punya PDF → **Mulai rekonsiliasi** (ikon timbangan / tombol di modal).

**Dari menu Bank Reconciliation:**

1. Buka **Cashier** → **Bank Reconciliation**.
2. Klik aksi **Create** (URL: `/cashier/bank-reconciliation/create`).
3. Pilih **Giro**, **periode**, dan dokumen **Rekening Koran**.
4. Simpan. Sistem mengantrekan parsing PDF → **bank statement lines** dan fetch **SAP GL lines**.

## Layar detail rekonsiliasi (Review)

Di halaman review (**show**) Anda dapat:

- **Re-parse statement** — antre ulang parsing PDF (jika dokumen Koran terlampir).
- **Fetch SAP lines** — antre ulang pengambilan GL SAP.
- **Auto-match** — pencocokan otomatis baris bank dengan SAP.
- **Manual match** — pilih baris bank dan baris SAP → cocokkan sebagai satu grup.
- **Unmatch** — hapus grup cocok (tidak tersedia setelah terkunci).
- **Submit for validation** — ajukan ke validator jika saldo sudah seimbang.

Muat ulang halaman atau pantau jumlah baris saat job antrean selesai.

## Validasi rekonsiliasi (validator)

Pengguna dengan **`validate_bank_reconciliation`** (biasanya **admin** / **superadmin**) yang **bukan** penyusun sesi tersebut dapat:

- Melihat tab **Pending validation** di **Bank Reconciliation**.
- Melihat kartu **Bank reconciliation pending validation** di dashboard utama.
- Di halaman review sesi yang **pending validation**: klik **Validate** (setujui → status **completed**, buka **Report**) atau **Reject** (kembali ke penyusun dengan alasan).

## Laporan rekonsiliasi

Setelah validator menyetujui, buka **Report** dari halaman review atau dari ikon sel hijau di dashboard Koran. Laporan menampilkan ringkasan saldo, baris outstanding, dan tombol **Print**.

## Hak akses (permission)

| Permission | Fungsi |
|------------|--------|
| **`akses_koran`** | Melihat **Rekening Koran** dan **Bank Reconciliation** |
| **`upload_koran`** | Upload PDF Rekening Koran dari dashboard Koran |
| **`delete_koran`** | Hapus PDF Koran (dibatasi project; diblokir jika rekonsiliasi terkunci) |
| **`validate_bank_reconciliation`** | Menyetujui / menolak rekonsiliasi yang diajukan |

## Penanganan masalah

- **Menu tidak tampil** — minta administrator memberi **`akses_koran`**.
- **Parsing / SAP kosong** — periksa PDF valid, queue worker aktif, pengaturan SAP B1 Service Layer.
- **Tidak bisa Submit for validation** — pastikan selisih bank vs buku sudah seimbang dan semua baris relevan sudah dicocokkan atau dikecualikan.
- **Tidak bisa hapus PDF** — rekonsiliasi mungkin **pending validation** atau **completed**; hubungi validator atau admin.
- **Jawaban HELP usang** — administrator menjalankan `php artisan help:reindex` setelah manual diperbarui.
