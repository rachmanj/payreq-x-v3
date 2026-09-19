# Spek: Journal Entry multi-currency (USD) — AccountingOne

Status: disetujui Iwan 18 Sep 2026 (jawaban grill: USD dulu · kurs manual dgn default kurs harian app · **campuran valas+IDR dalam satu dokumen TIDAK diizinkan** — revisi 19 Sep 2026 setelah uji SAP prod · cukup modul JE · daftarkan akun valas yang sudah ada).

## Tujuan
Modul Journal Entry AccountingOne bisa mencatat jurnal **USD murni** atau **IDR murni**, supaya transaksi valas (mis. settlement obligasi BCA USD) dan biaya IDR terkait bisa diinput dari aplikasi (seringnya **dua jurnal terpisah**), bukan manual di SAP.

## Fakta teknis terverifikasi (18 Sep 2026, prod)
- SAP SL: baris Journal Entry punya properti **`FCCurrency` (string)**, **`FCDebit` (double)**, **`FCCredit` (double)** — TIDAK ada `FCLineTotal`. (Sumber: `$metadata` prod, ComplexType `JournalEntryLine`.)
- Kurs harian app: tabel `exchange_rates` (`currency_from`, `currency_to`, `exchange_rate`, `effective_date`, `kmk_number`, `source`), terisi otomatis (USD→IDR tersedia).
- Modul JE sekarang IDR-only: tabel `journal_entries`/`journal_entry_details` tanpa kolom mata uang; `SapJournalEntryBuilder` tidak mengirim FC.
- Akun valas yang sudah ada di SAP (contoh): `11201026` BCA USD - 291.088.5858, `11201027` BCA USD - 191.926.8989, `11201020` BCA IDR - 291.099.5858, `11301006` Bond INDON34NEW (USD). Akun obligasi baru (INDON31NEWNEW) & piutang bunga dibuat tim di SAP (di luar aplikasi).

## Uji nyata SAP prod (19 Sep 2026, semua JE uji distorno)
| Skenario | Hasil SAP |
|---|---|
| Jurnal **USD murni** (semua baris valas) | **Diterima** |
| Jurnal **IDR murni** | **Diterima** |
| Jurnal **campuran** USD + IDR dalam satu dokumen | **Selalu ditolak** |

Pesan error SAP menyesatkan (bergantung nominal): `-4006 Update the exchange rate`, `-5012 Unbalanced Transaction`, atau `-5002` jika dicoba paksa `FCCurrency` pada baris IDR atau semua baris `USD`. **Tidak ada bentuk payload campuran yang diterima.**

Solusi operasional yang terverifikasi: pecah transaksi (contoh obligasi) menjadi **dua jurnal** — satu USD murni, satu IDR murni.

## Model data
- `journal_entry_details`: tambah `currency` (string, default `'IDR'`), `fc_amount` (decimal(18,2) nullable), `exchange_rate` (decimal(18,6) nullable).
- `journal_entries`: tambah `has_foreign_currency` (boolean default false) untuk filter/tampilan daftar.

