# Spec — Accounting Manager Dashboard (v2)

> Feature: dashboard pemantauan untuk Manager Accounting (Iwan).
> Branch: `feature/accounting-manager-dashboard`
> Status: **Spec disetujui (v2 — dua sisi)** — siap implement.

---

## 1. Goal

Manager Accounting memantau **DUA SISI** arus kas perusahaan:

1. **Sisi Dana Beredar (Funding)** — berapa dana masih beredar di requestor (outstanding advance + aging), dan berapa dana lagi dibutuhkan untuk payreq yang belum paid/parsial. Untuk perencanaan **pemenuhan kebutuhan dana cash**.
2. **Sisi Realisasi Biaya (Expense)** — seberapa besar dana sudah dikeluarkan/direalisasikan ke tiap project atas biaya tertentu.

---

## 2. Scope

### In Scope

- 1 halaman dashboard + 1 permission + 1 route.
- **Section A (Funding)**: KPI + tabel outstanding advance per project (dengan aging) + kebutuhan dana payreq belum paid/parsial.
- **Section B (Expense)**: KPI + tabel realisasi biaya per project (breakdown by akun COA).
- Saldo kas global (posisi kas & bank).
- Drill-down per project ke daftar dokumen.
- Trend chart realisasi vs advance per bulan.

### Out of Scope

- Edit data (read-only).
- Integrasi e-Filing / SAP.
- Export/print (nanti).

---

## 3. Tech Decisions

| Item | Keputusan |
|---|---|
| Framework | Laravel 10 (Blade + Tailwind + AdminLTE) |
| Chart | Chart.js |
| **Project dimension — Funding** | `payreqs.project` (project PEMBUAT payreq) |
| **Project dimension — Expense** | `realization_details.project` (project DIKENAKAN biaya) |
| Saldo kas | `SUM(accounts.balance)` WHERE `type_id IN (1, 2)` |
| Outstanding advance | `payreqs` WHERE `type='advance'` DAN tanpa realization |
| Payreq belum paid | `payreqs.status` belum `paid` (submitted/approved) |
| Payreq parsial | `SUM(outgoings.amount) < payreq.amount` |
| Cache | `Cache::remember(..., 300)` (5 menit) |
| Permission | `view_accounting_manager_dashboard` |

---

## 4. DB Changes

**Tidak ada perubahan schema tabel** — read-only.

Hanya **1 migration + 1 seeder** permission baru:
- `database/migrations/xxxx_create_view_accounting_manager_dashboard_permission.php`
- `database/seeders/AccountingManagerDashboardPermissionSeeder.php`

Permission: `view_accounting_manager_dashboard`.

---

## 5. Data Mapping (presisi)

### 5.1 Saldo kas (global)

```php
Account::whereIn('type_id', [1, 2])->sum('balance'); // 1=bank, 2=cash
```

### 5.2 Sisi Funding — Outstanding Advance (dana beredar di requestor)

**Definisi:** payreq `type='advance'` yang **belum punya realization sama sekali**.

```php
Payreq::where('type', 'advance')
    ->whereDoesntHave('realization')
    ->groupBy('project')            // payreqs.project
    ->selectRaw('project, COUNT(*) as count, SUM(amount) as total');
```

**Aging:** hari sejak dana keluar (`outgoing_date`) / sejak `submit_at` sampai sekarang. *(perlu konfirmasi Iwan — lihat §8)*

### 5.3 Sisi Funding — Payreq Belum Paid / Parsial (kebutuhan dana)

```php
// Belum paid: approved tapi belum dicairkan
Payreq::whereIn('status', ['submitted', 'approved'])->sum('amount');

// Parsial: sudah ada outgoing tapi sum < amount
Payreq::whereHas('outgoings')
    ->get()
    ->filter(fn($p) => $p->outgoings->sum('amount') < $p->amount);
```

Group by `payreqs.project`.

### 5.4 Sisi Expense — Realisasi per project

