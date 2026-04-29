# Task tracking

## Active

_(none — add items here)_

## Recently completed

- **2026-04-29** — **Cashier: Realization Attachments**
  - Module for uploading/downloading/deleting images & PDFs per **`realizations`** row (`realization_attachments` table, private disk). Spatie permissions **`akses_realization_attachments`**, **`create_realization_attachments`**, **`delete_realization_attachments`**, **`realization_attachments_scope_bo`** (seeder + idempotent migration inserting permission rows + cache reset). **`RealizationAttachmentsAccessService`** scopes lists: HO (**000H**) all projects; **`realization_attachments_scope_bo`** non-HO/non-APS projects **and** **`whereHas('attachments')`** only; others own project. Sidebar menu (`sidebar.blade.php`) — **not** only `menu/cashier.blade.php` (unused in main layout). DataTable filters + Employee Name column + action badge when attachments exist; detail shows remarks.  
  - Docs: `docs/architecture.md`, `docs/decisions.md` (ADR-REALIZATION-ATTACH-01), `MEMORY.md` [037].

- **2026-04-29** — **Automated Kemenkeu exchange rates (`exchange-rates:update`) hardening + PHPUnit DB safety**
  - Parser updated for live Kemenkeu HTML (**Tanggal berlaku**, mixed EN/ID month names, flexible day widths). Command **`firstOrCreate`s** missing **`currencies`** (scraped codes + **`IDR`**), resolves **`created_by`** from **`Auth::id()`** or **`User::query()->orderBy('id')->value('id')`** (no assumption that user **`id = 1`** exists). **`phpunit.xml`** forces **`sqlite` `:memory:`** so **`RefreshDatabase`** never targets MySQL dev; **`doctrine/dbal`** (^3.10) in **`require-dev`** for SQLite migrations. Feature test **`tests/Feature/ExchangeRatesUpdateCommandTest.php`** mocks the scraper.  
  - Docs: `docs/architecture.md`, `docs/decisions.md` (ADR-TEST-01), `MEMORY.md` [036].

- **2026-04-25** — **PCBC PDF validation (official report gate)**
  - `dokumens`: `validation_status`, `validated_at`, `validated_by`, `rejection_reason`; compliance + monthly dashboard count only **`validated`** rows; upload → **pending**; validate/reject routes; permission `validate_pcbc_report` (seeder + `RoleController`); confirmations on validate/reject; uploader reads **rejection reason** via Status column **View reason** modal and **Edit** modal alert.  
  - Docs: `docs/architecture.md`, `docs/decisions.md` (ADR-PCBC-04, ADR-PCBC-05), `MEMORY.md` [034].

- **2026-04-24** — **PCBC weekly compliance & UX**  
  - Service + config for Monday–Sun weeks (`Asia/Makassar`), `dokumen_date` basis, exception projects, two-week sanction, redirect middleware, `see_pcbc_warning` permission, shared view data middleware, bilingual banner, upload page and DataTable fixes.  
  - Docs: `docs/architecture.md`, `docs/decisions.md`, `MEMORY.md` [033].

## Archive

Move older completed work here or trim when the list grows. Historical detail often lives in `MEMORY.md` and `docs/decisions.md`.
