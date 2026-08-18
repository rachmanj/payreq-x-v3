# Spec — Utility Billing (PLN / PDAM / TELKOM per ID Pelanggan)

> Feature: monitoring & pencatatan tagihan utilitas per ID Pelanggan.
> Branch: `feature/utility-billing`
> Status: **Spec disetujui** — siap implement.

---

## 1. Goal

Manager Accounting memantau tagihan bulanan **PLN, PDAM, TELKOM** per **ID Pelanggan** (bukan cuma total per jenis utilitas). Setiap ID Pelanggan punya tagihan bulanan yang dipantau status bayarnya (belum / telat / lunas).

Pembayaran tetap melalui alur existing: **requestor payreq (advance/reimburse) → bayar di luar sistem** → tandai lunas di modul ini.

---

## 2. Scope

### In Scope (Fase 1)
- Master **ID Pelanggan** (CRUD): jenis utilitas, no ID, nama/alias, lokasi, project, mapping akun COA.
- Pencatatan **tagihan bulanan** per ID Pelanggan + status bayar.
- Tandai **lunas** (manual: tanggal bayar + nomor referensi).
- **Copy dari bulan lalu** (duplikat tagihan periode sebelumnya → periode baru).
- Dashboard ringkas + list tagihan dengan highlight warna (belum/kuning/telat/hijau-lunas).
- Menu terpisah "Utilities" di sidebar.
- Permission `akses_utilities` (role `superadmin` + `manager`).

### Out of Scope (Fase 2 / nanti)
- Link otomatis ke `payreq_id` / `realization_id`.
- Import Excel bulk.
- Reminder/notifikasi Telegram jatuh tempo.
- Meter reading detail (kWh/m3) — hanya simpan angka opsional.

---

## 3. Tech Decisions

| Item | Keputusan |
|---|---|
| Framework | Laravel 10 (Blade + Tailwind + AdminLTE + DataTables) |
| Menu | Menu sidebar terpisah "Utilities" (sejajar Accounting) |
| Status bayar | **Derived** (bukan kolom): lunas / telat / belum / mendekati-jatuh-tempo |
| Anti duplikat | Unique `(utility_customer_id, periode)` |
| Jenis utilitas | `pln` / `pdam` / `telkom` (enum/string) |
| Project dim | `project` string (konsisten `accounts.project` / `payreqs.project`) |
| Permission | `akses_utilities` (migration + seeder) |

---

## 4. DB Changes (2 tabel baru)

### 4.1 `utility_customers` (master ID pelanggan)

```php
Schema::create('utility_customers', function (Blueprint $table) {
    $table->id();
    $table->string('jenis_utilitas', 20);        // pln | pdam | telkom
    $table->string('id_pelanggan', 50);           // nomor ID pelanggan
    $table->string('nama');                       // alias, mis. "PLN - Kantor 000H"
    $table->string('lokasi')->nullable();         // alamat / lokasi meter
    $table->string('project', 20);                // 000H / 001H / ...
    $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['jenis_utilitas', 'id_pelanggan']);
    $table->index(['project', 'jenis_utilitas']);
});
```

### 4.2 `utility_bills` (tagihan bulanan)

```php
Schema::create('utility_bills', function (Blueprint $table) {
    $table->id();
    $table->foreignId('utility_customer_id')->constrained('utility_customers')->cascadeOnDelete();
    $table->string('periode', 7);                 // "YYYY-MM"
    $table->decimal('jumlah_tagihan', 15, 2);
    $table->string('nomor_tagihan')->nullable();  // nomor referensi tagihan
    $table->date('tanggal_jatuh_tempo');
    $table->date('tanggal_bayar')->nullable();
    $table->integer('meter_awal')->nullable();    // opsional
    $table->integer('meter_akhir')->nullable();   // opsional
    $table->text('keterangan')->nullable();
    $table->timestamps();

    $table->unique(['utility_customer_id', 'periode']);
    $table->index(['tanggal_jatuh_tempo', 'tanggal_bayar']);
});
```

### 4.3 Permission

Migration + seeder `akses_utilities` → role `superadmin` + `manager`.

---

## 5. Data Mapping

### 5.1 Status tagihan (derived accessor di model `UtilityBill`)

```php
// prioritas: lunas > telat > mendekati > belum
if ($this->tanggal_bayar) return 'lunas';
if ($this->tanggal_jatuh_tempo < now()->toDateString()) return 'telat';
if ($this->tanggal_jatuh_tempo <= now()->addDays(3)->toDateString()) return 'mendekati';
return 'belum';
```

Warna: `lunas`=hijau, `telat`=merah, `mendekati`=kuning, `belum`=abu.

### 5.2 Summary dashboard

- Total tagihan bulan berjalan per jenis (PLN/PDAM/TELKOM).
- Jumlah & nominal tagihan `belum` / `telat` / `lunas`.
- Per project: total tagihan + yang telat.

