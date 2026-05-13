# Application architecture (internal)

This document summarizes structural flows that extend Laravel defaults. Keep it scoped and factual—defer rationale to `docs/decisions.md`.

## Anggaran / RAB (budget requests)

### Data model (header + lines)

- **`anggarans`**: budget header (existing fields unchanged for approvals, `balance`/`persen`, `type` `periode|event|buc`, etc.). Added: **`warning_threshold`** (0–100, default **80**) for utilization alerts; **`fund_status`** **`pending`** → **`pooled`** → **`released`**; **`fund_pooled_at`**, **`fund_pooled_by`** (`users` FK).
- **`anggaran_details`**: optional **`account_id`**, description, qty, unit, **`unit_price`**, **`amount`**, **`sort_order`**; cascades on parent delete.
- **`App\Models\Anggaran`**: **`details()`**, **`department()`**, **`fundPooledBy()`**, fund-status constants; **`payreqs()`** uses **`hasMany(Payreq::class, 'rab_id')`** (payreq column is **`rab_id`**, not **`anggaran_id`**).

### User-payreq surface (`routes/user_payreqs.php`)

- List/detail/edit/create views live under `resources/views/user-payreqs/anggarans/`; budget lines UI + partial **`partials/budget-detail-rows.blade.php`**.
- **`App\Http\Controllers\UserPayreq\UserAnggaranController`** — drafts/submit (**`ApprovalPlanController`**), DataTables, **`AnggaranReleaseService`**; syncs **`details[]`** on create/update when present; passes **`Account`** list for line selectors.
- **`App\Http\Requests\UserPayreq\ProcessAnggaranRequest`** — **`rab_type`** conditionals plus **`details.*`** (account, description, qty, unit, unit_price, amount).
- **`App\Http\Controllers\UserPayreq\UserAnggaranDetailController`** — **`DELETE .../anggarans/{anggaran}/details/{detail}`** (authorized via **`editThroughPayreq`**).
- **`App\Policies\AnggaranPolicy`**: unchanged intent — **`view`**, **`editThroughPayreq`** for user flows.
- **Show:** banners when utilization **`≥ warning_threshold`** or **> 100%**; table of **budget lines** when any exist.

### Reports surface (`routes/reports.php`, prefix `reports/anggaran`)

- **Dashboard**: **`GET reports/anggaran/dashboard`** — **`App\Http\Controllers\Reports\AnggaranDashboardController@index`**: KPI cards (totals, near-threshold count, exceeded count, pending fund pool), by-type / by-project breakdowns, expiring periodic (30 days), exceeded shortlist, filters (`project`, `type`, `fund_status`). **`GET reports/anggaran/dashboard/by-department?project=`** — JSON for async department rollup. **`GET reports/anggaran/dashboard/release-data`** — server-side DataTable of approved active budgets (amount, stored release, remaining, utilization bar, fund badge, payreq count, department).
- **Consolidated**: **`GET reports/anggaran/consolidated`** — **`AnggaranConsolidatedController`**: same filters, pageable listing, grand totals, **by-department** section when a project is selected.
- **Fund pool**: **`GET|POST reports/anggaran/fund-pool`** — **`AnggaranFundPoolController`**: list/filter approved active rows; **`POST .../fund-pool/pool`** marks **pending → pooled** (optionally sets **`fund_pooled_at`** / **`fund_pooled_by`**); **`POST .../fund-pool/release`** marks **pooled → released** only. Both require **`recalculate_release`**.
- **Listing/DataTables**: `reports/anggaran/data` — **`Reports\AnggaranController`**; bulk IDs intersected in **`authorizedBulkIds`**.
- **Recalculate**: `POST reports/anggaran/recalculate` — **`recalculate_release`**; **`syncAllApprovedStoredTotals`** + **`flushAllReportingCaches`**.
- **Bulk activate/deactivate**: **`anggaran_bulk_activate_deactivate`** permission.
- **Edit/show**: reports **edit** can set **`warning_threshold`**; **show** loads **`details.account`**, spending banners, fund fields.

### Shared domain logic (`AnggaranReleaseService`)

- **`effectiveRabIdForPayqueries`**, **`calculateTotalRelease`**, **`progressSummary`**, **`syncStoredTotals`**, **`syncAllApprovedStoredTotals`**, cache helpers (**`forgetDetailCaches`**, **`flushListingCaches`**, **`flushAllReportingCaches`**) — unchanged role for stored **`balance`** / **`persen`**.
- Added: **`parsePersenToFloat`**, **`isOverThreshold`**, **`isExceeded`**, **`aggregateDashboardStats`** (scoped to user/role + optional filters), **`aggregateByDepartment`**, **`dashboardBaseQuery`**.

