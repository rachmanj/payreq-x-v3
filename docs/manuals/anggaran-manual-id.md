# RAB / Anggaran

Di aplikasi ini, **Anggaran** adalah rekaman anggaran di balik **RAB**. Staf membuat dan mengajukan RAB melalui **My PayReqs**; akuntansi/kontrol memakai **Reports** untuk dashboard, konsolidasi, fund pool, dan pemeliharaan.

## My PayReqs — RAB (pengguna)

### Membuka menu

- Menu atas **My PayReqs** → **RAB**, atau  
- Sidebar di bawah PayReqs → **RAB** (rute sama).

Diperlukan hak akses **akses_anggarans**. Jika menu **RAB** tidak ada, minta administrator memberikan hak tersebut.

### Daftar dan draf

Halaman **index** menampilkan daftar RAB/Anggaran Anda. Dari sini buka **create** atau data draf/disubmit sesuai status dan kebijakan.

### Membuat RAB baru

1. Buka **RAB** → **Create** (`/user-payreqs/anggarans/create`).  
2. Isi **nomor**, **rab_no**, **description**, **amount**, dan field terkait. Pilih **tipe RAB** (**periode**, **event**, atau **buc**).  
   - Untuk **periode**, pilih **Periode anggaran** dari daftar aktif untuk project Anda.  
   - Untuk **event** atau **buc**, isi **start date** dan **end date** sesuai form.  
3. Opsional — **budget lines**: tambah baris detail (**account**, **description**, **amount**) jika layar menyediakan butir baris.  
4. Lampirkan berkas jika alur kerja Anda mewajibkan.  
5. Kirim form dengan aksi **simpan draf** versus **Submit** (tombol mengacu pada `button_type`: draf tetap dapat diedit; submit memasukkan ke approval jika `create_submit` / `edit_submit` berhasil).

**Submit** yang berhasil membuat rencana approval untuk tipe **`rab`** dan mengubah status menjadi **submitted** jika setup approval berhasil.

### Edit dan detail

- **Edit** membuka data jika pengguna diizinkan (kebijakan **editThroughPayreq** pada Anggaran tersebut).  
- **Show** menampilkan data dan payreq terkait (`payreqs_data`).  
Satu baris detail anggaran dapat dihapus melalui rute **detail destroy** jika UI menyediakannya.

## Menu Approvals — RAB

Approver membuka **Approvals** di navbar (atau sidebar) → **RAB** untuk memproses RAB yang sudah diajukan (rute `approvals.request.anggarans`). Tahapan approval mengikuti pengaturan organisasi Anda setelah submit.

## Reports — terkait RAB (akuntansi / kontrol)

Buka **Reports** dari **My PayReqs** jika memiliki **akses_reports**, atau gunakan pintu masuk Reports yang ditugaskan. Di halaman indeks Reports, bagian **RAB Related** berisi tautan (masing-masing dapat memerlukan hak berbeda):

| Nama laporan        | Hak akses tipikal        |
|---------------------|---------------------------|
| **Periode RAB**     | **akses_periode_anggaran** |
| **RAB Dashboard**   | **akses_report_rab**       |
| **RAB Consolidated**| **akses_report_rab**       |
| **RAB Fund pool**   | **recalculate_release**    |
| **RAB List**        | **akses_report_rab**       |

Gunakan **Periode RAB** untuk memelihara periode aktif. **RAB List** untuk pencarian, pembaruan massal, **recalculate**, dan tampilan inactive sesuai peran. Aksi **Fund pool** menandai jumlah di-pool atau di-release jika hak akses memungkinkan.

## Kaitan dengan Payreq

Payreq dapat merujuk Anggaran/anggaran tergantung konfigurasi (misalnya bulk activate/deactivate dan mode budget link). Label field persis tampil di layar buat/realisasi Payreq yang terhubung ke daftar **anggarans**.
