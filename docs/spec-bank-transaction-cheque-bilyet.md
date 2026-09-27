# Spek: Select Cek / Bilyet pada Bank Transaction (kasir)

Status: **hasil grill 26 Sep 2026 — siap implementasi**
Sumber keputusan: Iwan (rachmanj), ronde 1 & 2.

## 1. Goal

Beberapa transaksi bank memerlukan **cek/bilyet** (mis. pindah buku bank → Petty Cash memakai bilyet giro). Saat ini tidak ada tempat mencatatnya, sehingga nomor cek hanya ditulis di keterangan bebas. Fitur ini menambahkan **pilihan cek/bilyet** pada form Bank Transaction yang disimpan **di aplikasi saja** — **tidak** ikut dikirim ke SAP.

## 2. Scope

### In scope
- Kolom baru `bilyet_id` (nullable) pada `verification_journals`.
- Select **Cheque / Bilyet** di form `cashier.bank-transactions.create` dan `.edit` — bersifat **opsional**.
- Daftar pilihan difilter mengikuti **Bank Account** yang dipilih.
- Tampilan di halaman detail (`show`): nomor + tanggal + nominal + status + tautan ke halaman Bilyet.
- Validasi server: bilyet yang dipilih harus ada dan berasal dari giro/rekening bank yang sama.

### Out of scope
- **Tidak** menjadi bagian payload yang dikirim ke SAP (murni referensi aplikasi).
- **Tidak** mengubah status, nominal, atau tanggal di master `bilyets` (modul Loan/Bilyet tetap sumber kebenaran status bilyet).
- **Tidak** mengubah kelayakan jalur langsung SAP (`cashier_submit_vj_to_sap`) — perilakunya tetap sama.
- **Tidak** ada perubahan pada halaman validasi Accounting (belum diperlukan; bisa ditambahkan menyusul bila diminta).
- **Tidak** mengubah alur VJ tipe lain (`verification`, `cash`).
- **Tidak** ada backfill untuk transaksi lama.

## 3. Tech decisions

- **Master cek/bilyet = tabel `bilyets`** (1.222 baris pada data produksi), kolom relevan: `giro_id`, `prefix`, `nomor`, `type`, `bilyet_date`, `cair_date`, `amount`, `remarks`, `status`, `project`. Sudah dipakai modul Loan (`status='cair'`) dan OP (`general_outgoing_payments.giro_id`).
- **Relasi bank:** `bilyets.giro_id` → `giros.id`, dan `giros.sap_account` = kode akun bank (`11201…`) yang dipakai field *Bank Account* di form. Contoh nyata: giro id 26 → `acc_no 149-0019306770`, `sap_account 11201072`, project 025C → bank Mandiri 025C yang dipakai Rangga.
- Model: `App\Models\Bilyet` (sudah ada).
- Tidak ada route/endpoint baru — field ikut submit form yang sudah ada.

## 4. DB changes

- Migrasi baru: tambah `verification_journals.bilyet_id` **nullable**, FK → `bilyets.id`, dengan index.
- Tidak ada perubahan tabel lain, tidak ada seeder/izin baru.

## 5. UI/UX

- Label: **"Cheque / Bilyet"**, opsi pertama **"— none —"**, memakai Select2 seperti field lain (halaman ini berbahasa Inggris).
- Teks opsi: `<prefix> <nomor> · <bilyet_date> · <amount> · <status>` (project ditampilkan sebagai info).
- Daftar opsi difilter mengikuti **Bank Account terpilih** (`giros.sap_account` = bank account VJ). Bila tidak ada yang cocok, dropdown tetap bisa dibiarkan kosong.
- Muncul di **Create** dan **Edit**, keduanya opsional.
- Halaman **detail (show)**: tambahkan baris "Cheque / Bilyet" berisi nomor + tanggal + nominal + status dengan tautan ke halaman Bilyet; bila kosong tampilkan "—".
- Ikuti VJ Soft UI yang sudah dipakai modul Bank Transaction; tidak ada style baru.

## 6. Route / endpoint

- Tidak ada route baru.
- `POST /cashier/bank-transactions` dan `PUT /cashier/bank-transactions/{id}` menerima `bilyet_id` (nullable, `exists:bilyets,id`).
- Validasi tambahan: `bilyet_id` yang dikirim harus berasal dari giro yang sinkron dengan `bank_account` VJ (cegah salah pasang lewat request manual).

## 7. Risks

| Risiko | Mitigasi |
|---|---|
| Salah memasang cek pada transaksi | Dropdown terfilter per Bank Account + validasi server bilyet ↔ bank account |
| Kasir mengira cek ikut terkirim ke SAP | Tidak ada perubahan payload SAP; catatan kecil di form: "referensi aplikasi saja" |
| Field baru mengganggu jalur langsung SAP | Tidak masuk payload & tidak mengubah logika kelayakan (tetap: 1 bank di kredit, debit di daftar izin, ≤ ambang, punya izin) |
| Bilyet master dihapus/diubah | FK nullable + tampilan aman bila bilyet tidak ditemukan ("—" atau nomor mentah) |
| Cek yang sama dipakai dua transaksi tidak terdeteksi | Diterima: status bilyet tetap dikelola modul Loan/Bilyet, fitur ini hanya referensi |
| Halaman detail diakses user tanpa hak lihat bilyet | Tautan hanya muncul bila user berhak membuka halaman Bilyet (perilaku izin halaman yang sudah ada) |

## 8. Yang masih terbuka

- Menampilkan pilihan cek di halaman validasi Accounting (untuk transaksi di atas ambang) — **belum** dikerjakan.
- Mencetak nomor cek pada PDF/print Bank Transaction — belum termasuk; bisa ditambahkan bila diminta.
