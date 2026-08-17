# Spec — Accounting Manager Dashboard (v3 FINAL)

> Feature: dashboard pemantauan untuk Manager Accounting (Iwan).
> Branch: `feature/accounting-manager-dashboard`
> Status: **Spec final — tervalidasi data production (dump 17 Aug 2026).**

---

## 1. Goal

Manager Accounting memantau **DUA SISI** arus kas:

1. **Sisi Dana Beredar (Funding)** — dana yang masih beredar di requestor (outstanding advance + aging) dan kebutuhan dana untuk payreq yang belum dicairkan.
2. **Sisi Realisasi Biaya (Expense)** — dana yang sudah direalisasikan ke tiap project atas biaya tertentu.

---

## 2. Scope

### In Scope
- 1 halaman dashboard + 1 permission + 1 route.
- **Section A (Funding)**: outstanding advance per project + aging + kebutuhan dana (belum paid).
- **Section B (Expense)**: realisasi biaya per project, breakdown by akun COA.
- Saldo kas global.
- Drill-down per project ke daftar dokumen.
- Trend chart realisasi vs advance per bulan.

### Out of Scope
- Edit data (read-only).
- Integrasi e-Filing/SAP.
- Export/print.

---

## 3. Tech Decisions (final)

| Item | Keputusan |
|---|---|
| Framework | Laravel 10 (Blade + Tailwind + AdminLTE) + Chart.js |
| **Funding project dim** | `payreqs.project` (0 null di data) |
| **Expense project dim** | `realization_details.project` (0 null di data) |
| Saldo kas | `SUM(accounts.balance)` WHERE `type_id IN (1,2)` |
| Outstanding advance | payreq `type='advance'`, tanpa realization, **sudah dicairkan** (ada outgoing) |
| Aging basis | `outgoings.outgoing_date` (tanggal dana dicairkan) |
| Kebutuhan dana (belum paid) | payreq `status IN ('submitted','approved','draft','revise')` DAN belum ada outgoing |
| Parsial | **DIBUANG** — 0 record di production (payment all-or-nothing) |
| Cache | `Cache::remember(..., 300)` |
| Permission | `view_accounting_manager_dashboard` |

---

## 4. DB Changes

Tidak ada perubahan schema. Hanya 1 migration + 1 seeder permission:
- `database/migrations/xxxx_create_view_accounting_manager_dashboard_permission.php`
- `database/seeders/AccountingManagerDashboardPermissionSeeder.php`

Permission: `view_accounting_manager_dashboard`.

---

## 5. Data Mapping (tervalidasi data)

### 5.1 Saldo kas (global)
```php
Account::whereIn('type_id', [1, 2])->sum('balance'); // 1=bank, 2=cash
```

### 5.2 Outstanding advance (dana beredar)
Payreq advance tanpa realization, yang SUDAH dicairkan (punya outgoing).
```php
Payreq::where('type','advance')
  ->whereHas('outgoings')                    // sudah dicairkan
  ->whereDoesntHave('realization')           // belum direalisasi
  ->whereNotIn('status', ['canceled','rejected'])
  ->groupBy('project')                        // payreqs.project
  ->selectRaw('project, COUNT(*) cnt, SUM(amount) total');
```
**Aging** per payreq = `DATEDIFF(NOW(), MIN(outgoings.outgoing_date))`. Bucket: 0-30 / 31-60 / 61-90 / >90 hari.

### 5.3 Kebutuhan dana (belum paid)
Payreq yang belum dicairkan sama sekali (belum ada outgoing), status menunggu bayar.
```php
Payreq::whereIn('status', ['submitted','approved','draft','revise'])
  ->whereDoesntHave('outgoings')
  ->groupBy('project')
  ->selectRaw('project, COUNT(*) cnt, SUM(amount) total');
```

### 5.4 Sisi Expense — Realisasi per project
```php
RealizationDetail::whereYear('expense_date', $year)
  ->whereMonth('expense_date', $month)
  ->groupBy('project')                        // realization_details.project
  ->selectRaw('project, SUM(amount) total');
```
Breakdown by akun: `account_id → accounts.account_number/account_name`.

### 5.5 Budget vs actual (opsional)
```php
Anggaran::where('is_active',1)->groupBy('rab_project')
  ->selectRaw('rab_project, SUM(amount) budget, SUM(balance) sisa');
```
> Catatan: budget pakai `rab_project`, realisasi pakai `realization_details.project` — dua dimensi beda, TAMPILKAN TERPISAH (lihat §9).

---

## 6. UI/UX

### Route
```
GET /accounting/manager-dashboard
  → AccountingManagerDashboardController@index
  → middleware: permission:view_accounting_manager_dashboard
```

### Layout
```
[KPI: Saldo Kas] [KPI: Outstanding Advance] [KPI: Kebutuhan Dana] [KPI: Realisasi]
SECTION A — Dana Beredar (Funding)
  Tabel per project (payreqs.project):
  Project | Outstanding Adv | Aging bucket | Belum Paid
  → drill-down list advance + list belum-paid
SECTION B — Realisasi Biaya (Expense)
  Tabel per project (realization_details.project):
  Project | Realisasi Bulan | YTD | Top Akun
  → drill-down list realisasi by akun
```

### Visual
- KPI cards AdminLTE. Aging color: hijau <30, kuning 30-60, merah >60 hari.
- Rupiah `number_format(..., 2)`.

---

## 7. API Endpoints
| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/accounting/manager-dashboard` | Halaman + KPI + kedua tabel |
| GET | `/accounting/manager-dashboard/project/{project}/advances` | DataTables JSON: advance outstanding |
| GET | `/accounting/manager-dashboard/project/{project}/unpaid` | DataTables JSON: belum paid |
| GET | `/accounting/manager-dashboard/project/{project}/realizations` | DataTables JSON: realisasi by akun |

---

## 8. Validated Facts (dari dump production)
- `payreqs.project` & `realization_details.project` = 0 null.
- `payreqs.rab_id` null 63%, `realization_details.rab_id` null 72% → `rab_project` tidak dipakai.
- Outstanding advance = 103 payreqs (Rp 859,66 jt): 52 sudah cair, 51 belum.
- Kebutuhan dana (approved+draft+submitted+revise) = 96 payreqs (Rp 1,08 M).
- Aging >90 hari = 17 payreqs (Rp 217,8 jt).
- "Parsial" tidak terjadi (0 record).

---

## 9. Risks
- **Dua dimensi project (funding vs expense) beda** — jangan dicampur; tampilkan section terpisah.
- **Budget (rab_project) vs realisasi (realization_details.project)** bisa mismatch — tampilkan terpisah atau beri disclaimer.
- **Performa** — cache 5 menit + index (`payreqs.project`, `payreqs.status`, `realization_details.project`, `realization_details.expense_date`).
