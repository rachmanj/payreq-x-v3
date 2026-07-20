# Rekonsiliasi bank (Cashier)

Modul **Bank Reconciliation** mencocokkan **baris laporan bank** (dari PDF Rekening Koran atau input manual) dengan **baris GL SAP** untuk rekening bank (**Giro**) dan bulan kalender. Pencocokan memakai **polaritas debit/kredit berlawanan** (debit bank berpasangan dengan kredit SAP). Pengajuan hanya diizinkan jika **laporan rekonsiliasi** formal sudah seimbang: saldo bank yang disesuaikan sama dengan saldo buku yang disesuaikan.

## Cara menggunakan fitur Bank Reconciliation

Ikuti alur berikut dari awal sampai rekonsiliasi tervalidasi:

1. **Upload Rekening Koran (PDF)** — buka **Cashier** → **Rekening Koran**. Di **Dashboard**, klik sel bulan pada baris rekening yang kosong. Modal upload terbuka; rekening dan bulan sudah terkunci. Pilih file PDF, lalu unggah (perlu **`upload_koran`**). Lewati langkah ini jika hanya memakai baris bank **manual**.
2. **Mulai rekonsiliasi** — setelah PDF terupload, klik sel bulan yang sama → **Mulai rekonsiliasi**. Atau buka **Cashier** → **Bank Reconciliation** → **Create**.
3. **Isi form Create** — pilih **mode sumber** (**AI** = parsing PDF, atau **Manual** = ketik baris bank sendiri), **Giro**, **periode** (bulan), dan untuk mode AI dokumen **Rekening Koran**. Simpan. Mode AI status **processing** (parsing PDF + fetch SAP diantrekan). Mode Manual status **in review** (fetch SAP diantrekan; Anda menambah baris bank sendiri).
4. **Tunggu queue worker** — pastikan `php artisan queue:work` berjalan. Muat ulang halaman **Review** sampai jumlah baris bank dan SAP muncul. Jika job gagal, banner merah **Last error** menampilkan alasan (juga di catatan sesi).
5. **Isi opening / closing balances** — di Review, isi **Opening — Bank**, **Closing — Bank**, **Opening — Book**, **Closing — Book**, lalu **Save balances**. Parsing AI dan Fetch SAP mengisi otomatis jika tersedia; Anda bisa mengubahnya. **Kedua closing balance wajib** sebelum submit.
6. **Cocokkan baris** — gunakan **Auto-match**, atau centang baris bank + SAP (bilah bawah menampilkan net pilihan) lalu **Match selected as group**. Gunakan **Unmatch** untuk membatalkan grup. Opsional: **Exclude** baris unmatched dengan alasan, atau **Type** (kategori reconciling) pada baris unmatched.
7. **Periksa reconciliation statement** — bilah kuning harus menampilkan **Reconciled** (unexplained difference mendekati nol). Tombol submit tetap nonaktif saat **Incomplete** atau **Not reconciled**.
8. **Ajukan validasi** — klik **Submit for validation**. Editing terkunci. Validator dengan **`validate_bank_reconciliation`** menerima notifikasi.
9. **Validator** — buka **Bank Reconciliation** → tab **Pending validation**, atau kartu di dashboard. **Validate** (setujui → **completed**, buka **Report**) atau **Reject** (penyusun dinotifikasi beserta alasan).
10. **Laporan / ekspor** — setelah disetujui (atau kapan saja Anda bisa melihat sesi), buka **Report** untuk cetak, atau **Export Excel** untuk unduh file pernyataan.

**Singkat:** Upload Koran → Create (AI atau Manual) → Simpan closing balances → Auto-match / manual match → statement **Reconciled** → **Submit for validation** → validator **Validate** → **Report** / **Export Excel**.

## Siapa yang dapat membukanya

Buka **Cashier** → **Bank Reconciliation**. Anda perlu permission **`akses_koran`** (satu area dengan **Rekening Koran**). Route juga memeriksa **`akses_koran`**. Peran luas (**admin**, **superadmin**, **cashier**, **approver_bo**, **cashier_bo**, **corsec**) melihat semua project; pengguna lain hanya Giro di **project** mereka.

Anda juga dapat mengetik **Bank Reconciliation** atau **rekonsiliasi** di **Search Menu here** (jika punya **`akses_koran`**).

## Prasyarat

- Data **Giro** sudah ada; untuk fetch SAP, isi **`sap_account`** pada Giro (atau fallback **Account** tipe bank untuk project).
- Mode **AI**: dokumen **Rekening Koran** (**tipe** `koran`) untuk Giro dan bulan tersebut.
- Queue worker aktif untuk parse, fetch SAP, dan auto-match.

## Upload Rekening Koran dari dashboard

Buka **Cashier** → **Rekening Koran** (**Dashboard**). Klik sel bulan:

- **Sel kosong** — modal upload; rekening dan bulan terkunci (**`upload_koran`**). Upload ganda untuk rekening/bulan yang sama ditolak.
- **Sel terisi** — lihat tanggal upload, buka PDF, lanjut rekonsiliasi, atau hapus PDF (**`delete_koran`**; hapus dinonaktifkan jika rekonsiliasi menunggu validasi atau selesai).

Ikon kecil pada sel menunjukkan status rekonsiliasi (belum dimulai, memproses, dalam review, menunggu validasi, selesai).

## Memulai rekonsiliasi baru

