# Jurnal Umum Manual (Accounting)

Modul **Manual Journal Entry** (Jurnal Umum Manual) memungkinkan staf akuntansi yang berwenang membuat voucher jurnal seimbang di AccountingOne, opsional dari **JE Templates**, lalu **Submit to SAP B1** agar jurnal terposting di SAP. Modul ini terpisah dari **Verification Journal** (alur verifikasi kasir).

## Siapa yang dapat membuka

Buka sidebar kiri **Accounting** → **Journal Entries** atau **JE Templates**.

Diperlukan hak akses **`create_manual_journal_entry`**. Jika menu tidak muncul, minta administrator memberikan hak tersebut (biasanya peran **superadmin**, **admin**, **cashier**, atau **approver**).

Anda juga dapat mengetik **Journal Entries** atau **JE Templates** di kolom **Search Menu here** di bilah atas.

Dropdown **Accounting** di navbar atas menampilkan tautan yang sama jika Anda memiliki hak akses.

## Daftar Journal Entries

**Accounting** → **Journal Entries** (`/accounting/journal-entries`) menampilkan semua jurnal manual dalam tabel yang dapat dicari.

Kolom meliputi **Number**, **Date**, **Memo**, **Status** (Draft / Posted / Failed / Reversed), **SAP Journal No**, dan **Created By**.

Gunakan **New Journal Entry** untuk membuat draf. Aksi baris: **View** (mata), **Edit** dan **Delete** (hanya draf), **Print**.

## Membuat jurnal manual

1. Buka **Accounting** → **Journal Entries** → **New Journal Entry**.
2. Isi **Date** (wajib), **Reference** dan **Memo** (opsional).
3. Opsional — **Load from Template**: pilih template dari dropdown. Baris terisi otomatis (akun, Dr/Cr, project, cost center, deskripsi). Anda tetap mengisi **amount** di setiap baris.
4. Di **Journal Lines**, tambah atau ubah baris:
   - **Account** — ketik untuk mencari; pilih dari saran autocomplete.
   - **Dr/Cr** — Debit atau Credit.
   - **Amount** — wajib di setiap baris.
   - **Project** dan **Cost Center** — pilih jika diwajibkan COA Anda.
   - **Description** — keterangan baris.
5. Gunakan **Add Line** untuk baris tambahan (minimal dua baris). Footer menampilkan **Total Debit**, **Total Credit**, dan **Difference** — selisih harus **0.00** sebelum simpan.
6. Klik **Save Journal Entry**.

Setelah simpan, Anda diarahkan ke halaman **show**. Sistem memberi nomor seperti **JE-000001**.

## Edit atau hapus draf

Di halaman **show** jurnal, selama status **Draft** (belum diposting ke SAP):

- **Edit** — ubah header dan baris (aturan keseimbangan tetap berlaku).
- **Delete** — dari aksi baris di daftar (ikon sampah), hanya jika masih dapat diedit.

Jurnal yang sudah **Posted** atau **Reversed** tidak dapat diedit atau dihapus di AccountingOne.

## Submit ke SAP B1

Di halaman **show**, untuk entri **Draft** yang seimbang:

1. Periksa header dan **Journal Lines**.
2. Klik **Submit to SAP B1** dan konfirmasi dialog.
3. Jika berhasil, status menjadi **Posted**, **SAP Journal No** terisi, dan **Submission History** muncul.

Submit diizinkan untuk pengguna dengan peran **superadmin**, **admin**, **cashier**, atau **approver** (selain **`create_manual_journal_entry`**).

Jika gagal, status **Failed** dan **Last Error** menampilkan pesan SAP. Perbaiki data jika masih draf, atau hubungi dukungan.

## Reverse di SAP B1

Setelah posting berhasil, pengguna dengan hak **`cancel_sap_journal`** melihat **Reverse in SAP B1** di halaman show.

1. Klik **Reverse in SAP B1**.
2. Isi **Reason** (wajib) di modal.
3. Konfirmasi. SAP membatalkan jurnal; entri ditandai **Reversed**.

## Cetak voucher

Klik **Print** di halaman show atau baris daftar. Membuka **Journal Voucher** di tab baru (nomor, tanggal, referensi, memo, baris, prepared by).

## JE Templates

**Accounting** → **JE Templates** (`/accounting/journal-entries/templates`) menampilkan template bersama (global — pengguna berwenang dapat memakai template mana pun).

### Buat atau edit template

1. **New Template** (atau edit dari daftar).
2. Isi **Name** dan **Description** (opsional).
3. Tentukan **Journal Lines** dengan akun, Dr/Cr, project, cost center, deskripsi. **Default Amount** opsional (petunjuk saat dimuat ke JE baru).
4. **Save Template**.

Template tidak memposting ke SAP sendiri. Saat membuat JE, gunakan **Load from Template** untuk menyalin layout baris, lalu isi amount dan simpan.

## Ringkasan status

| Status | Arti |
|--------|------|
| **Draft** | Tersimpan di AccountingOne saja; dapat diedit |
| **Posted** | Berhasil disubmit ke SAP B1 |
| **Failed** | Submit SAP dicoba tetapi gagal |
| **Reversed** | Jurnal yang diposting dibatalkan di SAP B1 |

## Hak akses terkait

| Hak akses | Fungsi |
|-----------|--------|
| **`create_manual_journal_entry`** | Menu, daftar, buat, edit, hapus draf, template, UI submit |
| **`cancel_sap_journal`** | **Reverse in SAP B1** pada entri yang sudah diposting |

## Asisten bantuan (Help)

Klik ikon **?** di bilah atas (**Help**). Di tab **How-to**, ajukan pertanyaan seperti “Bagaimana membuat manual journal entry?” atau “Cara submit JE ke SAP?”. Jawaban memakai manual ini setelah `php artisan help:reindex`.
