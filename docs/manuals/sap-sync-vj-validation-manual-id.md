# Validasi Verification Journal sebelum SAP Sync

Sebelum **Verification Journal (VJ)** dapat diposting ke SAP B1, harus melalui langkah **validasi**. Pengguna dengan permission **`validate_vj`** meninjau jurnal di halaman **SAP Sync** show dan mengeklik **Validate** atau **Reject**. **Submit to SAP B1** tetap nonaktif sampai status **Validated**.

Berlaku untuk VJ dari **realisasi** dan VJ **bank** (`type = bank`).

## Cara menggunakan validasi VJ

Alur dari awal sampai posting SAP:

1. **VJ dibuat** — dari **Cashier** → **Verification Journal** (alur realisasi) atau **Cashier** → **Bank Transaction** (VJ bank). Jurnal baru berstatus validasi **Pending**.
2. **Validator meninjau** — buka **Accounting** → **SAP Sync**, pilih tab project (mis. **022C**), buka jurnal dari daftar, atau gunakan penghitung di dashboard (di bawah). Di halaman show, baca kartu **Validation** dan baris jurnal.
3. **Validate atau Reject** — jika benar, klik **Validate**. Jika tidak, klik **Reject**, isi **Reason for rejection** (wajib), lalu **Confirm Reject**.
4. **Pengajuan SAP** — setelah **Validated**, pengguna yang boleh submit melihat **Submit to SAP B1** di halaman show yang sama. Jurnal **Pending** atau **Rejected** tidak dapat disubmit.
5. **Jika ditolak** — pembuat melihat banner merah **Verification Journal(s) Rejected** di bagian atas aplikasi (dengan **Review & Fix**). Mereka memperbaiki jurnal; status otomatis kembali ke **Pending** untuk validasi ulang (tanpa tombol ajukan ulang terpisah).
6. **Validasi ulang** — validator melihat jurnal lagi di penghitung dashboard **VJ pending validation** dan daftar project (badge **Pending**), lalu **Validate** atau **Reject** lagi.

**Singkat (validator):** Kartu dashboard **VJ pending validation** → **Accounting** → **SAP Sync** → daftar project → buka VJ → **Validate** → **Submit to SAP B1**.

**Singkat (pembuat setelah ditolak):** Banner penolakan → **Review & Fix** → **Edit Details** → simpan perubahan → tunggu validator.

## Siapa yang dapat membuka SAP Sync

Buka **Accounting** → **SAP Sync**. Anda perlu **`akses_sap_sync`**. Tab project (**000H**, **001H**, **022C**, dll.) menampilkan jurnal per project. Pengguna peran BO saja (**approver_bo**, **cashier_bo** tanpa **admin** / **superadmin** / **cashier** / **approver**) hanya untuk project **001H**.

Anda dapat mengetik **SAP Sync** di **Search Menu here** jika punya **`akses_sap_sync`**.

## Kartu Validation di halaman SAP Sync show

Kartu **Validation** di kanan menampilkan:

| Status | Arti |
|--------|------|
| **Pending** | Menunggu validator dengan **`validate_vj`** |
| **Validated** | Disetujui; **Submit to SAP B1** diizinkan (jika punya permission submit) |
| **Rejected** | Dikembalikan ke pembuat beserta alasan |

Saat **Rejected**, kartu menampilkan **Rejected — reason from reviewer**. Tombol **Validate** dan **Reject** hanya untuk validator selama status **Pending** dan jurnal belum diposting ke SAP.

## Penghitung dashboard untuk validator

Pengguna dengan **`validate_vj`** melihat **VJ pending validation** di dashboard utama:

- **Action Center** — kartu peringatan jika jumlah lebih dari nol (menuju dashboard **Accounting** → **SAP Sync**).
- **KPI tiles** — kartu sukses jika jumlah nol (“Nothing pending”).

Penghitung hanya mencakup jurnal berstatus **Pending**. Jurnal **Rejected** dan **Validated** tidak dihitung. Validator BO terbatas hanya menghitung jurnal project **001H**.

## Notifikasi pembuat setelah penolakan