### Operational tooling

- **`php artisan anggaran:sync-release-totals`** — hourly in **`App\Console\Kernel`**; **`flushListingCaches`** after sync (not full per-id detail flush).

- **`php artisan anggaran:inactivate-many --last-month`** — monthly **05:00**; skips **`buc`**.

- **`php artisan anggaran:expire-periodic`** — daily **01:00**: **approved** rows with **`type = periode`**, **`is_active = 1`**, period end **before today** — end date = **`end_date`** if set, else **last day of `periode_anggaran` month**; sets **`is_active = 0`**, listing cache flush.

### Tests

- **`tests/Feature/AnggaranReportsTest.php`** — recalculate + bulk permission gates (extend when new routes need coverage).

See **ADR-ANGGRAN-01**, **ADR-ANGGRAN-02**.

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

## Cashier: Bank reconciliation (Koran PDF ↔ SAP GL)

### Routes (`routes/cashier.php`, prefix **`cashier/bank-reconciliation`**)

- **CRUD / flow:** **`BankReconciliationController`** — **`index`**, **`create`**, **`store`**, **`show`** (review), **`report`**, **`status`** (JSON for polling), **`complete`**.
- **Async rebuild:** **`POST …/parse`** (re-queue PDF parse), **`POST …/fetch-sap`** (re-fetch GL snapshot), **`POST …/auto-match`** (queue auto-match job).
- **Matching:** **`POST …/match`** — manual **N:M** group (validated arrays **`bank_statement_line_ids[]`**, **`sap_gl_line_ids[]`**). **`POST …/unmatch/{reconciliation_match_group}`** — removes one **`ReconciliationMatchGroup`** and sets contained lines back to **unmatched** (blocked when reconciliation **`completed`**).

### Data model

- **`bank_reconciliations`**: ties **`giro_id`**, optional **`dokumen_id`** (Koran **`dokumens`** row), **`periode`**, balances (bank vs books), **`status`**, **`notes`** on failure.
- **`bank_statement_lines`**, **`sap_gl_lines`**: per-reconciliation rows with **`matched_status`** (**`unmatched`**, **`matched`**, **`manual`**, **`excluded`**).
- **N:M matching** is stored as **match groups**, not pairwise rows:
  - **`reconciliation_match_groups`**: **`match_type`** (**`auto_exact`**, **`auto_fuzzy`**, **`auto_split`**, **`manual`**), **`confidence_score`**, stored **`bank_total`** / **`sap_total`** / **`difference`** (net debit − credit per side).
  - **`match_group_bank_lines`**, **`match_group_sap_lines`**: pivot to statement / GL lines; **each bank line and each SAP line may appear in at most one group** (unique on **`bank_statement_line_id`** and **`sap_gl_line_id`**).

### Domain services & jobs

- **`BankStatementParserService`** + **`ParseBankStatementJob`**: resolves Koran PDF path (filesystem / URL quirks), calls **`OpenRouterService`** to extract structured bank lines; persists **`bank_statement_lines`** and opening/closing balances when available.
- **`SapService::getGLLines`** + **`FetchSapGlLinesJob`**: pulls SAP / bridge GL lines for the account/period into **`sap_gl_lines`** (query shape avoids invalid **`$select`** with **`$expand`** on the OData side).
- **`ReconciliationMatchingService`** + **`AutoMatchReconciliationJob`**: clears prior **auto** groups only, then **1:1** exact amount/date, **1:1** fuzzy (LLM-assisted), then **subset-sum splits** (**`auto_split`**, bounded combo size / date window / amount tolerance **0.005**). **`manualGroup`** / **`deleteMatchGroup`** / **`persistMatchGroup`** centralize group persistence and line status updates.
- Queued jobs use **`$this->afterCommit()`** in constructors (avoid duplicate **`$afterCommit`** property conflicts with **`Queueable`**).

### HTTP validation

- **`ManualMatchGroupBankReconciliationRequest`**: all IDs belong to the route **`bank_reconciliation`**, lines are **unmatched**, nets sum within **0.005** (aligned with **`ReconciliationMatchingService`**).

### UI

