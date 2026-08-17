# Spec — Accounting Manager Dashboard

> Feature: dashboard pemantauan untuk Manager Accounting (Iwan).
> Branch: `feature/accounting-manager-dashboard`
> Status: **Spec disetujui** — siap implement.

---

## 1. Goal

Memberi Manager Accounting satu halaman untuk memantau kesehatan finansial perusahaan secara cepat:

- **Saldo kas** (posisi kas & bank)
- **Outstanding advance** (advance yang belum dipertanggungjawabkan)
- **Realisasi biaya** per project
- **Budget vs actual** (serapan anggaran) per project

Semua bisa di-drill-down ke daftar dokumen untuk tindak lanjut.

---

## 2. Scope

### In Scope

- 1 halaman dashboard baru + 1 permission baru + 1 route baru.
- KPI cards global (saldo kas, outstanding advance, realisasi bulan berjalan, total anggaran aktif).
- Trend chart realisasi vs advance per bulan (Chart.js).
- Tabel komparatif per project.
- Drill-down per project ke daftar dokumen.

### Out of Scope

- Edit data dari dashboard (murni read-only).
- Integrasi e-Filing / SAP (nanti, terpisah).
- Mobile-specific layout (tetap responsif via Tailwind).
- Ekspor/print (bisa ditambah nanti).

---

## 3. Tech Decisions

| Item | Keputusan |
|---|---|
| Framework | Laravel 10 (Blade + Tailwind + AdminLTE), mengikuti konvensi existing |
| Chart | Chart.js (sudah dipakai dashboard existing) |
| Data source | Eloquent + `DB::raw` aggregate, read-only ke tabel existing (TANPA perubahan schema) |
| Project dimension | `anggarans.rab_project` (project yg menikmati biaya) |
| Saldo kas | `SUM(accounts.balance)` WHERE `type_id IN (1, 2)` |
| Outstanding advance | `payreqs` WHERE `type = 'advance'` DAN tidak punya realization |
| Cache | `Cache::remember(..., 300)` untuk KPI (5 menit) |
| Permission | `view_accounting_manager_dashboard` (migration + seeder) |

---

## 4. DB Changes

**Tidak ada perubahan schema tabel** — dashboard ini read-only.

Hanya perlu **1 migration + 1 seeder** untuk permission baru:

```
database/migrations/xxxx_create_view_accounting_manager_dashboard_permission.php
database/seeders/AccountingManagerDashboardPermissionSeeder.php
```

Permission: `view_accounting_manager_dashboard`.

---

## 5. Data Mapping (presisi)

### 5.1 Saldo kas

```php
Account::whereIn('type_id', [1, 2])->sum('balance'); // 1=bank, 2=cash
```

Bisa dipecah per rekening: `->groupBy('account_number')` → list per rekening + total.

### 5.2 Outstanding advance

**Definisi (dari Iwan):** payreqs `type = 'advance'` yang **belum punya realization sama sekali** (bukan realisasi sebagian).

```php
Payreq::where('type', 'advance')
    ->whereDoesntHave('realization')
    ->sum('amount');
```

Group by project: resolve project via `rab_id → anggarans.rab_project`.

### 5.3 Realisasi biaya per project

```php
RealizationDetail::query()
    ->join('anggarans', 'anggarans.id', '=', 'realization_details.rab_id')
    ->whereYear('realization_details.expense_date', $year)
    ->whereMonth('realization_details.expense_date', $month)
    ->groupBy('anggarans.rab_project')
    ->selectRaw('anggarans.rab_project as project, SUM(realization_details.amount) as total')
    ->get();
```

Breakdown by account (COA): `realization_details.account_id → accounts.account_number/account_name`.

### 5.4 Budget vs actual

```php
// Budget per project
Anggaran::where('is_active', 1)
    ->groupBy('rab_project')
    ->selectRaw('rab_project, SUM(amount) as budget, SUM(balance) as sisa');

// Serapan % = realisasi / budget * 100
```

