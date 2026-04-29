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