- **Review:** **`resources/views/cashier/bank-reconciliation/show.blade.php`** — match group summary table with **Unmatch**; bank/SAP grids with optional multi-select checkboxes (unmatched only) and fixed bottom bar showing running net totals before **Match selected as group**.
- **Koran shortcut:** **`KoranController`** (and **`resources/views/cashier/koran/dashboard.blade.php`**) surfaces **`reconciliation_id`** / **`reconciliation_status`** per month where applicable; cashier menu links into bank reconciliation where wired.

See **ADR-BANK-REC-01**.

## In-app HELP (RAG over manuals)

**Stateless** **how-to** assistant: each request is answered from **indexed Markdown** only (no live DB / tool calling). Menus in the UI remain **Cashier** → **Bank Reconciliation**, **My PayReqs** → **RAB**, **?** Help launcher, etc.—the feature does not rename modules.

### Routes & auth (`routes/help.php`, included from `routes/web.php` inside **`auth`**)

- **`POST /help/ask`** (**`help.ask`**) — JSON **`{ message, locale? }`**; returns **`{ answer, sources[], not_documented }`**. Middleware: **`permission:akses_help`**, **`throttle:30,1`**.
- **`POST /help/feedback`** (**`help.feedback`**) — JSON **`{ type, title, body, steps_to_reproduce? }`**; **`201`** + **`{ message, id }`**. Optional plaintext email when **`config('help.feedback_notify_email')`** is set.

### Permission & UI

- Spatie permission **`akses_help`**: migration assigns to roles **`superadmin`**, **`admin`**, **`cashier`**; others via Roles UI.
- Topbar **`?`** and modal partial **`templates/partials/help-panel.blade.php`** are wrapped in **`@can('akses_help')`**; included from **`templates/main.blade.php`**.

### Data model

- **`help_embeddings`**: **`chunk_key`** (unique), **`source_path`**, **`heading`**, **`locale`**, **`content`**, **`embedding`** (JSON float array). Model **`App\Models\HelpEmbedding`**.
- **`help_feedbacks`**: **`user_id`**, **`type`**, **`title`**, **`body`**, **`steps_to_reproduce`**. Model **`App\Models\HelpFeedback`** (**`protected $table = 'help_feedbacks'`**).

### Services & command

- **`App\Services\Help\HelpOpenRouterClient`** — **`/embeddings`** and **`/chat/completions`** vs OpenRouter (config under **`services.openrouter`** + **`HELP_*`** / **`OPENROUTER_*`** env). Retries / connect timeout for embeddings.
- **`App\Services\Help\HelpManualChunker`** — reads **`docs/manuals/*.md`** (split on **`##`**); optional **`docs/help-navigation.json`**; filename suffixes **`-en.md`** / **`-id.md`** set chunk **`locale`** for retrieval boost.
- **`App\Services\Help\HelpAssistantService`** — embed question, cosine similarity + locale boost, **`HELP_SIMILARITY_THRESHOLD`** gate, top-**`K`** context, strict system prompt (answer only from context).
- **`App\Services\Help\HelpVector`** — **`cosineSimilarity`**.
- **`php artisan help:reindex`** — **`App\Console\Commands\HelpReindexCommand`**: truncate **`help_embeddings`**, batch embed, insert rows. **Operational:** run after manual edits under **`docs/manuals/`**.

### Config

- **`config/help.php`** — threshold, top K, batch size, manuals path, navigation JSON path, feedback email, locale boost weights.
- **`config/services.php`** — **`openrouter.help_model`**, **`embedding_model`**, **`connect_timeout`**, **`embedding_retries`** (chat still uses existing **`OpenRouterService`** for other features e.g. Koran PDF parsing).

### Author-maintained manuals (`docs/manuals/`)

- **Bilingual by policy:** every topic ships as **`*-en.md`** and **`*-id.md`** (see **`docs/manuals/README.md`**).
- **tracked in Git:** **`.gitignore`** uses **`/docs/*`** with negation **`!/docs/manuals/`** so manuals are versioned while other **`docs/`** paths can stay ignored.

### Tests

- **`tests/Feature/Help/HelpAskTest.php`**, **`tests/Feature/Help/HelpFeedbackTest.php`** — **`Http::fake`** OpenRouter, permission gates, **`not_documented`** / chat path.

See **ADR-HELP-01**.

## Navigation: top-bar menu search