Pembuat **tidak** perlu akses **SAP Sync** untuk mengetahui penolakan. Jika Anda membuat jurnal (`created_by`), banner merah dapat ditutup muncul di bagian atas aplikasi:

- Judul: **Verification Journal(s) Rejected**
- Menampilkan nomor jurnal, project, penolak, waktu, dan **Reason**
- **Review & Fix** membuka:
  - VJ **bank** → halaman edit **Cashier** → **Bank Transaction**
  - VJ lain → halaman **Edit Details** jurnal tersebut

Banner hilang otomatis setelah Anda menyimpan perbaikan (status kembali **Pending**).

## Memperbaiki dan mengajukan ulang VJ yang ditolak

**Tidak ada** tombol terpisah “Resubmit for validation”. Pengajuan ulang otomatis:

1. Buka **Review & Fix** dari banner penolakan (atau langsung ke **Edit Details** / edit transaksi bank).
2. Perbaiki akun, cost center, deskripsi, atau field lain yang diizinkan.
3. **Simpan** perubahan detail.

Menyimpan mengembalikan validasi ke **Pending**, menghapus alasan penolakan, dan menghilangkan banner. Penghitung **VJ pending validation** validator bertambah satu.

Selama status **Rejected**:

- **Edit Details** — hanya **pembuat** atau pengguna dengan **`edit_verification_project`** (dan belum diposting ke SAP).
- **Update SAP** dan **Cancel SAP** — nonaktif untuk semua orang sampai jurnal tidak lagi ditolak.
- **Submit to SAP B1** — tidak tersedia sampai divalidasi ulang.

### VJ bank setelah penolakan

Saat VJ bank ditolak, status transaksi bank kembali ke **draft**. Pembuat mengedit di **Cashier** → **Bank Transaction**, lalu **Submit** lagi. Itu juga mengatur validasi kembali ke **Pending** dan menghapus alasan penolakan.

## Daftar project SAP Sync

Setiap tab project menampilkan kolom **validation_status**:

- **Pending** (badge kuning)
- **Validated** (hijau)
- **Rejected** (merah)

Kotak centang bulk submit SAP hanya untuk jurnal **Validated** yang belum diposting.

## Permission

| Permission / peran | Fungsi |
|--------------------|--------|
| **`akses_sap_sync`** | Membuka menu dan daftar **Accounting** → **SAP Sync** |
| **`validate_vj`** | **Validate** / **Reject** di halaman SAP Sync show; melihat penghitung dashboard **VJ pending validation** |
| **`edit_verification_project`** | **Edit Details** pada jurnal yang bukan milik Anda (editor per project) |
| **Pembuat** (`created_by`) | **Edit Details** pada jurnal sendiri (jika belum diposting) |
| **superadmin**, **admin**, **cashier** | **Update SAP** / **Cancel SAP** di halaman show (nonaktif saat **Rejected**) |
| **superadmin**, **admin**, **cashier**, **cashier_bo** | Mengubah **Project** pada baris detail di **Edit Details** |

Default: **`validate_vj`** di-seed untuk **superadmin** dan **admin**; administrator dapat memberikannya ke peran lain di manajemen **Role** bagian **SAP Integration**.

## Pemecahan masalah

- **Tidak melihat Validate / Reject** — perlu **`validate_vj`**, jurnal harus **Pending**, dan belum punya nomor jurnal SAP.
- **Submit to SAP B1 tidak ada** — jurnal harus **Validated** dulu; periksa kartu **Validation**.
- **Pembuat tidak melihat penolakan** — hanya pembuat jurnal yang melihat banner; muat ulang halaman setelah penolakan.
- **Ditolak tetapi Edit Details tidak ada** — harus pembuat atau punya **`edit_verification_project`**; jurnal yang sudah diposting tidak dapat diedit.
- **Penghitung VJ pending validation salah** — jurnal **Rejected** tidak dihitung; hanya **Pending**. Validator BO hanya melihat **001H**.
- **Jawaban HELP usang** — administrator menjalankan `php artisan help:reindex` setelah pembaruan manual.
