# Architecture decisions

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
