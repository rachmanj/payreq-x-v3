# Architecture decisions (ADRs)

Lightweight records of intent and trade-offs. Numbering is sequential by topic area.

---

## ADR-ANGGRAN-01 — RAB release totals: single pipeline, split permissions, POST recalc, scheduled sync

**Status:** Accepted (2026-04-29)

**Context**

- **`balance`** / **`persen`** on `anggarans` are stored for fast grids but diverged from payreq/outgoing/realization math when caches or **`old_rab_id`** migrations made paths inconsistent.
- Bulk listing actions (activate/deactivate many) were gated behind **`recalculate_release`**, blurring operational duties.
- Triggering full recomputation via GET carried unsafe semantics (prefetch/logs).
- A global cache flush per scheduled sync that iterated every RAB id proved too costly on large datasets.

**Decision**

1. Centralize release calculations in **`App\Services\AnggaranReleaseService`**, including **`old_rab_id`** handling for payreq queries.
2. Split Spatie permissions: **`recalculate_release`** (manual POST `/reports/anggaran/recalculate`) vs **`anggaran_bulk_activate_deactivate`** (bulk status toggles). Seed migration assigns the latter to roles already granted the former.
3. Replace GET Recalc with **`POST`** + CSRF.
4. Scheduled **`anggaran:sync-release-totals`** calls **`flushListingCaches`** after sync; admin-triggered Recalc may still perform broader **`flushAllReportingCaches`** when consistency demands it.

**Consequences**

- Positive: fewer mismatched percentages between User-payreq detail and reporting surfaces; clearer RBAC; safer HTTP verbs.
- Negative: scheduled sync runtime still scales with approved row counts—future tuning belongs in DB indexing or narrower sync scope, not cache loops alone.

---

## ADR-PAYREQ-01 — Shared realization-detail Form Requests for realization **and** reimburse

**Status:** Accepted (2026-04-28)

**Context**

- Reimbursement detail endpoints historically validated only **`description`** / **`amount`** while realization flows enforced fleet, **`expense_date`**, and HM monotonicity. Both persist **`realization_details`** rows.

**Decision**

1. Use **`StoreRealizationDetailRequest`** and **`UpdateRealizationDetailRequest`** on **`PayreqReimburseController::store_detail`** and **`update_detail`**, including **`rab_id`** merge from payreq like **`UserRealizationController`**.
2. **`UpdateRealizationDetailRequest::resolveDetail()`** resolves **`RealizationDetail`** from **`route('detail')`** (model or ID) **or** from **`realization_detail_id`** in the POST body when reimburse routes omit **`{detail}`**.
3. **`realization_detail_id`** is **required** only when the route has no **`detail`** parameter; **`prepareForValidation()`** merges **`realization_detail_id`** from implicit route binding when present.
4. **`authorize()`** returns **`Auth::check()`** when no detail can be resolved yet so missing IDs surface as **422** validation errors instead of **404**.

**Consequences**

- One validation pipeline for realization and reimburse lines; reimburse UI must expose the same fleet/expense/HM fields where rules apply.

---

## ADR-PAYREQ-02 — `expense_date` as calendar **`Y-m-d`** in JSON / arrays

**Status:** Accepted (2026-04-29)

**Context**

- `realization_details.expense_date` is **date-only**, but Eloquent **`date`** casts serialize Carbon as **ISO8601 UTC** in **`toArray()`** / JSON.
- Non-UTC **`APP_TIMEZONE`** maps calendar midnight to a UTC instant on the **prior** calendar date; clients using **`substring(0, 10)`** on ISO strings mis-display the saved day.
- HTML5 **`type=date`** submits **`YYYY-MM-DD`**; ambiguous **`Carbon::parse`** on payloads can amplify timezone drift.
- The minimum expense date tied to **payreq approval** was removed; only **not after today** (per **`APP_TIMEZONE`**) remains.

**Decision**

1. **`RealizationDetail::attributesToArray()`** overrides **`expense_date`** to **`Y-m-d`** (**`getRawOriginal('expense_date')`** when present, else TZ-aware formatting).
2. **`ValidatesRealizationDetailFleet`** adds **`canonicalDateOnlyString`** and **`parseExpenseDateStartOfDay`** for normalization and future-date checks.
3. **`RealizationDetailOdometerMonotonicityValidator`** prefers raw DB **`expense_date`** when bucketing by calendar day.

**Consequences**

- Stable calendar strings for browsers and simple parsers; consumers must treat **`expense_date`** as date-only, not a datetime instant.

---

## ADR-PAYREQ-03 — Advance payreq multi-allocation (**`budget_link_mode`**) vs legacy single **`rab_id`**

**Status:** Accepted (2026-05-08)

**Context**