**Dari dashboard Koran:** klik sel bulan yang sudah punya PDF → **Mulai rekonsiliasi**.

**Dari menu Bank Reconciliation:**

1. **Cashier** → **Bank Reconciliation** → **Create**.
2. Pilih mode **AI** atau **Manual**, **Giro**, **periode**, dan (AI saja) dokumen **Rekening Koran**.
3. Simpan. AI: antrekan parsing PDF dan fetch SAP. Manual: antrekan fetch SAP saja; tambah baris bank di Review.

## Opening dan closing balances

Di halaman Review, kartu **Opening / closing balances** menyimpan:

| Field | Arti |
|-------|------|
| **Opening — Bank** / **Closing — Bank** | Saldo dari laporan bank (sering dari parsing AI) |
| **Opening — Book** / **Closing — Book** | Saldo dari SAP (sering dari Fetch SAP) |

Klik **Save balances** setelah mengubah. Statement rekonsiliasi memakai saldo **closing**:

- **Adjusted bank** = Closing bank + jumlah net baris **buku** (SAP) yang unmatched  
- **Adjusted book** = Closing book − jumlah net baris **bank** yang unmatched  
- **Unexplained difference** = Adjusted bank − Adjusted book (harus mendekati **0** untuk submit)

Jika salah satu closing kosong, status menampilkan **Incomplete — enter closing balances**.

## Layar detail rekonsiliasi (Review)

Di halaman review (**show**) Anda dapat:

- **Re-parse PDF (AI)** / **Link & Parse** — antre ulang parsing PDF. Ada konfirmasi: mengganti baris bank dan menghapus match group terkait.
- **Fetch SAP lines** — antre ulang GL SAP. Ada konfirmasi: mengganti baris SAP dan menghapus match group terkait.
- **Auto-match** — exact, fuzzy (teks / AI), dan split (N:M). Grup match **manual** tetap dipertahankan saat auto-match dijalankan ulang.
- **Manual match** — centang baris unmatched; bilah bawah menampilkan net pilihan; **Match selected as group** jika bank net + SAP net ≈ 0.
- **Add / Edit / Delete bank line** — untuk baris manual atau koreksi (hanya unmatched).
- **Exclude / Include** — keluarkan baris dari total statement dengan alasan wajib (atau masukkan kembali).
- **Type** — kategori reconciling opsional pada baris unmatched (anotasi untuk laporan): misalnya deposit in transit, outstanding payment, biaya bank belum dibukukan, bunga/kredit belum dibukukan.
- **Submit for validation** — aktif hanya jika statement **Reconciled**.

Pantau bilah kuning **Reconciliation statement** (closing / adjusted / unexplained) dan banner merah **Last error** jika job gagal.

## Validasi rekonsiliasi (validator)

Pengguna dengan **`validate_bank_reconciliation`** yang **bukan** penyusun/pengaju sesi dapat:

- Melihat tab **Pending validation** di **Bank Reconciliation**.
- Melihat kartu **Bank reconciliation pending validation** di dashboard utama.
- Menerima **notifikasi** saat ada sesi yang diajukan untuk validasi.
- Di review sesi **pending validation**: **Validate** (setujui → **completed**, buka **Report**) atau **Reject** (kembali ke penyusun dengan alasan; penyusun dinotifikasi).

## Laporan rekonsiliasi dan ekspor Excel

Buka **Report** dari halaman review atau dashboard Koran. Laporan menampilkan statement formal (saldo per bank / buku, item reconciling per kategori, adjusted balances, unexplained difference, baris excluded, sign-off). Gunakan **Print**, atau **Export Excel** untuk mengunduh workbook pernyataan.

## Hak akses (permission)

| Permission | Fungsi |
|------------|--------|
| **`akses_koran`** | Akses **Rekening Koran** dan **Bank Reconciliation** (menu dan route) |
| **`upload_koran`** | Upload PDF Rekening Koran dari dashboard Koran |
| **`delete_koran`** | Hapus PDF Koran (dibatasi project; diblokir jika rekonsiliasi terkunci) |
| **`validate_bank_reconciliation`** | Menyetujui / menolak rekonsiliasi yang diajukan |

## Penanganan masalah

- **Menu tidak tampil / Access Denied** — minta administrator memberi **`akses_koran`**.
- **Banner Last error / status failed** — baca catatan (SAP account kosong, koneksi SAP, gagal parse PDF). Perbaiki penyebab, lalu jalankan ulang **Fetch SAP** atau **Re-parse**.
- **Baris SAP kosong** — periksa **`sap_account`** pada Giro, konfigurasi Service Layer, dan queue worker.
- **Tidak bisa Submit for validation** — simpan kedua **closing** balance; cocokkan atau klasifikasikan item outstanding sampai unexplained difference ≈ 0. Exclude menghapus baris dari total (gunakan hemat; lebih baik match atau Type reconciling).
- **Re-parse / Fetch menghapus match saya** — memang demikian setelah konfirmasi; aksi itu mengganti baris dan membersihkan match group terkait.
- **Tidak bisa hapus PDF** — rekonsiliasi mungkin **pending validation** atau **completed**.
- **Validator tidak menerima notifikasi** — pastikan queue worker berjalan dan notifikasi mail/database dikonfigurasi.
- **Jawaban HELP usang** — administrator menjalankan `php artisan help:reindex` setelah manual diperbarui.
