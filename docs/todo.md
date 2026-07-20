# Todo

## Recently Completed

### Bank Reconciliation P0 hardening (2026-07-20)

- [x] FetchSapGlLinesJob fetch-before-delete + failed status/notes + match-group cleanup
- [x] Retries/backoff/failed() on Parse, Fetch SAP, Auto-match jobs
- [x] Status JSON `notes` + dismissible failure banner on review poll
- [x] Additive reconciliation statement submit gate + editable opening/closing balances
- [x] `reconciling_type` classification (annotation) + formal report statement
- [x] Feature tests + architecture/ADR/MEMORY docs

### Bank Reconciliation P1 — policy + route gate (2026-07-20)

- [x] `BankReconciliationPolicy` (viewAny/view/create/update/submit/validate + project scoping)
- [x] `permission:akses_koran` middleware on bank-reconciliation route group
- [x] Controller uses `$this->authorize(...)` instead of inline auth helpers
- [x] `BankReconciliationAuthorizationTest` (missing permission, cross-project, elevated bypass)

### Bank Reconciliation P2 (2026-07-20)

- [x] Confirm dialogs before re-parse / fetch SAP (clears matches)
- [x] Matching performance: no N+1 `fresh()`, meet-in-the-middle subset sum, AI top-N fuzzy candidates
- [x] Notify validators on submit + preparer on reject (mail + database)
- [x] Excel export of reconciliation statement (`BankReconciliationExport`)
- [x] Tests: parse failure, split/fuzzy match, notifications, export, confirm UI

### Fix account statement unit_no from OIGE/ODLN (2026-07-20)

- [x] SQL lines on OJDT/JDT1; enrich unit_no via OData InventoryGenExits/DeliveryNotes (60/15)
- [x] doc_num=BaseRef; doc_type TransType labels (`mapDocTypeLabel`)
- [x] Default `SAP_ACCOUNT_STATEMENT_UNIT_UDF=MIS_UnitNo`; pure OData path unit_no stays null
- [x] Docs: api-account-statements, ADR-SAP-02, MEMORY [062], architecture

### Drop SAP-Bridge for Service Layer account statements (2026-07-20)

- [x] `SapService::getAccountStatement()` SQLQueries + OData fallback
- [x] Wire `SapTransactionController` + `FetchSapGlLinesJob`
- [x] Remove SAP-Bridge service/exception/config
- [x] `sap:probe-sql-queries` + `SAP_ACCOUNT_STATEMENT_*` env keys
- [x] Feature tests + docs (`MEMORY`, architecture, ADR, api-account-statements)

### Notulen AI hardening (2026-07-20)

- [x] Multibyte-safe chunking + preserve paragraph structure
- [x] ProcessMeeting retries / processing status / error_message
- [x] Retrieval scoping + chunk cache
- [x] Ask UX + evidence citations + language-aware not-found
- [x] OCR page/size guards
- [x] Embeddings provider switch (OpenRouter verified; OpenAI optional)
- [x] Observability columns + duplicate file_hash detection
- [x] Recreate `docs/notulen-ai.md`

## Next (backlog ideas)

### Bank Reconciliation (P3 deferred)

- [ ] Optional: reduce show-page polling (Echo/backoff once status terminal)
- [ ] Optional: bulk-accept high AI-confidence bank lines

### Other

- [ ] Audio-to-minutes (Whisper) if stakeholders want true “AI notulen”
- [ ] Page-by-page OCR with Imagick/pdftoppm
- [ ] Vector DB / MySQL VECTOR when chunk count grows