- Advances sometimes need **one cash payment split across multiple anggaran** rows without issuing separate payreq documents.
- Realization (**`realization_details`**) historically assumed a single payreq **`rab_id`**; multi-budget advances need **per-detail **`rab_id`** consistent with allocations.
- Users without budget-picker permission (**`rab_select`**) must remain on legacy single-RAB UX.
- Operational surfaces (**print**, **owner detail**) must expose each allocation clearly; drafts submitted from **edit** must include immutable fields the server validates (e.g. **Payreq No**).

**Decision**

1. Persist mode on **`payreqs`** as **`budget_link_mode`** (**`legacy`** | **`multi_allocation`**; **`App\Support\PayreqBudgetLinkMode`**). Child allocations live in **`payreq_anggaran_allocations`** with **`payreq_id`**, **`anggaran_id`**, **`amount`**, **`remarks`**, **`sort_order`**.
2. **Creation/update:** **`ProcessAdvancePayreqRequest`** + **`PayreqAdvanceController::proses`** — multi mode **`required`** **`allocations[]`** rows; **`withValidator`** enforces \(\sum\) row **`amount`** = header **`amount`**. Mode **cannot change after** draft creation (edit submit).
3. **`prepareForValidation`** merges formatted amounts; if **`rab_select`** is denied, **`budget_link_mode`** is forced to **`legacy`** and **`allocations`** cleared so server rules match constrained clients.
4. **Realizations:** When **`Payreq::isAdvanceMultiBudget()`**, realization detail endpoints accept/require **`rab_id`** per line aligned with **`StoreRealizationDetailRequest`** / **`UpdateRealizationDetailRequest`** and warning copy from **`PayreqRealizationBudgetWarningService`** where applicable.
5. **UX consistency:** Prints use **`advance/partials/print_budget_table_body`**; **`user-payreqs/show`** lists allocations via **`partials/show_advance_allocation_table`**. Advance **edit** uses **`readonly`** (**not **`disabled`**) on **`payreq_no`** so validation receives the field.

**Consequences**

- Positive: clearer audit trail per anggaran share; realization lines can reconcile to budgets independently; RBAC-aligned simplification for non-**`rab_select`** users.
- Negative: API/printing/reporting callers outside Blade may still assume a single **`payreq.rab_id`**—extend intentionally where totals must split.

---

## ADR-COMPAT-01 — No constants in **`ValidatesRealizationDetailFleet`**

**Status:** Accepted (2026-04-29)

**Context**

- PHP **before 8.2** cannot declare **`const`** inside **traits**, causing fatal errors on older production PHP.

**Decision**

- Replace trait constants with **`fleetInputFieldNames(): array`**.

**Consequences**

- Older PHP stacks load the concern; behaviour unchanged on PHP 8.2+.

---

## ADR-OVERDUE-EXT-01 — Overdue extension workflow, eligibility alignment, approver access, dashboard signal

**Status:** Accepted (2026-05-06)

**Context**

- Payreq/realization documents can become overdue; only some projects (**000H**, **APS**) participate in a formal **request → approve/reject** extension flow alongside legacy admin **extend** actions on document-overdue screens.
- Submit-time eligibility initially diverged from list queries when **due date = today** (“overdue” in SQL **`due_date < now()`** sense vs **end-of-day** parsing).
- **`superadmin`** / **`admin`** users needed the approval UI without depending solely on Spatie permission rows copied per environment.
- The dashboard includes **`Announcement::visibleToUser()`**, whose **`scopeCurrent`** used MySQL-only **`DATE_ADD`**, breaking **`GET /dashboard`** under **SQLite** PHPUnit configuration.

**Decision**

1. Persist extension requests on **`overdue_extensions`** with statuses **`pending`** / **`approved`** / **`rejected`**; **`OverdueExtensionController`** owns listing JSON, user **`store`**, and **`approve`** / **`reject`** (transactions updating **`payreqs.due_date`** or **`realizations.due_date`** on approve).
2. User-facing extension UX lives on **`user-payreqs/overdue-documents`** (not duplicated inline on generic My Payreqs index); **`store`** enforces owner, eligible project, document state (**advance + paid** vs **approved realization**), **`Carbon::parse($due_date)->lt(now())`**, and single pending row per document.
3. **`Gate::before`**: users with roles **`superadmin`** or **`admin`** implicitly pass **`approve_overdue_extension`**; others rely on Spatie **`approve_overdue_extension`** as usual.
4. Dashboard shows a **pending count** card for users who can approve, linking to **`document-overdue.extensions.index`**.
5. **`Announcement::scopeCurrent`**: branch **`DATE_ADD`** (MySQL default) vs SQLite-compatible **`date(...)`** arithmetic so announcement scopes remain correct in production while PHPUnit can render the dashboard.

**Consequences**

- Positive: one pipeline for extension history + approvals; eligibility matches overdue listings; admins retain access without fragile permission DB drift; approvers see backlog from **`/dashboard`**.
- Negative: **`Gate::before`** must stay narrow (single ability string check) to avoid accidental broad bypass; dual SQL for **`scopeCurrent`** must be kept in sync when the “current window” rule changes.

---
