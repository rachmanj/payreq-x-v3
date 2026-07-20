# Todo

## Recently Completed

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

- [ ] Audio-to-minutes (Whisper) if stakeholders want true “AI notulen”
- [ ] Page-by-page OCR with Imagick/pdftoppm
- [ ] Vector DB / MySQL VECTOR when chunk count grows