Permission-aware **quick navigation**: authenticated users search sidebar destinations from **`templates/partials/topbar.blade.php`** (desktop **`md+`** only); behaviour mirrors **`templates/partials/sidebar.blade.php`** gates (**Spatie** **`can`** / **`canAny`**, **`hasAnyRole`**) plus **`PcbcComplianceService`** for **Ready to Pay** / **Incoming List** when weekly PCBC sanctions apply (**same rule as sidebar**, which hides links rather than exposing locked URLs).

### Routes & payload (`routes/web.php` and `routes/api.php`)

- **`GET /menu/search-items`** (**`menu.search.items`**) — **preferred** for the Blade shell: registered in **`routes/web.php`** inside **`auth`** so the request uses the normal **`web`** middleware stack only (session cookies behave the same as full-page loads). JSON **`{ items: [...] }`**.
- **`GET /api/menu/search`** (**`api.menu.search`**) — retained for compatibility; uses **`web`** + **`auth`** on top of the **`api`** group (throttling applies). The navbar passes the **web** URL via **`data-menu-search-url`**.
- Optional **`?q=`** — substring filter server-side on concatenated **`searchText`**, capped at **15** rows (primary UX still filters client-side after one fetch).

### Implementation files

- **`App\Services\MenuSearchService`** — builds flat **`items`** with **`title`**, **`route`** (**absolute URL** via **`URL::to(route(...))`**), **`icon`**, **`category`**, **`breadcrumb`**, **`keywords`**, **`searchText`** (lowercased).
- **`App\Http\Controllers\Api\MenuSearchController`** — **`Cache::remember`** TTL **3600** s; cache key **`menu_items_user_{id}_{md5(sorted permission names + PCBC sanction suffix)}`** so role updates and sanction toggles do not serve stale cashier entries without clearing permissions.

### Front-end assets

- **`public/js/menu-search.js`** — loaded after jQuery in **`templates/partials/script.blade.php`**; loads **`/api/menu/search`** once, debounced typing (**300** ms), keyboard (**↑/↓**, **Enter**, **Escape**), **Ctrl+K / Cmd+K** focuses **`#menu-search-input`** (skips when focus is another input/textarea).
- **`public/css/menu-search.css`** — linked from **`templates/partials/head.blade.php`** (dark **`navbar-dark`** styling + **`navbar-light`** overrides).

### Maintainer rule

New sidebar entries **must** be reflected in **`MenuSearchService`** (or future shared menu definition) or search results drift from visible navigation.

See **`docs/menu-search-feature-reference.md`** (portable spec) and **ADR-NAV-01**.

## Verification journal: SAP-aligned print

Browser **print** layout for the SAP-style journal voucher (opened in a new tab, **`window.print()`** on load).

### Route and view

- **`GET`** **`verifications/.../{id}/print-sap-journal`** — **`VerificationJournalController::printSapJournal`** (**`routes/verification.php`**, name **`verifications.journal.print_sap_journal`**). Linked from verification journal and SAP sync row actions as **SAPJ**.
- **Blade:** **`resources/views/verifications/journal/print_sap_journal.blade.php`** — self-contained **`@media print`** styles, **`table.lines`** for detail rows.

### Print behaviour (maintainer notes)

- **Title:** **`p.jv-title`** (**JOURNAL VOUCHER**) uses **`font-size: 20px`** for readability on printouts.
- **Repeating header:** The top banner (logo, voucher no / date / doc curr, company lines, title) lives in **`thead`** as **`tr.jv-page-header`** (first row), followed by the column-header **`th`** row. On multi-page prints, browsers repeat **both** **`thead`** rows when **`table.lines`** breaks across pages. **`thead`** is set to **`display: table-header-group`** for predictable print table-header behaviour; the banner row uses **`border: none`** so it does not appear as a grid cell.
- **Footer:** A static **“page X of Y”** line is **not** rendered (native HTML print does not provide a simple, accurate page total without PDF/running elements).

### Limitation

If the **table** fits on one page but **blocks below** the table (e.g. signatures) flow to a **second** page, the voucher banner may **not** repeat there—only continuation pages **of the same table** get the repeated **`thead`**. Full **every-page** headers need a PDF generator or other print stack features.

Task log: **`MEMORY.md` [046]**.

## Related docs

- `docs/decisions.md` — ADR-ANGGRAN-01 (RAB release consolidation & tooling), ADR-ANGGRAN-02, ADR-PAYREQ-01/02/**03**, ADR-COMPAT-01, **ADR-OVERDUE-EXT-01**, **ADR-BANK-REC-01**, **ADR-HELP-01**, **ADR-NAV-01**.
