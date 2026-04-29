# System architecture (living document)

This file describes the **current** behavior of the application as implemented in code. Update it when structure or data flow changes.

## Cashier: PCBC (Physical cash reporting)

PCBC in this codebase serves **two related purposes**:

1. **Document uploads** — PDFs stored as `Dokumen` with `type = 'pcbc'`, file on disk, metadata includes `dokumen_date` (document date) and `project`.
2. **Structured counts** — Records in the `pcbcs` table (model `Pcbc`) for denomination entry, system vs physical vs SAP amounts, print formats.

**Weekly upload compliance (business rules)** applies to **(1)** only. Compliance uses `Dokumen.dokumen_date` to decide whether a file “covers” a given Monday–Sunday week in `Asia/Makassar`, and only rows with **`validation_status = validated`** count as a **qualifying** (official) PCBC for that week. **Pending** and **rejected** uploads do not satisfy compliance or the per-month dashboard grid. Projects listed in `config/pcbc_compliance.php` → `exception_project_codes` (APS, 026C, 023C) are exempt from the weekly rule and from sanctions.

### Key components

| Piece | Role |
|--------|------|
| `config/pcbc_compliance.php` | Timezone, exempt project codes |
| `App\Services\PcbcComplianceService` | Week boundaries, `getStatus()`, `isSanctioned()`, `shouldEnforceForUser()` |
| `App\Http\Middleware\SharePcbcComplianceForViews` | `view()->share('pcbcCompliance', …)` and `pcbcViolationSanctioned` for **all** Blade views in `web` stack (avoids only composing `templates.main`, which would leave child views like `cashier.pcbc.upload` without data) |
| `App\Http\Middleware\EnsurePcbcWeeklyCompliance` | Alias `pcbc.weekly_compliance` — blocks `cashier.approveds.*` and `cashier.incomings.*` when user is “sanctioned” |
| `components/pcbc-compliance-banner` | Bilingual (EN/ID) alert when `show_banner` is true |
| Spatie permission `see_pcbc_warning` | Required to show compliance messages in UI; not required for actual sanction enforcement on routes |
| Spatie permission `validate_pcbc_report` | May **validate** or **reject** a **pending** PCBC `Dokumen` (not required to read the upload list) |

Sanction: **no qualifying upload** in each of the **last two full calendar weeks** (W−1 and W−2) for the user’s `auth()->user()->project`. Qualifying = at least one `Dokumen` row (`type=pcbc`, **`validation_status=validated`**, matching `project`, `dokumen_date` in that week).

### PCBC PDF validation (state on `dokumens`)

| Column / field | Purpose |
|----------------|---------|
| `validation_status` | `pending` (new upload), `validated` (official for compliance and dashboard), `rejected` (not official; uploader may fix and resubmit) |
| `validated_at`, `validated_by` | Set when a validator approves or rejects; **rejector** is `validated_by` for audit |
| `rejection_reason` | Free text when status is `rejected` |

- **Grandfathering:** The migration defaults `validation_status` to `validated` so **existing** `dokumens` rows behave as already accepted; only **new** PCBC uploads from `PcbcController::upload` start as **pending**.
- **Re-validation:** `PcbcController::update` resets to **pending** (and clears validator fields) when the PCBC file, **PCBC date**, or **project** changes.
- **Routes:** `POST /cashier/pcbc/dokumen/{dokumen}/validate` and `…/reject` (names `cashier.pcbc.dokumen.validate|reject`) — `reject` requires `rejection_reason` (server- and client-side).
- **Upload list (DataTable):** Status column is rendered from `resources/views/cashier/pcbc/_validation_status_column.blade.php`. For **rejected** rows, **View reason** opens a read-only modal (reason, who rejected, when). `resources/views/cashier/pcbc/action.blade.php` shows the same reason in the **Edit** modal so uploaders see feedback when fixing a file.
- **Seeder / roles:** `database/seeders/ValidatePcbcReportPermissionSeeder.php`; `RoleController` includes `validate_pcbc_report` in “Data Upload & Import” and `ensureValidatePcbcReportPermissionExists()` on role create/edit.

```mermaid
flowchart TD
  U[Upload PDF] --> P[pending]
  P --> V[POST validate]
  P --> R[POST reject + reason]
  V --> OK[validated]
  R --> RJ[rejected]
  RJ --> E[Edit: new file or date or project]
  E --> P
  OK --> C[Qualifies for compliance + dashboard]
```

Enforcement of sanctions: users with `shouldEnforceForUser()` — not exempt, has `project`, and `can('akses_transaksi_cashier')`.

### PCBC upload DataTable

`Cashier\PcbcController@data` uses **Yajra `DataTables::of($query)`** on an **Eloquent query** (not `get()` + collection) with `->make(true)` so **server-side** sort/pagination are correct. Default client order is column index **2** (PCBC document date) **desc**. `orderColumn` for `dokumen_date` uses raw SQL `dokumen_date $1`. Display formatting uses `getRawOriginal('dokumen_date')` so model accessors do not break ordering. The table includes a **validation_status** column (`orderColumn` on `validation_status`); **raw** HTML is used for status badges and the rejection “View reason” control.

### UI notes

- **AdminLTE** (later CSS block) sets `.card-title { float: left; }` for card headers, which can cause headings and the following list to run together. The PCBC upload “Weekly report rules” heading intentionally **does not** use the `card-title` class on that `h5`.
- The upload tab compliance card shows “Set your user project…” when `getStatus()` is `null`, usually because `auth()->user()->project` is empty. After `SharePcbcComplianceForViews`, users with a project and `see_pcbc_warning` see real status in that card.

### Data flow (compliance for one request)

