# Spec — Rekening Tujuan per Baris Transaksi (PR DNC)

Sumber: email DNC Administrator (dnc.administrator@arka.co.id) 9 Sep 2026 → permintaan Iwan 13 Sep 2026.
Status: keputusan grill ronde 1 disetujui Iwan (13 Sep 2026) — siap implementasi.

## 1. Goal

Satu PR (advance) bisa berisi **beberapa baris transaksi**, dan **tiap baris punya rekening tujuan transfer sendiri**. Draft harus bisa disimpan dengan metode Transfer walau satu rekening header belum dipilih, selama rekening per baris sudah diisi.

Konteks nyata: DNC = departemen 10 "Design & Construction" (akronim DNC) — user `dncdiv` (id 23) membuat PR mode multi-anggaran (contoh PR 16340: 12 baris transaksi, PR 16349: 5 baris) yang pembayarannya ke 17 rekening vendor berbeda.

## 2. Scope

**In (fase 1):**
- Form PR advance mode **multi-anggaran** (`budget_link_mode = multi_allocation`): kolom **Rekening Tujuan** + **Rencana Nominal** per baris transaksi.
- Mode advance **legacy** (RAB tunggal): tetap pakai daftar tujuan level-PR yang sudah ada (tidak ada baris transaksi).
- Halaman approve: tampilan read-only per baris transaksi.
- Print/PDF payreq: blok rekening tujuan per baris transaksi (kondisional).
- Halaman kasir split: daftar rencana per baris transaksi + tombol "Isi dari rencana" mengisi per baris.

**Out (fase berikutnya / tidak dikerjakan):**
- Reimburse (fase 2).
- Pembayaran otomatis / 1 outgoing per baris transaksi — kasir tetap menambah baris pembayaran manual.
- Relasi baris transaksi ↔ realisasi / verification journal.
- SAP: **tidak disentuh** (PR transfer tidak membuat OP SAP).

## 3. Keputusan (grill 13 Sep 2026)

| Q | Keputusan |
|---|---|
| Q1 | Rekening ditempel **per baris transaksi** (baris anggaran). Daftar tujuan level-PR yang ada tetap sebagai ringkasan/fallback. |
| Q2 | Berlaku untuk advance **multi-anggaran + legacy** sekarang; **reimburse fase 2**. |
| Q3 | Rekening per baris **opsional**. Hanya rekening milik requestor. Rencana nominal per baris **tidak boleh melebihi** nominal baris transaksi itu. |
| Q4 | Kasir: tombol "Isi dari rencana" mengisi **per baris transaksi** (sekali klik = satu baris). Tidak ada pembayaran otomatis. |
| Q5 | Tampil di halaman approve (read-only) + print/PDF (kondisional, PR tanpa rencana tampil persis seperti sekarang). SAP tidak disentuh. |

## 4. DB changes

`payreq_anggaran_allocations` (migration baru, additive, nullable):

| Kolom | Tipe | Catatan |
|---|---|---|
| `transfer_account_id` | bigint unsigned nullable | FK `transfer_accounts` `nullOnDelete` |
| `planned_amount` | bigint nullable | rencana nominal per baris (IDR integer) |

Tidak ada perubahan pada `payreq_transfer_destinations`, `payreqs`, `outgoings`.

## 5. Validation & sinkronisasi

- `allocations.*.transfer_account_id` → `nullable|integer|exists:transfer_accounts,id` + cek kepemilikan (`user_id = auth()->id()`), pesan: `"Akun transfer tidak valid atau bukan milik Anda."`.
- `allocations.*.planned_amount` → `nullable|numeric|min:1`, dan **≤ `allocations.*.amount`** (pesan: total/rencana melebihi nominal baris).
- Normalisasi pemisah ribuan pada `planned_amount` (reuse `PayreqTransferDestinationService::normalizePlannedAmountInput`) — titik DAN koma dibuang, string kosong → null.
- Bila **ada minimal satu baris** dengan rekening: `payment_method` dipaksa `transfer` dan `payreqs.transfer_account_id` = rekening baris **pertama yang terisi** → draft bisa disimpan tanpa memilih satu rekening header.
- Urutan fallback rekening header: (1) baris transaksi pertama yang terisi, (2) daftar tujuan level-PR pertama (existing), (3) belum ada → validasi lama tetap berlaku (harus pilih satu rekening).
- `syncAdvanceAllocations` menyimpan kedua kolom baru (rows masih delete+recreate seperti sekarang).

## 6. UI/UX

- **Form advance create/edit (multi-anggaran):** tabel baris transaksi dapat 2 kolom baru — `Rekening Tujuan` (select2 rekening milik user + tombol "+ Tambah Akun Transfer" yang sudah ada) dan `Rencana Nominal` (input angka, boleh kosong). Perilaku baris lama (anggaran, nominal, keterangan) tidak berubah.
- **Halaman approve payreq:** tabel read-only rekening tujuan per baris transaksi (baris/uraian, nominal baris, rekening tujuan, rencana nominal). Tidak tampil kalau tidak ada rencana.
- **Print/PDF payreq:** blok rekening tujuan per baris transaksi, kondisional; PR tanpa rencana per baris harus tampil **persis** seperti sekarang.
- **Kasir (`cashier/approved/split`):** daftar rencana per baris transaksi (read-only) + tombol "Isi dari rencana" mengisi rekening & nominal baris berikutnya secara berurutan (rencana nominal null → kolom nominal dikosongkan). Fallback ke rencana level-PR bila tidak ada rencana per baris.
- Semua label UI Bahasa Indonesia.

## 7. Endpoints

Tidak ada route baru. Yang berubah hanya payload/validasi:
- `POST user-payreqs/advance/proses` → payload `allocations[]` bertambah `transfer_account_id`, `planned_amount`.
- Halaman kasir split: data rencana per baris dikirim sebagai JSON ke view (tanpa endpoint baru).

## 8. Risks

- **Jangan sentuh SAP** — PR transfer tidak membuat OP SAP; verifikasi tidak ada panggilan SAP baru.
- **Jangan ubah perilaku legacy**: PR tanpa rencana per baris & daftar tujuan kosong harus tampil/berperilaku identik dengan sebelumnya (regresi data lama aman).
- **`planned_amount` skala uang** — integer IDR, wajib normalisasi titik/koma (jebakan lama: `3.000.000` tersimpan jadi `3`).
- Baris transaksi di-delete+recreate saat simpan → rekening & rencana per baris harus ikut tertulis ulang (jangan sampai hilang saat edit draft).
- Nominal baris kosong/kosong-penuh: rencana nominal opsional, tidak boleh bikin error validasi baru.

## 9. Verifikasi wajib

- Test fitur baru: draft multi-anggaran dengan 2 baris → 2 rekening berbeda tersimpan, header = baris pertama, tersimpan sebagai draft **tanpa** memilih rekening header.
- Tolak: rekening milik user lain; rencana nominal > nominal baris.
- Terima: rencana nominal ≤ nominal baris; baris tanpa rekening (opsional).
- Edit draft: ubah rekening baris → tersimpan tanpa duplikasi.
- Print/PDF & halaman approve: muncul saat ada rencana, hilang saat tidak ada.
- Full suite tidak menambah kegagalan baru dibanding baseline.
