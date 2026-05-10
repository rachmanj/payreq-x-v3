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

## ADR-ANGGRAN-02 — RAB detail lines, fund pool, consolidated reporting, utilization alerts, periodic auto-expiry

**Status:** Accepted (2026-05-09)

**Context**

- Budget preparation needed **line-item breakdown** (optional **COA** / **`accounts`** link) while keeping header **`anggarans.amount`** as the ceiling users and approvals already understand.
- Finance needed **pool → release** stages on cash planning, **project-level consolidation**, and **department rollups** without exporting spreadsheets for every review.
- Operators needed **visible alerts** when **`persen`** nears or exceeds policy limits, configurable per row via **`warning_threshold`**.
- **Periodic** (**`periode`**) budgets should **stop being selectable/active** after the period without manual monthly toggles.

**Decision**

1. Add **`anggaran_details`** child table; users submit **`details[]`** from RAB create/edit; server replaces lines on save when **`details`** is present (**full replace** per save for simplicity).
2. Add **`anggarans.warning_threshold`**, **`fund_status`**, **`fund_pooled_at`**, **`fund_pooled_by`**. Fund transitions: **`recalculate_release`** may mark **pending→pooled** and **pooled→released** only (no skipping stages in controller bulk actions).
3. Split **reporting** concerns: **`AnggaranDashboardController`** owns the rich dashboard + JSON/DataTables endpoints; listing/recalc/bulk remain **`Reports\AnggaranController`**; **`AnggaranConsolidatedController`** and **`AnggaranFundPoolController`** own their screens.
4. **Alerts:** UI derives warnings from stored **`persen`** vs **`warning_threshold`** and **> 100%**; no extra persisted “warning flag”.
5. **Expiry:** Command **`anggaran:expire-periodic`** deactivates (**`is_active = 0`**) only **approved** **`periode`** rows past period end; end date resolves from **`end_date`** or **`periode_anggaran` month-end**. Schedule in **`App\Console\Kernel`** (daily).

**Consequences**

- Positive: clearer budget structure, admin surfaces for consolidation and funding workflow, less manual cleanup for expired periodic headers.
- Negative: line-item totals are not auto-enforced against header **`amount`** in app logic (operators should reconcile intentionally); fund pool permissions reuse **`recalculate_release`**—split permission later if duties diverge.

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

## ADR-BANK-REC-01 — Bank reconciliation **match groups** (N:M) instead of pairwise **`reconciliation_matches`**

**Status:** Accepted (2026-05-08)

**Context**

- Real-world bank vs ledger ties are often **one bank movement ↔ several postings**, **several bank lines ↔ one posting**, or **many ↔ many**; storing only **one bank ↔ one SAP** rows duplicated intent and blocked legitimate splits.
- Auto-match already needed **subset-sum** style grouping for splits; manual UX forced users into unnatural pairwise selects.

**Decision**

1. Replace legacy pairwise **`reconciliation_matches`** with **`reconciliation_match_groups`** plus pivots **`match_group_bank_lines`** and **`match_group_sap_lines`** (migration migrates old rows then drops the old table).
2. Enforce **at most one group membership per bank line and per SAP line** via **unique** constraints on **`bank_statement_line_id`** and **`sap_gl_line_id`** in the pivot tables.
3. Store rolled-up **`bank_total`**, **`sap_total`**, **`difference`** on the group for review/reporting; **`match_type`** distinguishes **`auto_exact`**, **`auto_fuzzy`**, **`auto_split`**, **`manual`**.
4. **Manual matching** submits **arrays** of IDs validated by **`ManualMatchGroupBankReconciliationRequest`**; totals must agree within **0.005**, consistent with **`ReconciliationMatchingService::AMOUNT_TOLERANCE`**.
5. **Undo** is **`deleteMatchGroup`** (explicit **`POST`** **`…/unmatch/{reconciliation_match_group}`**), which restores contained lines to **`unmatched`**; disallowed when the parent **`bank_reconciliations.status`** is **`completed`**.
6. **Auto-match** clears **auto** groups only (**exact**, **fuzzy**, **split**) before recomputing, preserving **manual** groups.