## Aturan bisnis
1. Baris `IDR` (default): perilaku sekarang — `debit`/`credit` = nominal IDR; `fc_amount` & `exchange_rate` NULL.
2. Baris `USD`: `fc_amount` (>0) dan `exchange_rate` (>0) WAJIB; `debit`/`credit` (IDR) = `round(fc_amount × exchange_rate, 2)` — dihitung sistem, tidak diketik manual.
3. **Kurs default**: saat baris dipilih USD, form mengisi kurs dari `exchange_rates` terbaru (`currency_from='USD'`, `currency_to='IDR'`, `effective_date <= tanggal jurnal`, ambil paling baru) dan **bisa dioverride** user (kasus kurs bank berbeda).
4. **Seimbang per mata uang**: total debit = total kredit harus terpenuhi (a) dalam IDR/LC (pembulatan 0,01) DAN (b) di setiap grup mata uang valas (mis. grup USD harus balance). Pesan error jelas, Bahasa Indonesia, menyebut mata uang & selisihnya.
5. Hanya **USD** + IDR yang divalidasi sebagai valas (mata uang lain ditolak dengan pesan "Baris valas hanya mendukung USD untuk saat ini").
6. **Campuran USD + IDR dalam satu jurnal DITOLAK** di aplikasi (`JournalEntryMulticurrencyService::validateLines`), dengan pesan: *SAP tidak menerima jurnal yang mencampur valas (USD) dan IDR dalam satu dokumen. Pisahkan menjadi dua jurnal: satu jurnal valas (USD) dan satu jurnal IDR.* Berlaku di form (`StoreJournalEntryRequest`) dan sebelum kirim SAP (`JournalEntryBuilder::validate`).

## Payload SAP (`SapJournalEntryBuilder`)
- Baris USD: `FCCurrency => 'USD'`, `FCDebit`/`FCCredit` = nominal FC, plus `Debit`/`Credit` = nilai IDR (LC). `AccountCode`, `LineMemo`, `ProjectCode` seperti sekarang.
- Baris IDR: seperti perilaku sekarang (tanpa `FCCurrency`/`FCDebit`/`FCCredit`).
- Validasi balance dilakukan sebelum kirim (lihat aturan 4). Jurnal yang lolos validasi app hanya **murni USD** atau **murni IDR** (aturan 6).

## UI (view JE create/edit, gaya VJ soft-UI)
- Tabel baris: tambah kolom **Mata Uang** (dropdown IDR/USD), **Nominal valas** & **Kurs** (aktif hanya bila USD), kolom Debit/Kredit IDR tetap ada (terhitung otomatis, read-only untuk baris USD).
- Petunjuk singkat: valas (USD) dan IDR tidak boleh dicampur dalam satu jurnal; gunakan dua jurnal terpisah bila perlu.
- Ringkasan total per mata uang ditampilkan di bawah tabel (mis. "Total USD: D 706.152,03 / K 706.152,03 ✓ · Total IDR: ...").
- Preview sebelum submit menampilkan baris FC + hasil konversi IDR.
- Nomor desimal: FC 2 desimal; IDR tanpa desimal pada tampilan (pola halaman lain).

## Test (feature + unit)
1. IDR = fc × rate (pembulatan) benar.
2. Baris USD tanpa `fc_amount`/`exchange_rate` → validasi menolak.
3. Balance per mata uang: grup USD tidak balance → ditolak dengan pesan menyebut USD & selisih.
4. Payload: baris USD memuat `FCCurrency`/`FCDebit`/`FCCredit`; baris IDR tidak memuatnya.
5. Kurs default terisi dari `exchange_rates` (mock) dan bisa dioverride.
6. JE **campuran** (USD + IDR), skenario obligasi → **ditolak**; pesan menyebut USD dan IDR.
7. JE **USD murni** (3 baris obligasi tanpa baris IDR) → lolos validasi & store.
8. JE **IDR murni** (2 baris) → lolos validasi.
9. JE IDR murni: perilaku & payload tidak berubah (regression).
10. Mata uang selain USD → ditolak.

## Uji nyata SAP (wajib sebelum dipakai user)
Setelah deploy: buat **satu JE kecil USD** (mis. Dr/Cr USD 10 dengan kurs), verifikasi di SAP (`JournalEntries(JdtNum)` — cek `FCCurrency`/`FCDebit`/`FCCredit` dan `Debit`/`Credit` IDR), lalu **batalkan/reversal** JE itu. Campuran USD+IDR sudah diverifikasi ditolak SAP (19 Sep 2026). Perlu izin Iwan ("boleh uji").

## Di luar lingkup
Mata uang selain USD · OP/pembayaran valas · template JE valas · pembuatan akun GL baru di SAP.
