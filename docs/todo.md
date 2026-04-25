# Task tracking

## Active

_(none — add items here)_

## Recently completed

- **2026-04-25** — **PCBC PDF validation (official report gate)**  
  - `dokumens`: `validation_status`, `validated_at`, `validated_by`, `rejection_reason`; compliance + monthly dashboard count only **`validated`** rows; upload → **pending**; validate/reject routes; permission `validate_pcbc_report` (seeder + `RoleController`); confirmations on validate/reject; uploader reads **rejection reason** via Status column **View reason** modal and **Edit** modal alert.  
  - Docs: `docs/architecture.md`, `docs/decisions.md` (ADR-PCBC-04, ADR-PCBC-05), `MEMORY.md` [034].

- **2026-04-24** — **PCBC weekly compliance & UX**  
  - Service + config for Monday–Sun weeks (`Asia/Makassar`), `dokumen_date` basis, exception projects, two-week sanction, redirect middleware, `see_pcbc_warning` permission, shared view data middleware, bilingual banner, upload page and DataTable fixes.  
  - Docs: `docs/architecture.md`, `docs/decisions.md`, `MEMORY.md` [033].

## Archive

Move older completed work here or trim when the list grows. Historical detail often lives in `MEMORY.md` and `docs/decisions.md`.