```mermaid
flowchart LR
  MW[SharePcbcComplianceForViews] -->|share| V[View data]
  V --> B[Banner + nav partials]
  S[PcbcComplianceService] --> MW
  R[Request user.project + Dokumen query] --> S
  WH[pcbc.weekly_compliance] -->|if sanctioned| RED[Redirect to PCBC upload]
```

## Related files

- Routes: `routes/cashier.php` — `pcbc.weekly_compliance` on `approveds` and `incomings` groups; PCBC `upload` POST, `dokumen/validate` + `dokumen/reject` POST, `data` GET, resource routes
- Menus: `resources/views/templates/partials/menu/cashier.blade.php`, `sidebar.blade.php` — use `$pcbcViolationSanctioned` for locking Ready to Pay / Incoming List
- Seeder: `database/seeders/SeePcbcWarningPermissionSeeder.php` — creates `see_pcbc_warning` and assigns to common roles
- Seeder: `database/seeders/ValidatePcbcReportPermissionSeeder.php` — creates `validate_pcbc_report` and assigns to superadmin/admin by default
- `RoleController` — includes `see_pcbc_warning` and `validate_pcbc_report` in “Data Upload & Import”; `ensurePcbcWarningPermissionExists()` and `ensureValidatePcbcReportPermissionExists()` on role create/edit
- `database/migrations/2026_04_25_030141_add_pcbc_validation_to_dokumens_table.php` — `validation_status`, `validated_at`, `validated_by`, `rejection_reason`

## Payreq: realization details vs reimbursement details (shared validation)

Both **realization** (`UserRealizationController`) and **reimbursement** (`PayreqReimburseController`) persist lines on **`realization_details`** linked to a **`Realizations`** row. Starting 2026-04, **store/update detail** validation is aligned:

| Concern | Implementation |
|--------|----------------|
| Fleet / fuel-service rules, LOT handling | Trait `App\Http\Requests\Concerns\ValidatesRealizationDetailFleet` via `StoreRealizationDetailRequest` / `UpdateRealizationDetailRequest` |
| `expense_date` vs today & payreq approval window | Same requests (`applyExpenseDateBusinessRules`) |
| HM (`km_position`) cross-day monotonicity per `unit_no` | `App\Support\RealizationDetailOdometerMonotonicityValidator` |
| Payload persistence (`expense_date`, HM, fleet fields, `rab_id`) | `realizationDetailPayload()` + create merge (`project`, `department_id`, `rab_id` from payreq where applicable) |

**Routing difference:** Realizations often update a detail via **`POST …/update_detail/{detail}`** (implicit route binding). Reimburse uses **`POST …/update_detail`** with **`realization_detail_id`** in the body only. `UpdateRealizationDetailRequest::resolveDetail()` loads by route `{detail}` **or** `realization_detail_id`; **`authorize()`** allows authenticated users through when no detail is resolved yet so missing IDs fail validation with **422**. **`prepareForValidation()`** merges `realization_detail_id` from a bound `RealizationDetail` route parameter when present.

**UI:** `resources/views/user-payreqs/reimburse/add_details.blade.php` mirrors realization patterns (expense date column, fleet row, LOTC checkbox when `lotc_detail` exists). **`PayreqReimburseController`** passes **`lotc_detail`** from `LotClaim::where('lot_no', $payreq->lot_no)` when `payreq->lot_no` is set.

**Print:** `resources/views/user-payreqs/reimburse/print_pdf.blade.php` includes an **Expense date** column per detail row.

**Print:** `resources/views/user-payreqs/reimburse/print_pdf.blade.php` includes an **Expense date** column per detail row.

See **ADR-PAYREQ-01** in `docs/decisions.md`.

## Accounting: automated exchange rates (Kemenkeu Kurs Pajak)

The console command **`exchange-rates:update`** (see `app/Console/Kernel.php` schedule) pulls **Kurs Pajak** from **Kemenkeu** (`fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak`), parses the current KMK effective range and table rates, and **`updateOrCreate`s** `exchange_rates` rows (optional daily expansion over the KMK period; `--no-expand` for a single row per currency per period).

| Piece | Role |
|--------|------|
| `App\Services\ExchangeRateScraperService` | HTTP fetch + HTML parse: KMK number, **Tanggal berlaku** range (supports Indonesian and English month names mixed on the public page), rates from the main table (`config/exchange_rates.php` → `target_currencies`; optional `--currencies=` on the command). |
| `App\Console\Commands\UpdateExchangeRates` | Ensures **`currencies`** rows exist (`firstOrCreate`) for each scraped code and **IDR** before writing rates; **`created_by`** uses `Auth::id()` when available, otherwise the **smallest existing** `users.id` — **not** a hardcoded user id. Aborts with a clear message if **no users** exist. |
| `config/exchange_rates.php` | Target currency list from `EXCHANGE_RATES_TARGET` env (default `USD,AUD,SGD`). |

**Tests:** `tests/Feature/ExchangeRatesUpdateCommandTest.php` mocks `ExchangeRateScraperService` (no real HTTP). Do not run feature tests that use `RefreshDatabase` against a shared MySQL dev database.

## Development: PHPUnit and database isolation

**PHPUnit** is configured in **`phpunit.xml`** to use **SQLite in-memory** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) for automated tests. This prevents `RefreshDatabase` (and similar traits) from migrating or wiping the **development or production MySQL** database configured in `.env`.

The **`doctrine/dbal`** package is listed under **`require-dev`** so SQLite can run the same migrations that use column **change** operations (Laravel delegates some of those to Doctrine when the platform requires it).

Developers should run **`composer install`** (including dev dependencies) before **`php artisan test`**. Production deploys that use `composer install --no-dev` do not require `doctrine/dbal` on the server unless you run tests there.

See **ADR-TEST-01** in `docs/decisions.md`.
