# Administrasi Bilyet & BAPSB (Bilyet Giro, Cek, Letter of Authority)

Modul **Administrasi Bilyet** mencatat surat berharga bank yang dipegang unit — **Bilyet Giro (BG)**, **Cek (Check)**, dan **Letter of Authority (LOA)** — dari penerimaan fisik, penyerahan (**release**), pencairan (**cair**), sampai pembatalan (**void**), lengkap dengan jejak audit. Fitur **BAPSB (Berita Acara Pemeriksaan Surat Berharga Bank)** dijalankan setiap bulan per project: memeriksa fisik surat berharga terhadap data sistem, mencetak berita acara untuk ditandatangani, mengunggah scan PDF, lalu meminta validasi tim **Accounting HO**.

## Daftar Isi

- [Pengantar: Administrasi Bilyet dan BAPSB](#pengantar-administrasi-bilyet-dan-bapsb)
- [Siapa pengguna modul ini](#siapa-pengguna-modul-ini)
- [Memulai: menu, hak akses, dan alur ringkas](#memulai-menu-hak-akses-dan-alur-ringkas)
- [Mendaftarkan Bilyet Baru](#mendaftarkan-bilyet-baru)
- [Import Bilyet Massal dari Excel](#import-bilyet-massal-dari-excel)
- [Status bilyet dan aksi yang tersedia](#status-bilyet-dan-aksi-yang-tersedia)
- [Pencairan dan Penyerahan Bilyet](#pencairan-dan-penyerahan-bilyet)
- [Daftar Bilyet, Filter, dan Dashboard](#daftar-bilyet-filter-dan-dashboard)
- [Audit Trail dan Riwayat Bilyet](#audit-trail-dan-riwayat-bilyet)
- [Mengisi Cheque / Bilyet di Bank Transaction](#mengisi-cheque--bilyet-di-bank-transaction)
- [Membuat BAPSB Bulanan](#membuat-bapsb-bulanan)
- [Cetak BAPSB dan Blok Tanda Tangan](#cetak-bapsb-dan-blok-tanda-tangan)
- [Unggah PDF yang Ditandatangani dan Submit BAPSB](#unggah-pdf-yang-ditandatangani-dan-submit-bapsb)
- [Validasi BAPSB oleh Accounting HO](#validasi-bapsb-oleh-accounting-ho)
- [Kepatuhan, Batas Waktu, dan Daftar Tunggakan](#kepatuhan-batas-waktu-dan-daftar-tunggakan)
- [Referensi Permission](#referensi-permission)
- [Tugas yang Sering Dilakukan](#tugas-yang-sering-dilakukan)
- [Troubleshooting Dropdown Cek Kosong dan Masalah Lain](#troubleshooting-dropdown-cek-kosong-dan-masalah-lain)
- [Quick Reference: Status, URL, dan Glosarium](#quick-reference-status-url-dan-glosarium)

## Pengantar: Administrasi Bilyet dan BAPSB

Dua hal yang diatur modul ini:

- **Administrasi Bilyet** — master data surat berharga bank per rekening giro: nomor, jenis, tanggal bilyet, tanggal cair, nominal, dan status. Semua laporan dan pemeriksaan fisik mengambil data dari sini.
- **BAPSB** — dokumen pemeriksaan bulanan per project yang memotret status fisik bilyet pada akhir periode, memuat hasil pemeriksaan per baris (fisik ada/tidak ada, lokasi penyimpanan), ringkasan per jenis, dan mutasi bulan itu. Satu berita acara untuk satu project dan satu bulan.

Bila Anda baru pertama kali memakai modul ini, mulai dari **Mendaftarkan Bilyet Baru** dan **Import Bilyet Massal dari Excel**; BAPSB tidak akan bisa dibuat untuk sebuah rekening sebelum bilyetnya terdaftar.

## Siapa pengguna modul ini

| Pengguna | Peran pada modul ini |
|----------|----------------------|
| **Kasir / accounting site** (`000H`, `021C`, `022C`, `025C`) | Mendaftarkan bilyet, mengimpor dari Excel, menyerahkan/mencairkan/membatalkan bilyet, menyusun dan mengirim BAPSB unitnya |
| **Accounting HO** (validator) | Memvalidasi BAPSB yang sudah dikirim, memantau daftar tunggakan seluruh unit |
| **BO** (`001H`) | Sama seperti unit lain: mengurus bilyet dan BAPSB rekening BO |
| **Admin / superadmin** | Memberi hak akses, mengubah penanda rekening, memperbaiki data bilyet lewat halaman edit khusus superadmin |
| **Auditor / kontrol internal** | Membaca **Audit Trail**, **Riwayat** per bilyet, dan laporan di tab **Reports** |

Rekening **tabungan** tidak menerbitkan surat berharga, karena itu tidak perlu BAPSB (lihat **Kepatuhan, Batas Waktu, dan Daftar Tunggakan**).

## Memulai: menu, hak akses, dan alur ringkas

**Menu:**

- **Cashier → Administrasi Bilyet** (butuh permission **`akses_bilyet`**) — membuka tab **Dashboard**.
- **Cashier → BAPSB** (butuh permission **`akses_bapsb`**) — daftar berita acara per unit.
- Master rekening **Giro** ada di **Accounting → Giro** (butuh **`akses_giro`**).

**Tab di dalam Administrasi Bilyet** (kartu navigasi di atas halaman): **Dashboard** | **List** | **Upload** | **Audit Trail** | **Reports**.

**Alur ringkas pengelolaan bilyet:**

1. Terima fisik Bilyet Giro / Cek / LOA dari penerbit atau pihak internal.
2. Daftarkan **satu per satu** lewat tombol **+ Bilyet** di **List**, atau **massal** lewat **Upload** (template Excel).
3. Bilyet berstatus **onhand** sampai Anda **release** (menyerahkan ke penerima); setelah dana masuk, tandai **cair**; bila dibatalkan, tandai **void**.
4. Setiap perubahan tercatat otomatis di **Audit Trail** dan di **Riwayat** bilyet tersebut.
5. Sebelum dipakai sebagai rujukan pembayaran, bilyet harus terdaftar pada rekening yang benar agar muncul di dropdown **Cheque / Bilyet** pada **Bank Transactions**.
6. Setiap bulan, susun **BAPSB** untuk project Anda, cetak, tanda tangani, unggah PDF hasil scan, lalu **Submit for validation**.

## Mendaftarkan Bilyet Baru

1. Buka **Cashier → Administrasi Bilyet**, lalu klik tab **List**.
2. Klik tombol **+ Bilyet** di kanan atas kartu **Bilyet List** (tombol ini butuh permission **`add_bilyet`**).
3. Isi modal **New Bilyet** (lihat tabel field di bawah), lalu klik **Save**.
4. Setelah tersimpan, tabel **Bilyet List** menampilkan pesan sukses. Data baru muncul saat filter dijalankan (lihat **Daftar Bilyet, Filter, dan Dashboard**).

Sistem menolak nomor ganda: bila kombinasi **Prefix + Bilyet No** sudah ada, muncul pesan *Bilyet number already exists*.

### Field pada form New Bilyet

| Field | Wajib | Keterangan |
|-------|-------|------------|
| **Prefix** | Ya | Awalan nomor bilyet (maks. 10 karakter), mis. `GH`. |
| **Bilyet No** | Ya | Nomor bilyet tanpa prefix (maks. 30 karakter). |
| **Bilyet Type** | Ya | Pilihan pada form: **Cek**, **BG**, **LOA**. Di tabel dan halaman detail, jenis ditampilkan sebagai **CEK** (Check), **BILYET** (Bilyet Giro), atau **LOA** (Letter of Authority). |
| **Giro** | Ya | Rekening giro pemilik bilyet (daftar dari master **Giro**: `acc_no - acc_name`). |
| **Bilyet Date** | Tidak | Tanggal yang tercetak pada bilyet. |
| **Cair Date** | Tidak | Tanggal pencairan/pelunasan (harus sama atau setelah Bilyet Date). |
| **Remarks** | Tidak | Catatan bebas (maks. 500 karakter). |
| **Amount** | Tidak | Nominal bilyet (IDR, tidak boleh negatif). |
| **Upload bilyet** | Tidak | Lampiran opsional: PDF, JPG/JPEG, atau PNG, maksimal **2 MB**. |

### Status ditentukan otomatis saat penyimpanan

Form **New Bilyet** tidak meminta status. Status dihitung dari kelengkapan data:

| Isi form | Status hasil |
|----------|--------------|
| Amount + Bilyet Date + Cair Date terisi | **cair** |
| Amount **atau** Bilyet Date terisi | **release** |
| Semuanya kosong | **onhand** |

Ingin mengisi status lain? Halaman **edit** khusus **superadmin** menyediakan pilihan status lengkap (**On Hand**, **Release**, **Cair**, **Void**) dan memvalidasi perpindahannya.

### Update Many — ubah beberapa bilyet sekaligus

Tombol **Update Many** (permission **`add_bilyet`**) membuka modal untuk memperbarui banyak bilyet sekaligus:

- **Bilyet Date**, **Purpose** (catatan), dan **Amount** yang akan diterapkan.
- **Select Bilyets (Onhand only)** — hanya bilyet berstatus **onhand** yang bisa dipilih.
- Klik **Save**; muncul pesan *Successfully updated N bilyets.* dan setiap perubahan tercatat sebagai **Bulk Updated** di audit trail.

## Import Bilyet Massal dari Excel

Dipakai saat Anda menerima daftar bilyet dalam jumlah besar (puluhan/ ratusan baris) dari unit atau bank.

1. Buka **Cashier → Administrasi Bilyet** → tab **Upload** (kartu **Upload Bilyets**).
2. Klik **download template** (tombol hijau) — berkas `bilyet_template.xlsx`.
3. Isi template. Kolom yang dikenali sistem, sesuai berkas template:

| Kolom | Isi |
|-------|-----|
| `acc_no` | Nomor rekening giro (harus sudah ada di master **Giro**) |
| `prefix` | Awalan nomor bilyet (wajib) |
| `nomor` | Nomor bilyet (wajib) |
| `type` | Jenis: `cek`, `bilyet`, atau `loa` |
| `bilyet_date` | Tanggal bilyet |
| `cair_date` | Tanggal cair (kosongkan bila belum cair) |
| `amount` | Nominal |
| `remarks` | Catatan |

4. Simpan berkas, lalu klik **Upload** di halaman **Upload Bilyets** dan pilih berkas: format **.xls atau .xlsx**, ukuran maksimal **10 MB** (berkas yang lebih besar atau bukan Excel langsung ditolak dengan peringatan).
5. Baris valid masuk ke **tabel staging** (`bilyet-temps`) — bukan langsung ke master bilyet. Periksa daftarnya di tabel **Upload Bilyets** (kolom **Nomor**, **status**, **Giro Acc**, **Type**, **BilyetD**, **CairD**, **Amount**, **Loan**). Baris yang rekeningnya tidak dikenali akan muncul sebagai masalah; gunakan tombol **Empty Table** untuk membersihkan staging bila perlu.
6. Klik **Import** (tombol oranye) → modal **Import Bilyets to DB** → isi **Receive Date** (tanggal penerimaan fisik, wajib) → klik **Import**.
7. Tombol **Import** nonaktif selama masih ada baris tanpa rekening giro yang sah atau ada nomor duplikat. Setelah impor sukses muncul pesan *Import completed successfully! N records imported*. Baris tanpa rekening yang sah dilewati, dan tabel staging dibersihkan otomatis.

**Catatan:** status setiap baris hasil impor dihitung dengan aturan yang sama seperti pendaftaran manual (lihat **Status ditentukan otomatis saat penyimpanan**).

Bila impor selesai tetapi tidak ada baris yang masuk, penyebab tersering adalah **nomor rekening di file Excel tidak ada di sistem**. Perbaiki `acc_no`, ulangi Upload, lalu Import.

## Status bilyet dan aksi yang tersedia

| Status | Arti | Aksi yang tersedia |
|--------|------|--------------------|
| **onhand** | Bilyet fisik ada di unit, belum diserahkan | tombol edit/release, **Void**, **View Details**, **Riwayat**; **hapus** (permission `delete_bilyet`) |
| **release** | Bilyet sudah diserahkan ke penerima, belum dicairkan | tombol **Cairkan**, **Void**, **View Details**, **Riwayat** |
| **cair** | Sudah dicairkan bank | **View Details**, **Riwayat** (tidak dapat diubah lagi) |
| **void** | Dibatalkan; catatan, nominal, dan tanggal tetap tersimpan | **View Details**, **Riwayat** |

Aturan perpindahan status yang diberlakukan sistem:

| Dari | Boleh pindah ke |
|------|-----------------|
| **onhand** | **release**, **void** |
| **release** | **cair**, **void** |
| **cair** | — (final) |
| **void** | — (final) |

Percobaan perpindahan yang tidak sah ditolak dengan pesan *Invalid status transition from … to …*. **Superadmin** dapat memaksa perpindahan tersebut lewat halaman edit superadmin, dengan syarat mengisi alasan pada kolom remarks (minimal 10 karakter); alasan itu otomatis dicatat di dalam catatan bilyet sebagai *SUPERADMIN OVERRIDE*.

## Pencairan dan Penyerahan Bilyet

Semua aksi dijalankan dari tombol ikon pada kolom **Action** di tabel **Bilyet List** (permission **`akses_bilyet`**). Bilyet project lain hanya tampil untuk **admin/superadmin**.

### Penyerahan (release)

1. Pada bilyet berstatus **onhand**, klik ikon pensil (**Edit/Release**).
2. Modal **Release {Type} no {Nomor}** terbuka. Isi **Bilyet Date**, **Cair Date (Optional)**, **Amount**, dan **Purpose** (catatan) — data yang sudah ada akan tampil sebagai nilai awal.
3. Klik **Update**. Setelah tanggal/nominal terisi, status berpindah ke **release**.

### Pencairan (cair)

1. Pada bilyet berstatus **release**, klik ikon uang (**Cairkan**).
2. Modal **Cairkan {Type} no {Nomor}** terbuka: **Bilyet Date**, **Amount**, dan **Purpose** ditampilkan hanya-baca; **Cair Date** wajib diisi.
3. Bila bilyet ini untuk pembayaran angsuran (`purpose` = loan payment dan terhubung ke installment), muncul opsi **Create SAP Outgoing Payment** — bila dicentang, sistem membuat **Outgoing Payment** di SAP B1 untuk AP Invoice angsuran terkait.
4. Klik **Cairkan** → status menjadi **cair**.

### Pembatalan (void)

1. Pada bilyet berstatus **onhand** atau **release**, klik ikon larangan (**Void**).
2. Modal konfirmasi menjelaskan: *Status will be changed to VOID* dan data lain (remarks, amount, tanggal) tidak berubah.
3. Klik **Void**.

### Halaman daftar khusus

Daftar khusus per tahapan juga tersedia pada rute berikut (dibuka langsung lewat alamat, tanpa tab menu):

| Halaman | Alamat | Kolom khusus |
|---------|--------|--------------|
| **Cair** | `/cashier/bilyets/cair` | kolom **CairD** |
| **Release** | `/cashier/bilyets/release` | kolom **CairD** |
| **Void** | `/cashier/bilyets/void` | kolom **VoidD** |

Pada tiap baris tersedia tombol **edit** → modal **Edit Data for {Type} no {Nomor}**: **Bilyet Date**, **Cair Date**, **Purpose**, **Amount**, dan pilihan **Is VOID?** (**NO**/**YES**). Pilih **YES** untuk menandai bilyet sebagai **void**.

## Daftar Bilyet, Filter, dan Dashboard

### Tab Dashboard

Menampilkan ringkasan posisi bilyet per rekening bank (tabel **Bank Account** × kolom **Cek**, **BG**, **LoA**, **Debit**, **Total**, **Amount**) dalam empat kartu: **Onhand**, **Release**, **Due This Month**, dan **Void**. Pakai tab ini untuk melihat posisi surat berharga yang masih dipegang unit.

### Tab List

Kartu **Bilyet List** berisi tabel dengan kolom: **#**, **Nomor**, **Bank | Account**, **Type**, **BilyetD**, **CairD**, **Status**, **IDR**, kolom centang, dan **Action**.

Penting: tabel **tidak** memuat data sebelum filter dijalankan — halaman menampilkan petunjuk **Gunakan Filter** di atas. Isi filter lalu klik **Filter**:

| Filter | Isi |
|--------|-----|
| **Status** | Semua Status, **onhand**, **release**, **cair**, **void** |
| **Bank Account** | Salah satu giro |
| **Nomor Bilyet** | Pencarian nomor |
| **Tanggal Dari** / **Tanggal Sampai** | Rentang tanggal |
| **Amount From** / **Amount To** | Rentang nominal |

Klik **Reset** untuk mengosongkan filter.

Centang baris (atau centang semua di kepala tabel) untuk membuka panel **Selected Summary**: **Selected Items**, **Total Amount**, **Average**, dan **Status Mix**. Pintasan papan tuntas: **Ctrl+A** memilih semua, **Esc** membersihkan pilihan. Tombol **Update Many** di kartu ini otomatis memuat bilyet **onhand** yang Anda centang.

Tombol ikon pada kolom **Action**: edit/release (pensil), **Cairkan** (uang), **Void** (larangan), **View Details** (mata), **Riwayat** (jam), **hapus** (tempat sampah, hanya status **onhand** dan dengan `delete_bilyet`), serta **Superadmin Edit** (roda gigi) untuk pengguna ber-role **superadmin**.

### Tab Reports

Kartu **Bilyet Reports & Analytics** memuat filter **Date From**, **Date To**, dan **Project**, dengan tombol **Load Report** (memuat metrik: jumlah bilyet, distribusi status/jenis, tren bulanan, distribusi bank, pengguna paling aktif) dan **Export** untuk mengunduh data. Data report diambil dari endpoint report modul bilyet.

## Audit Trail dan Riwayat Bilyet

### Audit Trail (semua bilyet)

Buka tab **Audit Trail** dari kartu navigasi. Filter yang tersedia: **Action** (**All Actions**, **Created**, **Updated**, **Status Changed**, **Voided**, **Bulk Updated**), **Date From**, dan **Date To**. Kolom tabel: **Date & Time**, **Action**, **Bilyet**, **User**, **Changes**, **IP Address**, **Actions**.

### Riwayat per bilyet

Klik ikon **Riwayat** pada baris bilyet (atau tautan **View** dari halaman BAPSB) untuk membuka halaman riwayat: identitas bilyet, status, jenis, dan **timeline** kronologis semua aktivitas beserta pengguna dan alamat IP-nya.

## Mengisi Cheque / Bilyet di Bank Transaction

Field **Cheque / Bilyet** pada **Cashier → Bank Transactions** (form **Create** atau **Edit**) menghubungkan transaksi bank dengan bilyet yang menjadi sumbernya.

1. Buka **Cashier → Bank Transactions → Create**.
2. Pilih **Bank Account** lebih dulu.
3. Dropdown **Cheque / Bilyet** otomatis dimuat untuk rekening tersebut. Pilihan bawaan **— none —** berarti tidak ada bilyet yang dikaitkan.
4. Pilih bilyet. Label pilihan berisi nomor (`prefix` + `nomor`), tanggal bilyet, nominal, status, dan project, mis. `GH123456 · 2026-09-19 · 1.500.000 · release · 025C`.
5. Simpan transaksi.

Ketentuan yang perlu diketahui:

- Daftar pilihan **hanya** memuat bilyet dari rekening bank yang dipilih, yaitu bilyet dengan `bilyets.giro_id` menunjuk ke giro yang kolom **`sap_account`**-nya sama dengan **Bank Account** transaksi.
- Field ini **hanya rujukan aplikasi**. Nomor bilyet **tidak dikirim ke SAP** dan **tidak mengubah status, nominal, atau tanggal bilyet**.
- Bila bilyet yang dikirim tidak cocok dengan rekening, sistem menolak dengan pesan *The selected cheque/bilyet does not belong to the selected bank account.* (atau *… is not linked to a valid bank giro account.* bila giro bilyet tidak terhubung).
- Halaman **detail** transaksi menampilkan baris **Cheque / Bilyet** berisi nomor bilyet, tanggal, nominal, dan status, dengan tautan ke riwayat bilyet tersebut.

Bila dropdown kosong, lihat **Troubleshooting Dropdown Cek Kosong dan Masalah Lain**.

## Membuat BAPSB Bulanan

**BAPSB** adalah berita acara pemeriksaan fisik surat berharga bank: **satu dokumen per project per bulan**. Diperlukan permission **`akses_bapsb`**.

1. Buka **Cashier → BAPSB** (kartu **Bank Securities Inspection Report (BAPSB)**).
2. Klik **+ BAPSB**. Halaman **Create BAPSB** terbuka dengan kartu **Header**.
3. Pilih **Period** (daftar 18 bulan terakhir) dan **Project**. Daftar project yang bisa dipilih hanya project yang punya rekening giro dengan penanda `needs_bilyet`. Mengubah Period atau Project memuat ulang daftar bilyet.
4. Isi **Report date** (tanggal berita acara, default hari ini), **Checked by 1**, **Checked by 2** (keduanya wajib — nama pemeriksa), dan **Approved by (optional)**. Kolom **Approved by** memang opsional: unit site boleh mengosongkannya karena validasi akhir dilakukan Accounting HO.
5. Periksa daftar bilyet yang muncul otomatis (lihat bagian berikut), isi hasil pemeriksaan fisiknya per baris.
6. Periksa kartu **Summary**: jumlah dan total nominal per jenis — **Bilyet Giro**, **Checks**, **LOA** — serta **Mutations this period** (**Settled** = berapa bilyet cair bulan itu, **Voided** = berapa bilyet dibatalkan bulan itu).
7. Klik **Save draft**. Nomor berita acara dibuat otomatis dengan pola **BAPSB-0001/{project}/{MM-YYYY}**, mis. `BAPSB-0001/025C/09-2026`. Status validasi awal: **pending**.

Bila daftar kosong, halaman menampilkan peringatan *No on-hand / released bilyets for this period on giro accounts. Register bilyets first or choose another period.* dan tombol **Save draft** dinonaktifkan. Sistem juga menolak berita acara ganda untuk project dan period yang sama (*BAPSB for this project and period already exists.*) dan memastikan daftar baris masih sama dengan kondisi master saat penyimpanan (*Bilyet list does not match the selected period. Refresh and try again.*).

### Bilyet mana yang masuk daftar BAPSB

Daftar ditarik otomatis dari master bilyet (tidak diketik ulang), dengan kriteria:

- project sesuai pilihan;
- status **onhand** atau **release** (fisik seharusnya masih ada);
- **bilyet_date** pada atau sebelum akhir periode;
- **cair_date** kosong atau setelah akhir periode;
- hanya rekening dengan penanda **`needs_bilyet`** (default: rekening jenis **giro** = perlu, rekening jenis **tabungan** = tidak perlu);
- jenis **debit** dikecualikan.

Baris dikelompokkan per rekening bank; judul kelompok memakai format `sap_account — acc_no (acc_name)` bila kolom `sap_account` giro terisi.

### Mengisi hasil pemeriksaan per baris

Setiap baris bilyet menampilkan **Type**, **Number**, **Date**, **Amount**, dan **Status** (dari master, tidak dapat diubah di sini). Yang Anda isi:

| Kolom | Isi |
|-------|-----|
| **Physical** | Radio **Present** (fisik ada) atau **Missing** (fisik tidak ada) — wajib dipilih |
| **Location** | Lokasi penyimpanan: **Brankas Site**, **Lemari Besi Accounting HO**, **Brankas BO**, **Safe Deposit Box Bank**, atau **Lainnya** — wajib |
| **Note** | Catatan khusus lokasi, mis. nomor brankas atau keterangan bila memilih **Lainnya** |
| **Remarks** | Keterangan selisih / temuan pemeriksaan |

Setelah tersimpan, hasil pemeriksaan masih bisa diperbaiki: dari halaman detail klik **Edit** selagi berita acara belum di-submit. Halaman **edit** memakai kartu Header dan Summary yang sama dan menghitung ulang ringkasan dari kondisi master terbaru.

## Cetak BAPSB dan Blok Tanda Tangan

1. Dari halaman detail atau daftar, klik **Print**. Halaman cetak terbuka di tab baru dan dialog cetak browser otomatis muncul.
2. Halaman cetak memuat judul **BERITA ACARA PEMERIKSAAN SURAT BERHARGA BANK**, nomor berita acara, **Project**, **Period**, dan **Date**, diikuti tabel berisi **#**, **Type**, **Number**, **Bank account**, **Date**, **Amount**, **Physical** (**Ada**/**Tidak ada**), dan **Location**.
3. Di bawahnya tercetak tabel **Summary** (Bilyet Giro, Checks, LOA, Settled, Voided).
4. Blok tanda tangan berisi empat kolom:

| Kolom cetak | Isi |
|-------------|-----|
| **Prepared by** | Nama penyusun (otomatis dari pengguna yang menyimpan draft) |
| **Checked by 1** | Nilai kolom **Checked by 1** yang Anda isi di form |
| **Checked by 2** | Nilai kolom **Checked by 2** yang Anda isi di form |
| **Approved by** | Nilai kolom **Approved by**; bila dikosongkan tercetak `—` |

5. Cetak dokumen, minta tanda tangan sesuai kolom, lalu pindai hasilnya menjadi **PDF**.

## Unggah PDF yang Ditandatangani dan Submit BAPSB

1. Buka detail berita acara, lalu pada kartu **Upload signed PDF** pilih berkas scan hasil tanda tangan.
2. Klik **Upload PDF**. Batasan berkas: hanya **PDF**, maksimal **5 MB**. Berkas tersimpan sebagai dokumen dengan jenis **`bapsb`** pada project tersebut dan status validasi **pending**. Mengunggah ulang akan menggantikan PDF sebelumnya.
3. Klik **Submit for validation**. Tombol ini baru aktif setelah PDF terunggah; bila belum, muncul pesan *Upload the signed PDF before submitting.* Setelah submit, muncul pesan *BAPSB submitted for HO validation.* dan waktu submit tercatat.
4. Setelah di-submit, berita acara terkunci: tombol **Edit** hilang (pesan *Submitted BAPSB cannot be edited.*) dan PDF tidak bisa diganti (*Cannot replace PDF after submission.*). Jika ada kesalahan, koordinasikan dengan administrator (tidak ada aksi batal/buka-kunci di antarmuka).

## Validasi BAPSB oleh Accounting HO

Validasi dilakukan pengguna dengan permission **`validate_bapsb_report`** (lihat **Referensi Permission**).

1. Ketika ada berita acara yang dikirim, kartu **BAPSB pending validation** muncul di dashboard (jumlah = berita acara yang sudah di-submit dan masih **pending**); klik kartu itu untuk membuka daftar tunggakan.
2. Alternatif: buka **Cashier → BAPSB → /cashier/bapsb/outstanding** (`Late submissions by unit` dan `Pending HO validation`).
3. Buka berita acara dari kolom **Action** pada tabel **Pending HO validation** (tombol **View**).
4. Di kartu **HO validation** pada halaman detail: isi **Note (optional)** lalu klik **Validate BAPSB**.
5. Status berita acara berubah dari **pending** menjadi **validated**, dan PDF-nya ikut ditandai tervalidasi berikut nama validator serta tanggal-waktunya.

Catatan implementasi saat ini: yang tersedia hanya aksi **Validate**. Bila berita acara perlu dikembalikan ke unit, gunakan catatan pada kolom **Note** dan komunikasi di luar aplikasi — tidak ada tombol tolak/buka kunci pada antarmuka.

## Kepatuhan, Batas Waktu, dan Daftar Tunggakan

- **Batas submit:** tanggal **5 bulan berikutnya** (akhir hari) untuk periode bulan sebelumnya.
- **Peringatan unit:** bila periode bulan lalu belum di-submit setelah batas waktu, pengguna dengan **`akses_bapsb`** pada unit itu melihat banner peringatan **BAPSB submission overdue** berisi periode dan tanggal jatuh tempo, dengan tautan **Create BAPSB**.
- **Daftar tunggakan terpusat:** halaman **/cashier/bapsb/outstanding** (permission **`validate_bapsb_report`**) menampilkan kartu **Late submissions by unit** dengan kolom **Project**, **Period**, **Deadline** untuk 12 bulan terakhir sampai bulan berjalan, plus kartu **Pending HO validation** (kolom **Number**, **Project**, **Submitted**).
- **Tidak memblokir:** keterlambatan BAPSB hanya menghasilkan peringatan dan daftar tunggakan. Transaksi kasir tetap berjalan normal (berbeda dari PCBC).
- **Tidak berlaku untuk rekening tabungan:** ketentuan ini hanya untuk project yang punya rekening giro dengan penanda `needs_bilyet`.
- **Belum tersedia:** rekap kepatuhan antar bulan (unit mana telat dan berapa kali) masih direncanakan.

## Referensi Permission

| Permission | Fungsi |
|------------|--------|
| **`akses_bilyet`** | Menu **Cashier → Administrasi Bilyet** dan seluruh halaman bilyet (Dashboard, List, Upload, Audit Trail, Reports) |
| **`add_bilyet`** | Tombol **+ Bilyet** dan **Update Many**; izin membuat bilyet baru |
| **`delete_bilyet`** | Tombol hapus pada bilyet berstatus **onhand** |
| **`akses_bapsb`** | Menu **Cashier → BAPSB** dan seluruh halaman BAPSB unit |
| **`validate_bapsb_report`** | Kartu **HO validation** (aksi **Validate BAPSB**), halaman tunggakan **/cashier/bapsb/outstanding**, dan kartu dashboard **BAPSB pending validation** |
| **`akses_giro`** | Master rekening **Accounting → Giro** (sumber daftar rekening dan kolom `sap_account`) |
| **`akses_cashier_modal`**, dll. | Tidak berkaitan dengan bilyet; hanya disebut agar tidak tertukar |

Pemberian bawaan dari seeder **BAPSB**: permission **`akses_bapsb`** diberikan ke role **superadmin**, **admin**, **cashier**, dan **head_cashier**; permission **`validate_bapsb_report`** diberikan ke role **head_cashier**, role **admin**/**superadmin**, serta pengguna Accounting HO yang ditunjuk pada seeder tersebut.

Halaman **edit** bilyet (semua field) hanya untuk pengguna ber-role **superadmin**.

## Tugas yang Sering Dilakukan

**1. Mendaftarkan satu bilyet baru dari surat fisik**
**Cashier → Administrasi Bilyet → List → + Bilyet** → isi Prefix, Bilyet No, Bilyet Type, Giro, Bilyet Date, Amount → **Save** → klik **Filter** di kartu **Bilyet List** untuk memastikan data muncul.

**2. Mengimpor ratusan bilyet dari daftar bank**
**Administrasi Bilyet → Upload → download template** → isi kolom `acc_no`/`prefix`/`nomor`/`type`/`bilyet_date`/`cair_date`/`amount`/`remarks` → **Upload** → periksa staging → **Import** → isi **Receive Date** → **Import**.

**3. Menandai bilyet sudah dicairkan**
**List** → **Filter** status **release** → ikon uang (**Cairkan**) → isi **Cair Date** (wajib) → centang **Create SAP Outgoing Payment** bila ini pembayaran angsuran yang perlu diposting ke SAP → **Cairkan**.

**4. Menghubungkan cek ke transaksi bank**
**Cashier → Bank Transactions → Create** → pilih **Bank Account** → pilih bilyet pada **Cheque / Bilyet** (opsional) → simpan. Cek yang tidak muncul di daftar berarti belum terdaftar pada rekening itu atau `sap_account` rekeningnya belum diisi.

**5. Mendaftarkan bilyet yang sudah dicairkan tanpa melewati release**
Isi **Amount**, **Bilyet Date**, dan **Cair Date** sekaligus pada form **New Bilyet** → status langsung menjadi **cair**.

**6. Menyiapkan BAPSB bulan lalu**
**Cashier → BAPSB → + BAPSB** → pilih **Period** dan **Project** → isi **Report date**, **Checked by 1**, **Checked by 2** → isi **Physical**, **Location**, **Note**, **Remarks** per baris → cek **Summary** → **Save draft** → **Print** → tanda tangan → unggah PDF → **Submit for validation**.

**7. Memantau tunggakan BAPSB seluruh unit (HO)**
Dashboard → kartu **BAPSB pending validation**, atau **/cashier/bapsb/outstanding** → kartu **Late submissions by unit** (project, period, deadline) dan **Pending HO validation** → **View** → **Validate BAPSB**.

**8. Menelusuri siapa yang mengubah sebuah bilyet**
**Administrasi Bilyet → List** → ikon **Riwayat** pada baris bilyet (timeline lengkap per bilyet), atau tab **Audit Trail** dan filter **Action**/**Date From**/**Date To** untuk seluruh bilyet.

## Troubleshooting Dropdown Cek Kosong dan Masalah Lain

### 1. Dropdown Cheque / Bilyet di Bank Transaction kosong

Ini kasus paling sering. Dropdown hanya berisi bilyet yang memenuhi **dua** syarat:

1. **Bilyet sudah terdaftar** untuk rekening tersebut (ada bilyet dengan `giro_id` = giro yang dipilih) di **Administrasi Bilyet**; dan
2. **Kolom `sap_account` pada master Giro rekening itu sudah diisi** (`Accounting → Giro`).

Bila salah satu belum terpenuhi, daftar kosong tanpa pesan kesalahan. Langkah perbaikan:

- Pastikan **Bank Account** transaksi sudah dipilih — sebelum itu dropdown memang hanya berisi **— none —**.
- Periksa listing bilyet untuk rekening tersebut (**List** → filter **Bank Account**). Bila kosong, daftarkan bilyet atau impor massal dari Excel.
- Periksa master **Giro**: kolom `sap_account` harus terisi dan **sama persis** dengan **Bank Account** yang dipakai transaksi bank. Bila kosong, hubungi Accounting HO/administrator untuk melengkapinya. Rekening giro yang pernah dilengkapi: `017C` → `11201028`, `022C` → `11201006`; project nonaktif (mis. `023C`) belum dilengkapi.
- Bila dropdown terisi lalu hilang, periksa apakah **Bank Account** diubah setelah memilih bilyet — daftar dimuat ulang mengikuti rekening yang aktif. Kegagalan memuat data juga memunculkan notifikasi *Failed to load cheque/bilyet options*.
- Saat menyimpan, pesan *The selected cheque/bilyet does not belong to the selected bank account.* berarti bilyet dan rekening tidak sepasang: pilih bilyet lain dari rekening itu.

### 2. Menu Administrasi Bilyet atau BAPSB tidak muncul / Access Denied

Hak akses belum diberikan. Minta administrator memberi **`akses_bilyet`** (menu Administrasi Bilyet) atau **`akses_bapsb`** (menu BAPSB). Validasi BAPSB dan halaman tunggakan memerlukan **`validate_bapsb_report`** terpisah.

### 3. Tombol + Bilyet atau Update Many tidak muncul

Kedua tombol butuh **`add_bilyet`**. Bila **+ Bilyet** ada tetapi **Action** pada baris kosong, penyebabnya baris tersebut milik project lain — baris project lain hanya tampil untuk **admin/superadmin**.

### 4. Tabel Bilyet List kosong padahal data ada

Tabel hanya memuat data setelah filter dijalankan: isi minimal satu filter (mis. **Status**) lalu klik **Filter**. Tanpa filter, sistem sengaja mengembalikan hasil kosong dan menampilkan petunjuk **Gunakan Filter**.

### 5. Bilyet number already exists

Kombinasi **Prefix + Bilyet No** sudah terpakai. Periksa lewat filter **Nomor Bilyet**; perbaiki prefix/nomor bila memang ganda, atau perbarui bilyet yang sudah ada.

### 6. Invalid status transition from …

Bilyet berstatus **cair** atau **void** tidak bisa diubah lagi, dan perpindahan harus mengikuti aturan di **Status bilyet dan aksi yang tersedia**. Perbaikan data untuk kasus ini hanya bisa dilakukan **superadmin** lewat halaman edit superadmin, dengan alasan minimal 10 karakter pada remarks.

### 7. Impor Excel tidak memproses apa pun

- Pastikan format **.xls/.xlsx** dan ukuran ≤ **10 MB**.
- **Import** nonaktif bila ada baris tanpa rekening giro yang sah atau nomor duplikat: bersihkan staging (**Empty Table**) lalu unggah ulang setelah memperbaiki `acc_no`.
- Pesan *Import completed but no records were imported…* berarti kolom `acc_no` tidak ada di master **Giro** — perbaiki nomor rekening (jangan dalam notasi ilmiah Excel).
- Kolom `prefix` dan `nomor` wajib terisi pada setiap baris; `amount` harus angka ≥ 0.

### 8. BAPSB: No on-hand / released bilyets for this period on giro accounts

Penyebab: bilyet untuk project/period itu belum terdaftar; atau semua bilyet sudah **cair**/**void** sebelum akhir periode; atau rekeningnya tidak ditandai `needs_bilyet` (rekening tabungan). Daftarkan bilyet lebih dulu, atau pilih Period lain. Perubahan penanda `needs_bilyet` per rekening belum tersedia di antarmuka master Giro — minta administrator/developer mengubahnya.

### 9. BAPSB: BAPSB for this project and period already exists

Satu project hanya boleh punya satu berita acara per bulan. Buka berita acara yang sudah ada dari daftar **Cashier → BAPSB** dan lanjutkan dari sana (draf bisa diedit, yang sudah di-submit tidak).

### 10. Submit for validation tidak aktif / tidak bisa mengunggah PDF lagi

**Submit for validation** nonaktif sampai PDF terunggah (*Upload the signed PDF before submitting.*). Sebaliknya, *Cannot replace PDF after submission.* berarti berita acara sudah di-submit — PDF dan isi tidak bisa diubah lagi.

### 11. BAPSB tidak ditemukan lewat Search Menu di bilah atas

Pencarian menu saat ini memuat entri **Administrasi Bilyet**, bukan **BAPSB**. Gunakan sidebar **Cashier → BAPSB** atau alamat langsung `/cashier/bapsb`.

### 12. Jawaban HELP Assistant masih memakai versi manual lama

Administrator menjalankan `php artisan help:reindex` di server setelah manual ini diperbarui.

## Quick Reference: Status, URL, dan Glosarium

### Peta status bilyet

| Kode di sistem | Badge di layar | Arti |
|----------------|----------------|------|
| `onhand` | Onhand | Fisik ada di unit, belum diserahkan |
| `release` | Release | Sudah diserahkan, belum dicairkan |
| `cair` | Cair | Sudah dicairkan bank (final) |
| `void` | Void | Dibatalkan (final) |

### Jenis bilyet

| Kode di sistem | Pilihan di form | Label tampilan |
|----------------|-----------------|----------------|
| `cek` | Cek | Check / CEK |
| `bilyet` | BG | Bilyet Giro / BILYET |
| `loa` | LOA | Letter of Authority / LOA |
| `debit` | tidak ditawarkan | tidak diikutkan pada BAPSB |

### Status BAPSB

| Nilai | Arti di layar |
|-------|---------------|
| Draft (belum **submitted_at**) | Belum dikirim; masih bisa diedit, PDF bisa diganti |
| Submitted (badge **Submitted**) | Sudah dikirim; terkunci |
| `pending` | Menunggu validasi Accounting HO |
| `validated` | Sudah divalidasi HO |

### Alamat halaman penting

| Halaman | URL / rute |
|---------|------------|
| Administrasi Bilyet — Dashboard | `/cashier/bilyets?page=dashboard` |
| Administrasi Bilyet — List | `/cashier/bilyets?page=list` |
| Administrasi Bilyet — Upload | `/cashier/bilyets?page=upload` |
| Audit Trail | `/cashier/bilyets/audit` |
| Reports | `/cashier/bilyets/reports` |
| Daftar Cair / Release / Void | `/cashier/bilyets/cair`, `/cashier/bilyets/release`, `/cashier/bilyets/void` |
| Staging impor | `/cashier/bilyet-temps` |
| BAPSB — daftar | `/cashier/bapsb` |
| BAPSB — buat baru | `/cashier/bapsb/create` |
| BAPSB — cetak | `/cashier/bapsb/{id}/print` |
| BAPSB — tunggakan | `/cashier/bapsb/outstanding` |
| Bank Transactions | `/cashier/bank-transactions` |
| Master Giro | `/accounting/giros` |

### Glosarium

| Istilah | Arti |
|---------|------|
| **Bilyet** | Surat berharga bank: Bilyet Giro, Cek, atau Letter of Authority |
| **Bilyet Giro (BG)** | Perintah pemindahbukuan dana yang diterbitkan pemegang rekening giro |
| **Cek / Check** | Surat perintah pembayaran tunai kepada bank |
| **LOA** | Letter of Authority — surat kuasa yang diperlakukan sebagai surat berharga |
| **Giro** | Rekening bank yang menerbitkan surat berharga; sumber daftar pada field **Giro** |
| **`sap_account`** | Kode akun bank SAP pada master Giro; penghubung antara bilyet dan **Bank Account** transaksi bank |
| **`needs_bilyet`** | Penanda pada master Giro: rekening perlu BAPSB (default: giro = ya, tabungan = tidak) |
| **onhand / release / cair / void** | Empat status siklus hidup bilyet |
| **BAPSB** | Berita Acara Pemeriksaan Surat Berharga Bank — pemeriksaan fisik bulanan per project |
| **Cair** | Aksi/status bilyet yang sudah dicairkan bank (**Settled**) |
| **Release** | Aksi/status penyerahan bilyet ke penerima |
| **Void** | Aksi/status pembatalan bilyet |
| **Mutasi periode** | Jumlah bilyet yang cair (**Settled**) dan void (**Voided**) dalam bulan berita acara |
| **Checked by 1 / Checked by 2** | Nama pemeriksa yang diisi penyusun dan tercetak pada blok tanda tangan |
| **Approved by** | Kolom opsional pada form BAPSB yang tercetak di blok **Approved by** |
