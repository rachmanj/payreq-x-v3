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

## ADR-COMPAT-01 — No constants in **`ValidatesRealizationDetailFleet`**

**Status:** Accepted (2026-04-29)

**Context**

- PHP **before 8.2** cannot declare **`const`** inside **traits**, causing fatal errors on older production PHP.

**Decision**

- Replace trait constants with **`fleetInputFieldNames(): array`**.

**Consequences**

- Older PHP stacks load the concern; behaviour unchanged on PHP 8.2+.

---
