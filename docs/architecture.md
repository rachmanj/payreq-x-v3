# Application architecture (internal)

This document summarizes structural flows that extend Laravel defaults. Keep it scoped and factual—defer rationale to `docs/decisions.md`.

## Anggaran / RAB (budget requests)

### User-payreq surface (`routes/user_payreqs.php`)

- List/detail/edit/create views live under `resources/views/user-payreqs/anggarans/`.
- **`App\Http\Controllers\UserPayreq\UserAnggaranController`** handles drafts/submit wiring (`ApprovalPlanController`), DataTables JSON endpoints, and delegates monetary math to **`App\Services\AnggaranReleaseService`**.
- **`App\Http\Requests\UserPayreq\ProcessAnggaranRequest`** validates create/update in one POST endpoint (`proses`) including conditional rules per `rab_type`.
- **`App\Policies\AnggaranPolicy`**: `view` expresses listing/leasing visibility aligned with merged scopes (`superadmin`/`admin`, cashier-by-project, creator, approved project/department scoping); **`editThroughPayreq`** restricts edits to creator + `editable` + prior visibility.

### Reports surface (`routes/reports.php`, prefix `reports/anggaran`)

- **Dashboard**: `GET reports/anggaran/dashboard` — summarized KPIs for rows visible under the same project/status conventions as the RAB DataTables listing (`reports/anggaran.dashboard`).
- **Listing/DataTables**: `reports/anggaran/data` — applies active/inactive and admin-vs-non-admin filters consistently with bulk operations (see `Reports\AnggaranController::authorizedBulkIds`).
- **Recalculate**: `POST reports/anggaran/recalculate` — authorization **`recalculate_release`** (Spatie). Runs **`AnggaranReleaseService::syncAllApprovedStoredTotals`** then **`flushAllReportingCaches`** so caches aligned after manual rebuild.
- **Bulk activate/deactivate**: `POST update_many` / `activate_many` — authorization **`anggaran_bulk_activate_deactivate`**. IDs requested by the client are intersected with server-side scoped IDs before mutation.

### Shared domain logic (`AnggaranReleaseService`)

- **`effectiveRabIdForPayqueries`**: uses **`old_rab_id`** when set so migrated rows reconcile payreq linkage consistently across User-payreq progress UI and reporting totals.
- **`calculateTotalRelease`**, **`progressSummary`**, **`syncStoredTotals`**, **`syncAllApprovedStoredTotals`**: single pipeline for stored **`balance`** / **`persen`** and displayed breakdown tables.

### Operational tooling

- **`php artisan anggaran:sync-release-totals`** — hourly scheduler (`App\Console\Kernel`) re-syncs approved rows and clears **listing** cache helpers (`flushListingCaches`) without iterating every cached detail key (heavy deployments reserve full **`flushAllReportingCaches`** for manual POST Recalc).

- **`php artisan anggaran:inactivate-many --last-month`** — bulk **`is_active = 0`** for **approved** rows whose **`date`** lies in the prior calendar month (optional **`--project=`**, **`--dry-run`**). **`type = buc`** rows are always skipped. Mirrors Reports **Inactivate Many** behaviour without selecting checkboxes. **Scheduled:** 1st of each month at **05:00** app timezone (`Kernel`).

### Tests

- `tests/Feature/AnggaranReportsTest.php` covers unauthorized POST paths vs permission gates.

## User-payreq: realization / reimbursement detail lines (`realization_details`)

- **Controllers / requests:** Realization flows use **`UserRealizationController`** + **`StoreRealizationDetailRequest`** / **`UpdateRealizationDetailRequest`**; reimbursement detail CRUD uses **`PayreqReimburseController`** with the **same** Form Requests (shared rules).
- **Fleet, dates, HM:** Concern **`App\Http\Requests\Concerns\ValidatesRealizationDetailFleet`**; cross-day HM monotonicity **`App\Support\RealizationDetailOdometerMonotonicityValidator`**.
- **`expense_date` (SQL `DATE`):**
  - **JSON / `toArray()`:** **`RealizationDetail::attributesToArray()`** overrides serialization so **`expense_date`** is **`Y-m-d`** (prefers **`getRawOriginal('expense_date')`**). Avoids **UTC ISO8601** midnight shifting the calendar day when the front end uses **`String(...).substring(0, 10)`**.
  - **Payload / validation helpers:** Trait methods **`canonicalDateOnlyString`** (persist normalized **`YYYY-MM-DD`**) and **`parseExpenseDateStartOfDay`** (“not in the future” vs **`Carbon::now(config('app.timezone'))`**).
  - **Business rule:** Expense date **must not be after today**; there is **no** minimum tied to **payreq approval** date (removed).
- **PHP before 8.2:** The fleet field list is **`fleetInputFieldNames()`**, not a trait **`const`** (traits cannot declare constants before PHP 8.2).

See **ADR-PAYREQ-01** (shared reimburse/realization validation), **ADR-PAYREQ-02**, **ADR-COMPAT-01**.

## Related docs

- `docs/decisions.md` — ADR-ANGGRAN-01 (RAB release consolidation & tooling), ADR-PAYREQ-01/02, ADR-COMPAT-01.
