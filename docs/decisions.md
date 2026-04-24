# Architecture / product decisions

Short records of **why** something was implemented a certain way. Revisit when requirements change.

---

## ADR-PCBC-01 — Share compliance via middleware instead of only `View::composer('templates.main')`

**Context:** The PCBC upload page (`cashier.pcbc.upload`) extends `templates.main`. A view composer bound only to `templates.main` does not reliably inject data into the **child** view’s `@section('content')`, so `$pcbcCompliance` was `null` in the content section even when the top banner (in the layout) had data.

**Decision:** Add `App\Http\Middleware\SharePcbcComplianceForViews` to the `web` middleware group and use `view()->share()` for `pcbcCompliance` and `pcbcViolationSanctioned` on every web request (guests: null / false).

**Consequences:** One lightweight service call per web request for authenticated users with the relevant permissions. Clear single source of truth for Blade.

**Status:** Accepted (2026-04-24)

---

## ADR-PCBC-02 — `see_pcbc_warning` separate from enforcement

**Context:** Stakeholders wanted some roles (e.g. approvers) to see compliance messaging without being cashiers, while sanctions should still target users with `akses_transaksi_cashier` who are non-compliant.

**Decision:**

- `see_pcbc_warning` gates **visibility** of the shared compliance payload and related UI.
- `pcbcViolationSanctioned` (from `isSanctioned()` and menu locks) is independent of that permission, but actual route blocking still uses `shouldEnforceForUser()` (exempt, project, and `akses_transaksi_cashier`).

**Consequences:** Grant `see_pcbc_warning` in Roles UI to any role that should read the banner; grant cashiers the sanctioning behavior through project + `akses_transaksi_cashier` + non-exempt.

**Status:** Accepted (2026-04-24)

---

## ADR-PCBC-03 — Yajra Eloquent for PCBC upload DataTable

**Context:** The upload table used `serverSide: true` with `->get()` and `of($collection)`, so ordering did not follow `dokumen_date` in SQL.

**Decision:** Use `Yajra\DataTables\Facades\DataTables::of($eloquentQuery)` with `->make(true)` and `orderColumn('dokumen_date', 'dokumen_date $1')` (and other columns as needed). Pass explicit `name` in DataTables column definitions in `upload.blade.php`.

**Consequences:** Correct default sort (newest PCBC document date first) and user-controlled column sort.

**Status:** Accepted (2026-04-24)