```php
RealizationDetail::query()
    ->whereYear('expense_date', $year)
    ->whereMonth('expense_date', $month)
    ->groupBy('project')             // realization_details.project
    ->selectRaw('project, SUM(amount) as total');
```

Breakdown by akun: `realization_details.account_id → accounts.account_number/account_name`.

### 5.5 Budget vs Actual (serapan) — OPSIONAL, per project

```php
Anggaran::where('is_active', 1)
    ->groupBy('rab_project')
    ->selectRaw('rab_project, SUM(amount) as budget, SUM(balance) as sisa');
// serapan = realisasi / budget
```

> Catatan: budget vs actual pakai `rab_project`, sementara realisasi pakai `realization_details.project`. Keduanya mungkin tidak 1:1 — flag sebagai risiko §9.

---

## 6. UI/UX

### Route

```
GET /accounting/manager-dashboard
  → AccountingManagerDashboardController@index
  → middleware: permission:view_accounting_manager_dashboard
```

### Layout (`resources/views/accounting/manager-dashboard/index.blade.php`)

```
┌──────────────────────────────────────────────────────────┐
│  Manager Accounting Dashboard          [Bulan ▾] [Tahun ▾] │
├──────────────────────────────────────────────────────────┤
│  [KPI: Saldo Kas]  [KPI: Outstanding Advance]             │
│  [KPI: Kebutuhan Dana Belum Paid]  [KPI: Realisasi Bulan] │
├──────────────────────────────────────────────────────────┤
│  SECTION A — Dana Beredar (Funding)                       │
│  Tabel per project (payreqs.project):                     │
│  Project | Outstanding Adv | Aging (rata/hari) | Belum Paid │
│  → klik → drill-down list advance outstanding             │
├──────────────────────────────────────────────────────────┤
│  SECTION B — Realisasi Biaya (Expense)                    │
│  Tabel per project (realization_details.project):         │
│  Project | Realisasi Bulan Ini | YTD | Top Akun           │
│  → klik → drill-down list realisasi by akun               │
└──────────────────────────────────────────────────────────┘
```

### Visual

- KPI cards gaya AdminLTE existing.
- Aging pakai color coding: hijau (<30 hari), kuning (30–60), merah (>60 hari).
- Format rupiah `number_format(..., 2)`.

---

## 7. API Endpoints

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/accounting/manager-dashboard` | Render halaman + KPI + kedua tabel |
| GET | `/accounting/manager-dashboard/project/{project}/advances` | DataTables JSON: advance outstanding per project |
| GET | `/accounting/manager-dashboard/project/{project}/realizations` | DataTables JSON: realisasi by akun per project |

---

## 8. Nuances & Open Questions

1. **Dua field project (sudah dikonfirmasi Iwan):** Funding pakai `payreqs.project`, Expense pakai `realization_details.project`. TIDAK pakai `anggarans.rab_project` lagi.

2. **Aging basis** — dihitung dari `outgoing_date` (tanggal dana keluar) atau `submit_at` (tanggal submit)? *(perlu konfirmasi Iwan)*

3. **Definisi "belum paid"** — apakah cukup `status` (`submitted`/`approved`), atau perlu `outgoing_date IS NULL` juga? Verifikasi dengan data dump.

4. **Definisi "parsial"** — payreq yang `SUM(outgoings) < amount`. Perlu dicek apakah ada field status khusus (mis. `partial`) atau dihitung manual.

5. **`rab_id` null** — untuk sisi funding (payreq tanpa rab_id) `payreqs.project` tetap ada, jadi aman. Untuk budget vs actual, anggaran tanpa `rab_project` dikelompokkan "Tanpa Project".

---

## 9. Risks

- **Dua project field bisa tidak sinkron** — realisasi dibebankan ke `realization_details.project`, sementara budget di `rab_project`; serapan budget vs realisasi bisa mismatch. Mitigasi: tampilkan keduanya terpisah, jangan dicampur.
- **Performa query aggregate** — cache 5 menit + index existing.
- **Definisi status paid/parsial** — harus divalidasi dengan data dump production sebelum finalisasi query.