**Consequences**

- Positive: one schema for manual splits and algorithmic splits; UI and audits describe **groups** naturally.
- Negative: consumers must stop referencing **`ReconciliationMatch`** / **`BankReconciliation::matches`**; any external reporting must pivot via **`reconciliation_match_groups`**.

---

## ADR-HELP-01 — In-app HELP: RAG on Markdown manuals only (OpenRouter embeddings + chat)

**Status:** Accepted (2026-05-09)

**Context**

- Users need guided answers for workflows (**RAB**, **bank reconciliation**, etc.) without granting an LLM direct database queries or hallucinated shortcuts.
- Manuals evolve independently of code releases; embeddings must refresh explicitly (**reindex**) so production answers stay deterministic relative to indexed text.
- The product UI mixes English labels (**Bank Reconciliation**, **How-to**) with bilingual end users — retrieval should favour **`locale`**-tagged chunks (**`-id.md`** / **`-en.md`**).

**Decision**

1. Store vectors in **`help_embeddings`** (**JSON** float arrays), full rebuild via **`php artisan help:reindex`** (truncate + chunk + embed batch); chunks split on **`##`** under **`docs/manuals/`**.
2. Gate low-confidence retrieval with **`HELP_SIMILARITY_THRESHOLD`** — below threshold returns **`not_documented`** without spending chat tokens.
3. Server-side only OpenRouter (**`OPENROUTER_API_KEY`**); separate HTTP client (**`HelpOpenRouterClient`**) from **`OpenRouterService`** used for unrelated flows (e.g. Koran PDF extraction).
4. Protect routes with **`akses_help`** (**Spatie**) + **`throttle`**; expose UI only via **`@can('akses_help')`**.
5. Maintain **paired** manuals **`topic-en.md`** + **`topic-id.md`** for every topic shipped to HELP.
6. **`.gitignore`**: ignore generic **`docs/*`** except **`docs/manuals/**`** so handbooks remain version-controlled.

**Consequences**

- Positive: predictable behaviour (documentation truth); admins control scope via markdown and roles; HELP does not expose live payreq or ledger balances.
- Negative: operational **`help:reindex`** after manual edits; retrieval quality depends on authoring and thresholds; feedback email needs working mail when **`HELP_FEEDBACK_NOTIFY_EMAIL`** is set.

---

## ADR-NAV-01 — Top navbar menu search (session-authenticated JSON + mirrored RBAC)

**Status:** Accepted (2026-05-10)

**Context**

- The primary shell (**AdminLTE** sidebar + top bar) accumulates many nested destinations; users requested faster jumps without expanding tree menus.
- Search results must **never** expose URLs the signed-in user cannot open—the surface area matches **`templates/partials/sidebar.blade.php`**, including exceptional flows (**PCBC** sanction locking **Ready to Pay** / **Incoming List** only).

**Decision**

1. Serve **`GET /api/menu/search`** under **`routes/api.php`** with **`middleware(['web', 'auth'])`** so **session** authentication matches Blade pages (not **`auth:sanctum`** alone).
2. Encode menu rows in **`App\Services\MenuSearchService`** with explicit **`User::can`** / **`hasAnyRole`** checks aligned to the sidebar; inject **`PcbcComplianceService`** where cashier sanction affects visibility.
3. Cache assembled **`items`** per user (**TTL 3600** s) keyed by **sorted Spatie permission names** plus a **sanction suffix** so permission-only cache keys do not leak outdated cashier links after sanction changes.
4. Front-end: **`public/js/menu-search.js`** + **`public/css/menu-search.css`**, included from **`templates/partials/{script,head}.blade.php`**; markup lives in **`templates/partials/topbar.blade.php`**.
5. Accept **second source of truth** risk: new sidebar links require parallel updates in **`MenuSearchService`** until a shared menu definition exists.

**Consequences**

- Positive: fast navigation with keyboard shortcut and minimal server load after first fetch; RBAC parity when service stays in sync with sidebar.
- Negative: drift if sidebar changes omit **`MenuSearchService`**; large menus may warrant lowering TTL or explicit cache bust hooks when roles change in-session.

---
