# Manuals for in-app HELP

Markdown files in this directory are indexed by `php artisan help:reindex`.

## Authoring rules

- Use `##` headings to split content; each `##` section becomes one search chunk.
- **Always provide both languages:** create a pair of files for every topic, with locale suffixes `*-en.md` (English) and `*-id.md` (Bahasa Indonesia). The HELP retriever boosts the file that matches the user’s locale.
- Keep English and Indonesian manuals aligned (same sections and menu names as shown in the UI).
- Describe real menu paths and button labels as they appear in the app — the HELP assistant must not infer UI names.
- Optionally add [`docs/help-navigation.json`](../help-navigation.json) with an `items` array to improve “where in the menu?” answers (menu paths, permissions, keywords). This file is indexed by `help:reindex` alongside manuals.

## Current manuals

| Topic | English | Bahasa Indonesia |
|--------|---------|------------------|
| Getting started | `getting-started-en.md` | `getting-started-id.md` |
| Bank reconciliation | `bank-reconciliation-manual-en.md` | `bank-reconciliation-manual-id.md` |
| RAB / Anggaran | `anggaran-manual-en.md` | `anggaran-manual-id.md` |
| Realization — scan fuel receipts (AI) | `realization-fuel-receipt-scan-manual-en.md` | `realization-fuel-receipt-scan-manual-id.md` |

Menu navigation hints for HELP: [`docs/help-navigation.json`](../help-navigation.json) (Bank Reconciliation, Rekening Koran, validator queue, Help panel).

## Technical reference

System design (API, tables, OpenRouter, **`akses_help`**): **`docs/architecture.md`** (section *In-app HELP*). Rationale: **`docs/decisions.md`** (**ADR-HELP-01**). Task log: **`MEMORY.md`** entry **[044]**.

**Realization — AI fuel receipt scan** (**Scan Fuel Receipts**, multi-receipt OpenRouter, bulk save): **`docs/architecture.md`** (subsection *AI scan: fuel receipts*). Rationale: **`docs/decisions.md`** (**ADR-REALIZATION-FUEL-SCAN-01**). Task log: **`MEMORY.md`** entry **[048]**.

**Verification journal — SAP browser print** (repeating **`thead`**, title size, print limits): **`docs/architecture.md`** (section *Verification journal: SAP-aligned print*). Task log: **`MEMORY.md`** entry **[046]**.

Navigation (**menu search** bar, **`GET /api/menu/search`**, RBAC parity with sidebar): **`docs/architecture.md`** (section *Navigation: top-bar menu search*). Rationale: **`docs/decisions.md`** (**ADR-NAV-01**). Portable field notes: **`docs/menu-search-feature-reference.md`**. Task log: **`MEMORY.md`** entry **[045]**.