### 5.5 Resolusi nama project

`rab_project` adalah code (string). Resolve ke nama via tabel `projects` (join `projects.code = anggarans.rab_project`), fallback ke raw code jika tidak ketemu.

---

## 6. UI/UX

### Route

```
GET /accounting/manager-dashboard
  → App\Http\Controllers\Accounting\AccountingManagerDashboardController@index
  → middleware: permission:view_accounting_manager_dashboard
```

### Layout halaman (`resources/views/accounting/manager-dashboard/index.blade.php`)

```
┌─────────────────────────────────────────────────────────┐
│  Manager Accounting Dashboard          [Bulan ▾] [Tahun ▾] │
├─────────────────────────────────────────────────────────┤
│  [KPI: Saldo Kas]  [KPI: Outstanding Advance]           │
│  [KPI: Realisasi Bulan Ini]  [KPI: Anggaran Aktif]       │
├─────────────────────────────────────────────────────────┤
│  Trend: Realisasi vs Advance (bar/line chart, 12 bulan)  │
├─────────────────────────────────────────────────────────┤
│  Tabel per project:                                      │
│  Project | Outstanding Adv | Realisasi | Budget | Serap% │
│  (klik baris → drill-down)                               │
└─────────────────────────────────────────────────────────┘
```

### Drill-down (klik project)

Modal/halaman detail project berisi:
- List advance outstanding (payreq_no, requestor, amount, tanggal)
- List realisasi by account (account, amount, expense_date)

### Visual

- KPI cards: angka besar + label, pakai gaya AdminLTE `small-box`/`info-box` existing.
- Serapan % pakai color coding: hijau (<80%), kuning (80–100%), merah (>100% / over budget).
- Format rupiah: `number_format(..., 2)` konsisten dengan existing.

---

## 7. API Endpoints

Semua data via route yang sama (blade-rendered) + endpoint DataTables JSON untuk drill-down:

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/accounting/manager-dashboard` | Render halaman + KPI + chart + tabel |
| GET | `/accounting/manager-dashboard/project/{project}/advances` | DataTables JSON: advance outstanding per project |
| GET | `/accounting/manager-dashboard/project/{project}/realizations` | DataTables JSON: realisasi by account per project |

---

## 8. Nuances & Edge Cases (PENTING)

1. **Tiga field "project" berbeda** — dipakai `anggarans.rab_project` (menikmati biaya), BUKAN `anggarans.project` (creator) ataupun `payreqs.project`. `realization_details` juga punya `project` sendiri — abaikan, gunakan `rab_id → anggarans.rab_project` agar konsisten.

2. **`rab_id` nullable** — payreq/realization yang tidak ter-link anggaran tidak punya `rab_project`. Kelompokkan sebagai "Tanpa Project" / fallback ke `payreqs.project`. **Perlu konfirmasi Iwan** kalau jumlahnya signifikan.

3. **Outstanding advance = advance tanpa realization sama sekali** — advance yang sudah punya realization (meski nominal kurang) TIDAK masuk outstanding. Sesuai definisi Iwan.

4. **Saldo kas global vs per project** — saldo kas ditampilkan global (posisi kas perusahaan), TIDAK dipecah per project (kas terpusat). Bisa dipecah per rekening.

5. **Periode default** — bulan berjalan; realisasi dihitung per `expense_date`, advance per `outgoing_date` (konsisten dengan dashboard existing).

---

## 9. Risks

- **Performa query aggregate** — tabel `payreqs`/`realization_details` besar; mitigasi: cache 5 menit + index existing (`rab_id`, `expense_date`, `project`).
- **Ambiguity field project** — mitigasi: fixed ke `rab_project`, fallback jelas, konfirmasi Iwan untuk data tanpa rab_id.
- **Definisi outstanding advance berubah** — nanti kalau Iwan mau masukkan realisasi-sebagian juga, tinggal ubah query `whereDoesntHave` → `where realisasi < amount`.