---

## 6. UI/UX

### Route (routes/accounting.php atau file baru routes/utilities.php)

```
GET  /utilities                       → dashboard ringkas
GET  /utilities/bills                 → list tagihan (DataTables) + filter periode/jenis/status
POST /utilities/bills/{bill}/mark-paid → tandai lunas (tanggal bayar + nomor ref)
GET  /utilities/bills/create          → input tagihan (per ID pelanggan)
POST /utilities/bills                 → simpan tagihan
POST /utilities/bills/copy-last-month → copy tagihan periode lalu ke periode baru
GET  /utilities/customers             → master ID pelanggan (CRUD resource)
```

Semua di-gate `permission:akses_utilities`.

### Layout

```
Sidebar → Utilities (menu terpisah, sejajar Accounting)
  ├── Dashboard
  ├── Tagihan
  └── ID Pelanggan
```

- **Dashboard**: KPI cards (total tagihan bulan ini, telat, belum, lunas) + tabel ringkas per jenis utilitas.
- **Tagihan**: DataTables + filter (periode, jenis utilitas, status, project). Kolom: ID Pelanggan, Jenis, Periode, Jumlah, Jatuh Tempo, Status (badge warna), Aksi (tandai lunas).
- **ID Pelanggan**: CRUD (jenis, id_pelanggan, nama, lokasi, project, akun).
- **Tandai lunas**: modal input tanggal bayar + nomor referensi.
- **Copy bulan lalu**: tombol → pilih periode sumber → duplikat ke periode baru (hanya yang belum lunas, atau semua? default: semua).

---

## 7. Models & Files

- `app/Models/UtilityCustomer.php`
- `app/Models/UtilityBill.php`
- `app/Http/Controllers/Utilities/UtilityDashboardController.php`
- `app/Http/Controllers/Utilities/UtilityBillController.php`
- `app/Http/Controllers/Utilities/UtilityCustomerController.php`
- `routes/utilities.php` (require di web.php, dalam auth group)
- Views di `resources/views/utilities/`
- Migration + seeder permission.

---

## 8. Risks

- **Periode sebagai string "YYYY-MM"** — harus konsisten format; validasi saat input.
- **Copy bulan lalu** bisa buat tagihan duplikat — mitigasi unique constraint (utility_customer_id, periode) → skip yang sudah ada.
- **Status derived** — pastikan timezone konsisten (Asia/Makassar) untuk hitung `telat`.
- **Jenis utilitas baru di masa depan** — pakai string (bukan enum DB) biar fleksibel tambah jenis.
- **OCR akurasi** — AI bisa salah baca nominal/idpel; mitigasi: preview confirm-before-save + confidence rendah ditandai.

---

## 9. OCR Upload Struk "Daftar Tagihan Kolektif" (Fase 2)

Upload gambar struk tagihan kolektif (PLN/PDAM/TELKOM dari PPOB) di `/utilities/bills`, extract N tagihan sekaligus via AI (OpenRouter vision), lalu masukkan ke daftar tagihan bulanan.

### Flow
1. User klik "Upload Struk" → pilih **jenis utilitas** + **periode** (default bulan berjalan) + upload gambar.
2. AI extract `bills[]` = `{idpel, nama, jumlah}` dari gambar.
3. Tampil **preview** (confirm-before-save): tabel editable (idpel, nama, jumlah, jatuh tempo) + status match (existing customer / baru).
4. User konfirmasi → simpan semua tagihan (`tanggal_bayar = null`, status **belum**), auto-create `utility_customers` untuk idpel baru.

### Keputusan
| Item | Keputusan |
|---|---|
| Status | **belum bayar** (tanggal_bayar = null) — upload di awal bulan, belum lunas |
| Jenis utilitas | **selector** di form upload |
| Periode | default dari **timestamp struk** (bisa override) |
| ID pelanggan baru | **auto-create** customer (idpel + nama dari struk + jenis + project dari form) |
| Konfirmasi | **preview confirm-before-save** |
| Tanggal jatuh tempo | default **end-of-month** periode (editable di preview) |

### Data Mapping
- `idpel` → `utility_customers.id_pelanggan` (match; create kalau belum ada).
- `nama` → nama customer (dipakai saat create baru).
- `jumlah` → `utility_bills.jumlah_tagihan`.
- `produk` ("PLN POSTPAID") → konfirmasi `jenis_utilitas`.

### Teknis
- Tambah method `extractUtilityBillsFromImageBase64()` di `OpenRouterService` (pola `extractReceiptFromImageBase64`), model vision `bankStatementModel` (gemini-3-flash-preview).
- Service baru `UtilityBillParserService` (pola `BankStatementParserService`): read base64 → OpenRouter → return `bills[]`.
- Controller: `UtilityBillController@upload` (render form) + `@parseUpload` (extract → preview) + `@storeUpload` (persist semua).
