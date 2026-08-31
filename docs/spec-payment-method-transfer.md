# Spec — Jenis Pembayaran (Cash / Transfer) untuk Payreq Advance & Reimburse

## 1. Goal

Payreq tipe **advance** dan **reimburse** mencatat **jenis pembayaran**: `cash` atau `transfer`. Jika `transfer`, tentukan **tujuan transfer**: bank mana, nomor rekening, atas nama siapa (bisa rekening karyawan ATAU vendor yang diminta requestor, mis. pembayaran sertifikasi pelatihan langsung ke vendor). Daftar akun transfer **melekat per requestor** dan bisa ditambah **on-the-fly** dari form payreq.

## 2. Scope

### In
- Kolom `payment_method` (cash|transfer) + `transfer_account_id` di `payreqs`
- Tabel baru `transfer_accounts` (daftar akun transfer milik requestor)
- Form create **advance** dan **reimburse**: pilih metode bayar; jika transfer → pilih akun transfer dari daftar milik requestor + tambah record baru on-the-fly
- Edit metode bayar selama payreq masih **draft/revise** (belum melewati approved)
- Tampilan: badge metode bayar di list payreq, detail payreq, print
- Validasi: transfer → bank + no. rekening + atas nama + label **wajib**
- Record transfer **reusable** lintas payreq (sekali simpan, bisa dipilih lagi)

### Out
- Utility bills → payreq flow (`createPayreq`) — menyusul di iterasi berikutnya
- Halaman CRUD khusus kelola daftar transfer (cukup on-the-fly dulu; halaman kelola bisa menyusul)
- Metode bayar pada `outgoings` / cashier journal — di luar scope (informasi bayar ada di payreq)

## 3. Tech Decisions

- Laravel 10 + Blade + AdminLTE 3 (sama dengan modul lain)
- Select2 untuk dropdown bank & akun transfer (pola yang sudah dipakai)
- Dropdown bank dari tabel `banks` yang sudah ada (9 bank: Mandiri, BPD Kaltim, BCA, BNI, Panin, Danamon, BNP, CIMB, BSI)
- Tambah akun transfer on-the-fly: **modal inline** di form payreq → POST AJAX ke endpoint, lalu refresh dropdown + pilih record baru

## 4. DB Changes

### Migration 1: `create_transfer_accounts_table`
```php
Schema::create('transfer_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // pemilik (requestor)
    $table->foreignId('bank_id')->constrained();                     // FK ke banks
    $table->string('account_number');
    $table->string('account_name');       // atas nama
    $table->string('label');              // mis. "Vendor A - Sertifikasi", "Rekening pribadi"
    $table->timestamps();
});
```

### Migration 2: `add_payment_method_to_payreqs_table`
```php
Schema::table('payreqs', function (Blueprint $table) {
    $table->string('payment_method')->nullable()->after('remarks'); // 'cash' | 'transfer'
    $table->foreignId('transfer_account_id')->nullable()->after('payment_method')
        ->constrained('transfer_accounts')->nullOnDelete();
});
```

## 5. UI/UX

### Form create advance & reimburse (`user-payreqs/advance/create`, `user-payreqs/reimburse/create` + `add_details`)
- Radio/select **Metode Pembayaran**: 💵 Cash | 🏦 Transfer
- Jika **Transfer** (show/hide dinamis):
  - Select2 **Akun Transfer** — daftar milik requestor (`label — bank (no. rek) — atas nama`)
  - Tombol **"+ Tambah Akun Transfer"** → modal: label, bank (dropdown dari `banks`), no. rekening, atas nama → POST AJAX → tersimpan ke daftar milik requestor → dropdown refresh + otomatis terpilih
- Default: **cash**

### List payreq (user & accounting)
- Badge metode bayar: `Cash` / `Transfer` (+ tooltip rekening tujuan)

### Detail & print payreq
- Baris "Metode Pembayaran" + jika transfer: bank, no. rekening, atas nama, label

### Edit
- Saat payreq masih `draft`/`revise`: metode bayar + akun transfer bisa diubah
- Setelah `submitted`/`approved`/dst: terkunci (read-only)

## 6. API Endpoints

| Method | URL | Fungsi |
|---|---|---|
| POST | `/user-payreqs/transfer-accounts` | Simpan akun transfer baru milik auth user (AJAX, return JSON record + id) |
| GET | `/user-payreqs/transfer-accounts` | (opsional) List akun transfer milik auth user |

### Save flow
- `PayreqController::store`: terima `payment_method` + `transfer_account_id`
  - `payment_method = transfer` → `transfer_account_id` **wajib** + akun harus milik auth user
  - `payment_method = cash` → `transfer_account_id = null`
- Update (draft/revise): validasi sama

### Validasi (Rules)
```php
'payment_method' => 'required|in:cash,transfer',
'transfer_account_id' => 'required_if:payment_method,transfer|nullable|integer|exists:transfer_accounts,id',
```
Tambahan: `transfer_account_id` harus `user_id = auth()->id()`.

## 7. Risks

- **Akun transfer orang lain**: pastikan validasi kepemilikan (`user_id = auth`) — jangan sampai requestor memilih rekening milik user lain
- **On-the-fly double submit**: modal tambah akun → disable tombol saat submit, cegah record dobel
- **Relasi `banks`**: tabel sudah ada, pastikan migration `bank_id` FK benar (constrained → banks.id)
- **Edit setelah approved**: pastikan lock logic konsisten dengan status payreq (editable flag sudah ada di payreqs)
- **Print & badge**: format rekening sensitif? (kebijakan internal — tampilkan apa adanya dulu, bisa di-mask belakangan)

## Keputusan Grill (ringkasan)
1. Cakupan: advance + reimburse sekaligus (penerima bisa vendor atas permintaan requestor)
2. Daftar transfer melekat per requestor, bisa tambah record on-the-fly
3. Input di form buat payreq, tampil di detail + dipakai cashier
4. Dropdown bank dari tabel `banks`
5. Transfer → wajib; show/hide dinamis; badge di list
6. Label record wajib
7. Edit selama draft/revise
8. Utility bills → payreq: menyusul
9. On-the-fly saja (tanpa halaman CRUD terpisah)
10. Record reusable + tampil di list/detail/print
