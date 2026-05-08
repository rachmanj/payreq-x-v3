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

## User-payreq: advance budget modes (legacy vs multi-allocation)

### Data model

- **`payreqs.budget_link_mode`**: **`legacy`** (default) or **`multi_allocation`** (stored string; see **`PayreqBudgetLinkMode`**).
- **`payreq_anggaran_allocations`**: one row per allocation — **`payreq_id`**, **`anggaran_id`**, **`amount`**, **`remarks`**, **`sort_order`**. Loaded via **`Payreq::anggaranAllocations()`** (ordered by **`sort_order`**, **`id`**).

### Behaviour

- **Legacy:** Existing behaviour — principal **`rab_id`** on **`payreqs`** ties the draft to **one** anggaran row; **`amount`** is the payreq total.
- **Multi-allocation (**`Payreq::isAdvanceMultiBudget()`**): **`allocations`** array POSTed from advance **create/edit**; sum of row **`amount`** must equal header **`amount`**. **`ProcessAdvancePayreqRequest`** validates rows; **`PayreqAdvanceController::proses`** saves payreq + allocation rows transactionally.
- **`Gate::rab_select`**: **`ProcessAdvancePayreqRequest::prepareForValidation()`** forces **`budget_link_mode = legacy`** and clears **`allocations`** when denied; UI should match (advance create uses **`rab_select`** for budget-form radios vs hidden legacy-only).

### Locks and realization

- On **edit**, requested **`budget_link_mode`** must match the stored mode (validated in **`ProcessAdvancePayreqRequest::withValidator`**).
- Multi advance realizations attach **`rab_id`** on **`realization_details`** chosen from allocation anggaran; warnings via **`PayreqRealizationBudgetWarningService`**. Add/edit realization detail Blades (**`resources/views/user-payreqs/realizations/add_details.blade.php`**) expose per-line **`rab_id`** selects with labels including distinct **`rab_no`**.

### User-facing surfaces

- **Advance create/edit:** **`resources/views/user-payreqs/advance/create.blade.php`**, **`edit.blade.php`** — budget radios, allocation grid, JS total sync (**`advanceCanSelectRab`**). **Payreq No on edit:** use **`readonly`**, **not **`disabled`**, so **`payreq_no`** is included in **`POST`** to **`advance.proses`**.
- **My payreq detail:** **`GET user-payreqs/{id}`** — **`UserPayreqController::show`** eager-loads **`anggaranAllocations.anggaran`**; **`user-payreqs/show`** replaces single **RAB** line with **`user-payreqs/partials/show_advance_allocation_table`** when multi-row data exists.
- **Print PDF:** **`UserPayreqController::print`** — advance templates include **`advance/partials/print_budget_table_body`** so line items repeat per allocation (default + signed + **`022c`** variants).

See **ADR-PAYREQ-03**.

## Document overdue extensions

### Data model

- Table **`overdue_extensions`**: links a **`document_type`** (**`payreq`** | **`realization`**) + **`document_id`** to **`user_id`** (requestor), **`current_due_date`**, **`requested_due_date`**, **`reason`**, **`status`** (**`pending`** | **`approved`** | **`rejected`**), optional reviewer metadata.
- **`App\Models\OverdueExtension`**: **`eligibleProjects()`** returns **`['000H', 'APS']`**; scopes **`pending()`** / **`approved()`** / **`rejected()`**.

### User submission

- **Listing:** **`GET user-payreqs/overdue-documents`** (`UserPayreqController::overdueDocuments`) — overdue payreqs/realizations with extension counts and modals (`resources/views/user-payreqs/overdue-documents.blade.php`, partial **`user-payreqs/partials/extension-request-modals.blade.php`**).
- **POST** **`document-overdue/extensions`** (`OverdueExtensionController::store`, validated by **`StoreOverdueExtensionRequest`**) — authenticated owner only; project must be eligible; **payreq**: **`type = advance`**, **`status = paid`**, **`due_date`** set; **realization**: **`status = approved`**, **`due_date`** set; **no second pending** row per document. Successful submit redirects to **`user-payreqs.index`** with flash success.
- **Overdue eligibility** for submit matches overdue listings: **`Carbon::parse($due_date)->lt(now())`** (same calendar-day semantics as **`due_date < now()`** in SQL-style comparisons — not “end of due date”).

### Approver UI and routes (`routes/web.php`, prefix **`document-overdue`**)

- **`GET document-overdue/extensions`** — DataTable index (**`OverdueExtensionController@index`**, **`can:approve_overdue_extension`** on **`index`** / **`data`**).
- **`PUT document-overdue/extensions/{extension}/approve|reject`** — **`approve_overdue_extension`** middleware on approve/reject; **`ReviewOverdueExtensionRequest`** on reject.
- Payreq/realization overdue screens retain **direct / bulk extend** actions (**`PayreqOverdueController`**, **`RealizationOverdueController`**) gated by **`can:approve_overdue_extension`** where applicable.
- Sidebar / menus: links **Approve overdue extensions** under Accounting (roles **`superadmin|admin|cashier`**) and Admin (**`akses_admin`**), using **`route('document-overdue.extensions.index')`**.

### Authorization

- Spatie permission **`approve_overdue_extension`** (seeded / **`RoleController`** ensures definition when editing roles).
- **`AuthServiceProvider::boot`**: **`Gate::before`** grants **`approve_overdue_extension`** when the user has role **`superadmin`** or **`admin`**, so approval surfaces work even if permission rows are missing from a synced role.

### Dashboard

- **`DashboardUserController::index`**: when **`auth()->user()->can('approve_overdue_extension')`**, passes **`pending_overdue_extension_count`** = **`OverdueExtension::query()->pending()->count()`**.
- **`resources/views/dashboard/row2.blade.php`**: stat card **Pending overdue extension requests**, link to **`document-overdue.extensions.index`**, attribute **`data-dashboard-pending-extension-requests`** for tests/DOM hooks. Wrapped in **`@can('approve_overdue_extension')`**.

### Layout note (extension modals elsewhere)

- **`templates/main.blade.php`** exposes **`@yield('modals')`** after the main wrapper so Bootstrap modals are not trapped inside nested markup; validation errors can surface via Toastr / reopen modal patterns on user overdue views as implemented.

### Announcements × PHPUnit (`Announcement::scopeCurrent`)

- **`scopeCurrent`** uses **`DATE_ADD(...)`** on MySQL for “active window” math; **SQLite** (PHPUnit **`phpunit.xml`** `:memory:`) uses a **`date(..., '+' || duration_days || ' days')`** expression so **`dashboard/index`** (which loads **`dashboard/announcements`**) does not 500 during feature tests.

### Tests

- **`tests/Feature/OverdueExtensionTest.php`** — permissions, submit/eligibility, approve flow, overdue-documents page, dashboard pending card visibility/count.

## Related docs

- `docs/decisions.md` — ADR-ANGGRAN-01 (RAB release consolidation & tooling), ADR-PAYREQ-01/02/**03**, ADR-COMPAT-01, **ADR-OVERDUE-EXT-01**.
