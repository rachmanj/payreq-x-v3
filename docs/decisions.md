# Architecture decisions

## ADR-SAP-01 — Drop SAP-Bridge; use Service Layer for account statements (2026-07-20)

**Context:** Account statements for Cashier SAP Transactions and Bank Reconciliation were fetched from an external SAP-Bridge HTTP API, while the app already had a working Service Layer client (`SapService`) for journals, invoices, and master data.

**Decision:** Remove SAP-Bridge entirely. Implement `SapService::getAccountStatement()` with:

1. **SQLQueries** on `OJDT`/`JDT1` as the preferred path (efficient opening balances + UDF support).
2. **OData JournalEntries** as fallback when SQLQueries is unavailable.
3. Config toggle `SAP_ACCOUNT_STATEMENT_MODE=auto|sql|odata` and optional `SAP_ACCOUNT_STATEMENT_UNIT_UDF`.

**Alternatives considered:** Keep Bridge as optional fallback (rejected — dual connectivity and ops burden); OData-only (rejected — opening-balance lookback is expensive and UDFs unreliable).

**Consequences:** One less microservice to operate; statement quality depends on Service Layer permissions (SQLQueries may need enabling). Run `php artisan sap:probe-sql-queries` after deploy.

**Review:** Revisit if SQLQueries remains disabled in production and OData lookback becomes too slow.

## ADR-SAP-02 — unit_no via OData InventoryGenExits/DeliveryNotes (2026-07-20)

**Context:** `U_MIS_UnitNo` is not on `JDT1`/`OJDT`. Company report `docs/je_daily.sql` joins `OIGE`/`ODLN` on `BaseRef`. Direct SQLQueries joins failed: this SL rejects `CASE`, and tables `OIGE`/`ODLN` return "not accessible".

**Decision:**

1. SQLQueries lines stay on `OJDT`/`JDT1` (`doc_num`=`BaseRef`, `doc_type`=`T1.TransType` → labels).
2. Enrich `unit_no` with batched OData `$filter` on `InventoryGenExits` (TransType 60) and `DeliveryNotes` (TransType 15), selecting the configured UDF (default `MIS_UnitNo` → `U_MIS_UnitNo`).
3. Pure OData statement fallback leaves `unit_no` null.

**Alternatives considered:** JDT1 UDF (rejected — missing); SQL join OIGE/ODLN (rejected — not accessible / no CASE); per-row OData (rejected — use chunks of 20 DocNums instead).

**Consequences:** Unit No only for Goods Issue / Material Issue, matching the SAP report. Extra OData round-trips only when those TransTypes appear in the period.

**Review:** If SQLQueries later exposes OIGE/ODLN, consider moving enrichment back into SQL.

## ADR-NOTULEN-01 — Notulen AI integrated into existing Blade stack (2026-07-03)

**Context:** `docs/notulen-ai.md` described a greenfield app (Laravel 12, Inertia, React, Ant Design, MySQL FULLTEXT, Sanctum PATs, Breeze auth).

**Decision:** Implement Notulen AI inside the existing AccountingOne Laravel 10 app using:

- **Blade + AdminLTE** pages (not Inertia/React/AntD).
- **OpenRouter embeddings + cosine similarity** (same pattern as in-app HELP), not MySQL FULLTEXT.
- **Existing `ApiKey` + `auth.apikey`** for third-party API access (not new Sanctum token UI).
- **Spatie permissions** `akses_notulen`, `upload_notulen`, `delete_notulen` on existing roles (not new admin/member roles).
- **Custom session auth** (username login) unchanged.
- **`smalot/pdfparser`** for local PDF text extraction; **`ProcessMeeting`** job on default **`sync`** queue.

**Consequences:** Faster integration, consistent UX with ERP sidebar; semantic retrieval cost follows HELP/OpenRouter embedding usage. Signed download URLs used for API source links; web uses auth-guarded download route.

**Review:** Revisit if notulen module needs dedicated SPA or vector DB at scale.
