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

---

## ADR-PCBC-04 — Official PCBC = `validation_status` **validated** (not “uploaded”)

**Context:** A PDF can be uploaded with the correct `dokumen_date` but should not count for sanctions or the monthly **dashboard** until a reviewer marks it **validated**. The business also needed **reject** with a reason and a path for uploaders to read that reason in the app.

**Decision:**

- Store lifecycle on `dokumens`: `validation_status` ∈ {`pending`, `validated`, `rejected`} plus `validated_at`, `validated_by`, `rejection_reason`.
- **Qualifying** upload for `PcbcComplianceService` and `PcbcController::check_pcbc_files` = `type=pcbc` **and** `validation_status=validated` **and** `dokumen_date` in range.
- New PCBC rows from the upload form start **pending**; the migration default **`validated`** grandfathers existing rows.
- Rejection stores **reason**; **validators** are users with Spatie permission **`validate_pcbc_report`**.

**Consequences:** Compliance logic and list/dashboard counts must always filter on `validation_status` where “official” matters. Uploader feedback is done in-UI (status column + edit modal), not by e-mail in this version.

**Status:** Accepted (2026-04-25)

---

## ADR-PCBC-05 — Uploader visibility of `rejection_reason` (list + edit)

**Context:** Rejecting without surfacing the reason in the UI would block uploaders from correcting submissions.

**Decision:** In the DataTable, **rejected** rows show **View reason** (modal: full text, rejector, timestamp from `validated_by` / `validated_at`). The **Edit** modal for a rejected row includes the same text at the top so the uploader always sees the reason in context when re-uploading or changing the date.

**Consequences:** Rejection modals and alerts must stay **escaped** (`e` + `nl2br` where needed) to avoid XSS from stored free text.

**Status:** Accepted (2026-04-25)
