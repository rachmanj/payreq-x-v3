**Purpose**: Record technical decisions and rationale for future reference
**Last Updated**: 2025-01-XX

# Technical Decision Records

## Decision Template

Decision: [Title] - [YYYY-MM-DD]

**Context**: [What situation led to this decision?]

**Options Considered**:

1. **Option A**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]
2. **Option B**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]

**Decision**: [What we chose]

**Rationale**: [Why we chose this option]

**Implementation**: [How this affects the codebase]

**Review Date**: [When to revisit this decision]

---

## Recent Decisions

### Decision: Sidebar Navigation Redesign - 2025-01-XX

**Context**: The application used a top navigation bar (`layout-top-nav`) with dropdown menus that limited menu visibility, required multiple clicks to access nested items, and consumed valuable vertical space. With many menu items (My PayReqs, Cashier, Accounting, Approvals, Admin), the horizontal navbar became cluttered, making navigation less intuitive, especially on mobile devices. Users needed better navigation hierarchy and improved access to all features.

**Options Considered**:

1. **Keep Top Navigation Bar with Improved Dropdowns**
   - ✅ Pros: No major layout changes, users familiar with current design, minimal code changes
   - ❌ Cons: Still requires multiple clicks, limited visibility of menu items, poor mobile experience, takes vertical space, doesn't scale well with many items

2. **Implement Custom Sidebar Solution**
   - ✅ Pros: Full control over design and behavior, can customize to exact needs
   - ❌ Cons: Requires significant development time, need to handle responsive behavior, state management, and accessibility manually, reinventing the wheel

3. **Use AdminLTE 3 Sidebar Layout (Chosen)**
   - ✅ Pros: Native AdminLTE support, proven enterprise UI pattern, built-in responsive behavior, automatic state management, consistent with framework, better mobile experience, more menu items visible at once
   - ❌ Cons: Requires converting all menu partials, need to update layout structure

**Decision**: Redesign navigation to use AdminLTE 3's sidebar layout (`sidebar-mini layout-fixed`) with hierarchical tree structure, converting all dropdown menus to expandable sidebar sections.

**Rationale**:

- AdminLTE 3's sidebar layout is a proven enterprise UI pattern that scales well with many menu items
- Sidebar provides better navigation hierarchy - users can see more menu items at once without clicking
- Better mobile experience with overlay-style navigation that slides in/out
- More efficient use of screen space - sidebar doesn't consume vertical space like top navbar
- Built-in responsive behavior handles mobile/tablet/desktop automatically
- Sidebar state persistence improves UX by remembering user preferences
- Active route highlighting and auto-expand functionality help users know exactly where they are
- Font Awesome icons provide visual clarity and faster menu item recognition
- Dark, fixed topbar matches sidebar theme and provides consistent navigation experience

**Implementation**:

- Created `resources/views/templates/partials/sidebar.blade.php` with complete sidebar structure using AdminLTE's `nav-sidebar` classes
- Created `resources/views/templates/partials/topbar.blade.php` with simplified top navigation (toggle button + user menu)
- Updated `resources/views/templates/main.blade.php`: Changed body class from `layout-top-nav layout-navbar-fixed` to `sidebar-mini layout-fixed layout-navbar-fixed`
- Converted all menu dropdowns to sidebar tree structure with `has-treeview` and `nav-treeview` classes
- Implemented active route highlighting using `request()->routeIs()` pattern matching
- Added JavaScript for sidebar state persistence (localStorage) and auto-expand active parent menus
- Updated login page to v.4.0 with modern gradient design highlighting sidebar improvements
- Fixed `/home` route issue by updating `RouteServiceProvider::HOME` constant and adding redirect route
- Preserved old navbar and menu partials for rollback reference

**Review Date**: 2025-07-XX (evaluate user feedback, assess navigation efficiency, consider additional sidebar enhancements like search or favorites)

---

### Decision: Direct SAP B1 Journal Entry Submission via Service Layer API - 2025-11-20

**Context**: Users were manually exporting verification journals to Excel and copy-pasting data into SAP B1 Journal Entry module. This workflow was time-consuming, error-prone, lacked audit trail, and required manual updates of SAP journal numbers in the local system. Business needed automated submission with proper error handling, transaction safety, and complete audit logging.

**Options Considered**:

1. **Continue Manual Excel Export/Import Workflow**
   - ✅ Pros: No code changes, users familiar with process, no integration complexity
   - ❌ Cons: Time-consuming, error-prone, no audit trail, manual data entry errors, no automatic SAP journal number updates, inconsistent data

2. **SAP B1 DI API (Data Interface)**
   - ✅ Pros: Batch processing capability, supports multiple document types
   - ❌ Cons: Requires file-based import, more complex setup, not real-time, requires file server access

3. **SAP B1 Service Layer REST API (Chosen)**
   - ✅ Pros: Real-time submission, programmatic control, proper error handling, supports all journal entry fields (projects, cost centers), automatic session management, standard REST API patterns
   - ❌ Cons: Requires Service Layer configuration, cookie-based session management complexity

**Decision**: Implement direct journal entry submission using SAP B1 Service Layer REST API with comprehensive error handling, database transactions, and audit logging.

**Rationale**:

- Service Layer API provides real-time, programmatic control over journal entry creation with proper error responses
- Cookie-based session management (handled by Guzzle CookieJar) is standard for SAP B1 Service Layer and can be abstracted in service class
- Database transactions ensure atomicity - local records only update if SAP submission succeeds, preventing data inconsistencies
- Comprehensive audit logging (`SapSubmissionLog`) provides complete trail of all submission attempts for compliance and troubleshooting
- Permission-based access control ensures only authorized users can submit journals
- Mandatory confirmation dialog prevents accidental submissions and informs users about posting implications
- Automatic SAP Document Number extraction and local database update eliminates manual data entry errors
- UI enhancements (button visibility, status indicators) provide clear feedback about submission status

**Implementation**:

- Created `App\Services\SapService` for SAP B1 Service Layer communication with cookie-based session management, automatic re-login on expiration, and journal entry creation
- Built `App\Services\SapJournalEntryBuilder` to construct journal entry payloads from `VerificationJournal` records with validation
- Added database migrations for submission tracking (`sap_submission_attempts`, `sap_submission_status`, etc.) and audit logging (`sap_submission_logs` table)
- Implemented `SapSyncController::submitToSap()` with permission checks, transaction management, error handling, and local record updates
- Enhanced UI with submit button, confirmation modal, status indicators, and disabled cancel button after posting
- Configured SAP B1 General Settings to make cash flow assignment optional ("Warning Only") to resolve "Cash flow assignment is mandatory" errors
- Added environment variables and config mapping for SAP B1 connection details

**Review Date**: 2026-02-20 (evaluate submission success rate, consider batch processing for multiple journals, assess need for cash flow assignment automation)

---

### Decision: VAT Sales Page Filtering Based on SAP Submission Status - 2026-01-XX

**Context**: The VAT Sales pages used `doc_num` and `faktur_no` fields to determine incomplete vs complete status, but these fields don't accurately reflect SAP B1 submission status. Documents could have `doc_num` set manually without being posted to SAP B1, causing confusion about which documents are actually submitted. Users needed clear separation between documents not yet posted to SAP (requiring submission) and documents already posted (showing SAP document numbers). The Complete tab showed generic "DocNum" column instead of specific SAP AR Invoice and Journal Entry numbers.

**Options Considered**:

1. **Keep Existing Filtering (doc_num/faktur_no)**
   - ✅ Pros: No code changes, existing data structure maintained
   - ❌ Cons: Inaccurate status reflection, manual doc_num can be set without SAP submission, confusion about submission status, generic DocNum column doesn't show SAP-specific numbers

2. **Add New Status Field**
   - ✅ Pros: Explicit status tracking, clear separation of concerns
   - ❌ Cons: Requires migration, need to maintain status field separately, potential for status drift

3. **Use SAP Submission Tracking Fields (Chosen)**
   - ✅ Pros: Accurate status reflection (sap_ar_doc_num directly indicates SAP submission), single source of truth, automatic status update on submission, shows specific SAP document numbers
   - ❌ Cons: Requires existing SAP submission infrastructure (already implemented)

**Decision**: Change filtering logic to use `sap_ar_doc_num` field (SAP B1 AR Invoice document number) as the source of truth for incomplete vs complete status. Incomplete tab shows documents where `sap_ar_doc_num IS NULL` with "Submit to SAP" button. Complete tab shows documents where `sap_ar_doc_num IS NOT NULL` with "AR Invoice DocNum" and "JE Num" columns replacing generic "DocNum" column.

**Rationale**:

- `sap_ar_doc_num` is set automatically when AR Invoice is successfully created in SAP B1, providing accurate submission status
- Single source of truth eliminates confusion from manually set `doc_num` values
- Documents automatically move to Complete tab after successful submission (sap_ar_doc_num is set)
- Displaying specific SAP document numbers (AR Invoice DocNum, JE Num) provides better traceability and audit trail
- Action buttons reflect document status - Submit button only for incomplete documents, Submitted badge for complete documents
- Clear workflow separation improves user experience and reduces errors

**Implementation**:

- Updated `VatController::data()` method: Changed filtering from `doc_num`/`faktur_no` to `sap_ar_doc_num` (NULL for incomplete, NOT NULL for complete)
- Added `sap_ar_doc_num` and `sap_je_num` columns to DataTable response
- Updated Complete tab view: Removed "DocNum" column, added "AR Invoice DocNum" and "JE Num" columns
- Updated action views: Incomplete shows "Submit to SAP" button, Complete shows "Submitted" badge
- No database changes required (sap_ar_doc_num field already exists from SAP AR Invoice integration)

**Review Date**: 2026-07-XX (evaluate user feedback, assess workflow efficiency, consider additional status indicators or filtering options)

---

### Decision: SweetAlert2 Confirmation Flow for SAP Sync Submissions - 2025-11-20

**Context**: Phase 2 feedback required replacing browser-native confirmations with a consistent, informative dialog that reiterates posting implications before touching SAP. The existing mix of Bootstrap modals (single submission) and `window.confirm()` (bulk submission + cancel SAP info) led to uneven UX, no structured summary, and no way to show previous errors before re-submitting.

**Options Considered**:

1. **Keep Bootstrap Modal + Browser Confirm**
   - ✅ Pros: Already implemented, no new dependency
   - ❌ Cons: Modal duplicated markup per page, not reusable for bulk actions, browser confirm cannot show context-rich content or branding

2. **Build Custom Vue/Blade Modal Component**
   - ✅ Pros: Full control over markup, can embed complex HTML
   - ❌ Cons: Requires additional JS framework plumbing on pages that currently do not bootstrap Vue; more boilerplate to reuse between single and bulk flows

3. **Adopt SweetAlert2 Across SAP Sync (Chosen)**
   - ✅ Pros: Already bundled with AdminLTE, supports HTML summaries, consistent look/feel, promise-based flow for single + bulk actions, accessible loading state
   - ❌ Cons: Need to refactor legacy modal/confirm code and ensure SweetAlert assets are loaded globally

**Decision**: Use SweetAlert2 for all SAP Sync confirmation dialogs (single submission, bulk submission, cancel SAP info) with shared helper logic so UX is consistent and informative.

**Rationale**:

- Business explicitly requested SweetAlert confirmations to replace native prompts
- SweetAlert2 enables us to inject journal summaries, important notes, and previous errors without maintaining duplicate modal markup
- Promise-based API works for both single-form submit and bulk submit flows, letting us show spinner/loading state until the form posts
- Shared Blade partial (`resources/views/accounting/sap-sync/partials/bulk-table-script.blade.php`) centralizes DataTable + SweetAlert wiring, reducing eight duplicate scripts
- Hidden form submission pattern keeps Laravel CSRF + validation unchanged while upgrading UX

**Implementation**:

- Replaced Bootstrap modal in `resources/views/accounting/sap-sync/show.blade.php` with SweetAlert summary builder + hidden form submit; added SweetAlert guard to "Cancel SAP Info" flow
- Added `$submissionMeta` payload (journal info, attempts, errors) and new JS helper `buildSubmissionSummaryHtml()` to render rich SweetAlert content
- Created `resources/views/accounting/sap-sync/partials/bulk-table-script.blade.php` for all project tabs: initializes DataTables, tracks checkbox selection, and shows SweetAlert before queuing hidden inputs + submitting the bulk form
- Updated all project-specific Blade views (`000H`, `001H`, `017C`, `021C`, `022C`, `023C`, `025C`, `026C`) to include the shared partial instead of inline scripts

**Review Date**: 2026-01-31 (verify SweetAlert2 approach covers any new SAP Sync actions, assess need for per-record previews or queued retries)

---

### Decision: Switch SAP Transactions to SAP-Bridge Account Statement API - 2025-11-18

**Context**: The cashier SAP Transactions page still queried a legacy GL microservice via `/api/v1/statements`. Business moved account-statement ownership to the SAP-Bridge app exposing `/api/account-statements` with richer metadata (opening/closing balances, summaries). We needed to realign the UI with the new source, enforce SAP-side constraints (6-month window), and surface accurate error feedback to cashiers.

**Options Considered**:

1. **Continue Using GL Aggregator**
    - ✅ Pros: Zero code changes, existing response shape already matches DataTables columns
    - ❌ Cons: Deprecated service, missing new balances/summary fields, inconsistent data vs SAP-Bridge, security drift (old API key header)
2. **Call SAP-Bridge Directly from Controller**
    - ✅ Pros: Smaller diff, controller holds HTTP logic
    - ❌ Cons: Tight coupling, duplicated config handling, harder to reuse/testing, no central error normalization
3. **Dedicated SapBridge Account Statement Service (Chosen)**
    - ✅ Pros: Centralizes HTTP config, timeout, headers, and error handling; controller stays focused on validation; easy to mock in tests; prepares for future SAP endpoints; aligns with service-layer patterns already in app
    - ❌ Cons: Additional class + exception to maintain

**Decision**: Implement `App\Services\SapBridge\AccountStatementService` and migrate the cashier UI/controller to consume SAP-Bridge `/api/account-statements` payloads directly.

**Rationale**:

- Service encapsulation keeps API details (base URL, headers, timeout) in one place and exposes a simple `getAccountStatement()` contract.
- SAP-Bridge response is richer than GL aggregator, so passing it through enables opening/closing balance + summary widgets without extra transforms.
- Standardized `SapBridgeException` lets controllers return user-friendly errors that mirror SAP responses (401/404/422).
- Client-side + server-side 6-month validation prevents unnecessary SAP requests and echoes SAP’s hard rule.
- Updating config/env (`SAP_BRIDGE_URL`, `SAP_BRIDGE_API_KEY`, `SAP_BRIDGE_TIMEOUT`) keeps deployments declarative.

**Implementation**:

- Added `sap_bridge` config block + env placeholders; created `App\Services\SapBridge\AccountStatementService` and `App\Exceptions\SapBridgeException`.
- Refactored `SapTransactionController::data()` for request validation, 6-month guard, and service injection; response now mirrors SAP-Bridge schema.
- Rebuilt `resources/views/cashier/sap-transactions/index.blade.php` DataTable + summary cards to consume SAP fields (posting_date, debit_amount, running_balance, tx_num, unit_no) and show balances/period info.
- Documented flow in `docs/architecture.md`, added ADR in `docs/decisions.md`, logged work in `docs/todo.md` and `MEMORY.md`.

**Review Date**: 2026-02-18 (confirm SAP-Bridge uptime, consider caching/pagination if traffic grows).

---

### Decision: Project Field Granularity - Detail Level vs Document Level - 2025-10-31

**Context**: Realization documents track expenses across multiple detail items. Project assignment determines budget allocation and financial reporting. Original implementation had project field at realization (document) level, assuming all expense items in one realization belong to same project. However, users frequently need to split single realization across multiple projects (e.g., expense report with items from both HO and APS projects).

**Options Considered**:

1. **Document-Level Project (Original)**

    - ✅ Pros: Simple UI, single dropdown at document level, easy to implement
    - ❌ Cons: Forces all expense items to same project, requires splitting into multiple realizations, poor UX for mixed-project expenses

2. **Detail-Level Project (Granular Approach)**

    - ✅ Pros: Allows mixed projects in single realization, more accurate budget tracking, better financial reporting, aligns with how users actually incur expenses
    - ❌ Cons: More complex UI (dropdown per row), requires editing each detail item

3. **Dual Fields (Document Default + Detail Override)**
    - ✅ Pros: Convenience of default with flexibility of override
    - ❌ Cons: Confusing UX (which value is used?), complex validation logic, potential data inconsistencies

**Decision**: Detail-Level Project (Granular Approach)

**Rationale**:

-   Real-world expense patterns: Users frequently incur expenses across multiple projects in single trip/timeframe
-   Accurate budget tracking: Financial reporting needs exact project attribution per expense item
-   Audit requirements: Each expense line must trace to specific project budget
-   Approver flexibility: During approval review, approvers can correct project assignments per item
-   Database integrity: Project field in realization_details table provides proper data normalization
-   Inline editing pattern: Per-row dropdowns align with existing inline edit UI for departments and amounts
-   Single source of truth: No ambiguity about which project each expense belongs to

**Implementation**:

**Database Schema**:

-   Removed project validation from realization document level
-   Project field remains in realization_details table (already existed)
-   Validation moved to detail level: `details.*.project` validation in both controllers
-   Nullable field allowing gradual data migration without breaking existing records

**Controller Updates**:

-   ApprovalRequestRealizationController: Pass projects list, validate per detail, save to realization_details
-   ApprovalRequestPayreqController: Same changes for consistency (both workflows edit realization_details)
-   Eager load projects: `Project::where('is_active', 1)->orderBy('code')->get()`

**Frontend Changes**:

-   Added Project column to details table between Department and Amount
-   Project dropdown per row in edit mode (like department dropdown)
-   JavaScript: transformRowToEdit() includes project dropdown generation
-   JavaScript: collectDetailsData() includes project value from each row
-   Display: Shows project code in view mode, dropdown in edit mode
-   New row creation: Project dropdown included in addNewRow() template

**User Experience**:

-   View mode: Project code displayed as plain text per row
-   Edit mode: Searchable dropdown per row with all active projects
-   Clear visual grouping: Project column positioned logically between Department and Amount
-   Consistent with existing patterns: Matches department dropdown UX
-   Real-time updates: AJAX save without page refresh

**Review Date**: 2026-04-30 (after 6 months to assess if detail-level granularity creates any workflow issues)

**Related Decision**: This aligns with existing granularity decisions where detail-level data (description, amount, department) provides better tracking than document-level aggregation.

---

### Decision: Form UX Enhancement Pattern for Complex Operations - 2025-10-23

**Context**: The loan installment generation page required users to generate multiple database records (potentially 36+ installments) in a single operation. Users needed to understand what would be generated, verify the calculations, and select the correct account from 20+ bank accounts. Original form had minimal guidance with only basic labels and account numbers, leading to potential errors and user uncertainty.

**Options Considered**:

1. **Minimal Form (Original Approach)**

    - ✅ Pros: Simple, fast to load, minimal code
    - ❌ Cons: Users uncertain about what will happen, high error rate, poor account selection (numbers only), no preview

2. **Multi-Step Wizard**

    - ✅ Pros: Guided experience, clear progression
    - ❌ Cons: More clicks required, complex state management, slower workflow for experienced users

3. **Single-Page Enhanced Form with Real-Time Preview**
    - ✅ Pros: All info on one screen, instant feedback, preview builds confidence, maintains workflow speed
    - ❌ Cons: More frontend JavaScript required

**Decision**: Single-Page Enhanced Form with Real-Time Preview

**Rationale**:

-   Preview functionality builds user confidence by showing exactly what will be generated before submission
-   Real-time calculations reduce errors and provide immediate feedback
-   Contextual information (loan details at top) helps users verify they're working with correct record
-   Enhanced dropdown options (account number + name) dramatically improve usability without requiring extra clicks
-   Auto-calculations with editable fields provide smart defaults while maintaining flexibility
-   Single-page approach maintains workflow efficiency for experienced users
-   Validation and confirmation prevent accidental bulk operations

**Implementation**:

**Form Enhancements**:

-   Loan information box at top (principal, tenor, creditor) provides context
-   Field labels in user's language (Indonesian) with descriptive help text
-   Auto-calculated installment amount (principal ÷ tenor) with editable override
-   Smart defaults: next month's first day for due date, 1 for starting number
-   IDR prefix on amount field for currency clarity
-   Required field indicators (red asterisks) for clear guidance

**Account Selection Enhancement**:

-   Format: "account_number - account_name" (e.g., "11201001 - Mandiri IDR - 149.0004194751")
-   Select2 integration for searchable dropdown
-   Simplified from original verbose format to essential information only
-   Maintains clarity while reducing visual clutter

**Real-Time Preview**:

-   JavaScript-powered generation summary
-   Shows: number of installments, installment range (#1 to #36), start date, amount per month, total amount, selected account
-   Updates instantly as user changes any field
-   Yellow warning box visually distinct from form inputs
-   Indonesian number formatting (171.016.000 vs 171016000)

**Validation & Confirmation**:

-   Client-side validation before submission
-   Confirmation dialog showing count of installments to be generated
-   Loading state during submission ("Generating..." with spinner)
-   Error messages clearly displayed near relevant fields

**Technical Stack**:

-   Blade template with inline JavaScript
-   Select2 for enhanced dropdown UX
-   Bootstrap 4 for responsive grid layout
-   No additional dependencies required

**Review Date**: 2026-04-23 (after 6 months to assess user feedback and error rates)

**Related Decision**: This pattern should be considered for other bulk operation forms in the system (e.g., bulk bilyet creation, bulk realization entries).

---

### Decision: Payment Method Enum for Installments - 2025-10-23

**Context**: Need to track HOW loan installments are paid. Some installments are paid via bilyet (check/BG), while others use auto-debit from bank accounts. Need a flexible system that supports multiple payment methods.

**Options Considered**:

1. **Boolean Field (is_bilyet_payment)**

    - ✅ Pros: Simple, straightforward
    - ❌ Cons: Only supports 2 payment types, not extensible, unclear semantics

2. **Payment Method Enum Field**

    - ✅ Pros: Clear semantics, extensible for future payment types, better reporting, explicit values
    - ❌ Cons: Slightly more complex than boolean

3. **Separate Boolean Fields (has_bilyet, is_auto_debit, etc.)**
    - ✅ Pros: Flexible
    - ❌ Cons: Can create conflicting states, hard to maintain, poor database design

**Decision**: Payment Method Enum Field with values: ['bilyet', 'auto_debit', 'cash', 'transfer', 'other']

**Rationale**:

-   Provides clear, explicit tracking of payment method
-   Extensible for future payment types (e.g., e-wallet, cryptocurrency)
-   Enables better analytics and reporting (breakdown by payment method)
-   Prevents conflicting states (can't be both bilyet AND auto-debit)
-   Industry-standard pattern for categorical data

**Implementation**:

-   Added `payment_method` enum column to `installments` table
-   Created constants in Installment model: `PAYMENT_METHODS` array
-   Business rule: If payment_method='bilyet', bilyet_id must be set; if 'auto_debit', bilyet_id must be NULL

**Review Date**: 2026-04-23 (after 6 months of usage to assess if additional payment methods needed)

---

### Decision: Purpose Field for Bilyets - 2025-10-23

**Context**: Not all bilyets are for loan payments. Many are for operational expenses (vendor payments, petty cash, etc.). Need to distinguish between different bilyet purposes for accurate reporting and financial categorization.

**Options Considered**:

1. **Infer from loan_id (if NULL = operational, if set = loan payment)**

    - ✅ Pros: No new field needed
    - ❌ Cons: Unclear semantics, can't have loan-related operational bilyets, implicit logic hard to understand

2. **Purpose Enum Field**

    - ✅ Pros: Explicit categorization, clear reporting, supports mixed use cases, better analytics
    - ❌ Cons: One additional field in database

3. **Separate Tables (bilyet_loans vs bilyet_operational)**
    - ✅ Pros: Completely separate concerns
    - ❌ Cons: Code duplication, harder to query across all bilyets, complex migrations

**Decision**: Purpose Enum Field with values: ['loan_payment', 'operational', 'other']

**Rationale**:

-   Explicit is better than implicit (Zen of Python applies to database design too)
-   Enables accurate financial reporting by purpose
-   Supports edge cases (e.g., loan-related bilyets that aren't direct payments)
-   Default 'operational' maintains backward compatibility with existing bilyets
-   Indexed field allows fast filtering in reports

**Implementation**:

-   Added `purpose` enum column to `bilyets` table (nullable, default='operational')
-   Created constants in Bilyet model: `PURPOSE_LABELS` array
-   Business rule: If purpose='loan_payment', should have loan_id set (not enforced for flexibility)

**Review Date**: 2026-04-23 (evaluate if additional purpose categories needed)

---

### Decision: Nullable bilyet_id in Installments (Not Required) - 2025-10-23

**Context**: When linking installments to bilyets, need to decide if every paid installment MUST have a bilyet_id. Auto-debit payments don't use bilyets.

**Options Considered**:

1. **Required bilyet_id for All Payments**

    - ✅ Pros: Forced consistency, every payment has documentation
    - ❌ Cons: Artificial bilyets for auto-debit, data pollution, doesn't match reality

2. **Nullable bilyet_id with payment_method Field**

    - ✅ Pros: Matches reality, clean data, flexible, supports multiple payment types
    - ❌ Cons: Requires additional field (payment_method), slightly more complex logic

3. **Always Create Bilyet Records (Mark as Virtual)**
    - ✅ Pros: Unified data model
    - ❌ Cons: Confusing (bilyet that doesn't exist physically), complicates bilyet management

**Decision**: Nullable bilyet_id with payment_method Field

**Rationale**:

-   Reflects business reality: some installments paid via auto-debit (no physical bilyet)
-   Clean data: only create bilyets when they actually exist
-   payment_method field explicitly tracks HOW payment was made
-   Flexible for future payment types
-   Historical data compatibility: existing installments have NULL bilyet_id

**Implementation**:

-   bilyet_id as nullable foreign key with onDelete('set null')
-   payment_method as nullable enum (NULL for unpaid installments)
-   Business validation: if payment_method='bilyet', bilyet_id must be set

**Review Date**: Not scheduled (fundamental design unlikely to change)

---

### Decision: Backward Compatibility for Existing bilyet_no Field - 2025-10-23

**Context**: Installments table has existing `bilyet_no` text field with historical data. New implementation adds `bilyet_id` FK. Need to decide how to handle both fields.

**Options Considered**:

1. **Migrate All Historical Data to FK**

    - ✅ Pros: Clean schema, single source of truth
    - ❌ Cons: Complex migration, risk of data loss, manual matching required

2. **Keep Both Fields for Backward Compatibility**

    - ✅ Pros: No data loss, preserves history, gradual migration possible
    - ❌ Cons: Two fields for same concept, potential confusion

3. **Archive Old Data, Use Only FK Going Forward**
    - ✅ Pros: Clean going forward
    - ❌ Cons: Loses historical context, breaks existing reports

**Decision**: Keep Both Fields (bilyet_no for historical, bilyet_id for new)

**Rationale**:

-   Preserves all historical data without risk
-   New records use proper FK relationship (bilyet_id)
-   Old records keep text reference (bilyet_no)
-   Reports can handle both: show bilyet_no if no bilyet_id, else show linked bilyet
-   Gradual migration possible if needed in future
-   Non-breaking change for existing functionality

**Implementation**:

-   Keep existing `bilyet_no` varchar field
-   Add new `bilyet_id` nullable FK
-   Views prioritize bilyet_id (show linked bilyet), fallback to bilyet_no
-   Future cleanup: can migrate matching records when time permits

**Review Date**: 2026-01-23 (after 3 months, assess if manual migration of historical data worthwhile)

---

### Decision: Modern CSS Animation vs JavaScript Marquee for Exchange Rate Ticker - 2025-10-23

**Context**: The dashboard exchange rate ticker was using the deprecated HTML `<marquee>` tag, which is obsolete and not supported in modern browsers. Need to choose a modern, maintainable solution for scrolling text animation.

**Options Considered**:

1. **Pure CSS Animation (Keyframes)**

    - ✅ Pros: No JavaScript required, better performance, modern standard, smooth animations, easier to maintain
    - ❌ Cons: Requires duplicate text content for seamless looping

2. **JavaScript Animation (requestAnimationFrame)**

    - ✅ Pros: Full control over animation, dynamic content handling
    - ❌ Cons: JavaScript dependency, potential performance issues, more complex code

3. **Third-party Library (Marquee.js, etc.)**
    - ✅ Pros: Feature-rich, cross-browser compatible
    - ❌ Cons: Additional dependency, bundle size increase, overkill for simple use case

**Decision**: Pure CSS Animation with keyframes

**Rationale**: CSS animations provide the best balance of performance, maintainability, and modern browser support. The approach is straightforward (duplicate text for seamless loop), has no JavaScript dependency, and aligns with modern web standards. The small trade-off of duplicating content is negligible compared to the benefits.

**Implementation**:

-   Replaced `<marquee>` with CSS `@keyframes scroll-left` animation
-   Text duplicated once for seamless infinite loop
-   Animation duration: 30 seconds for readable speed
-   Added gradient background and rotating icon for visual enhancement

**Review Date**: 2026-01-01 (review if browser support changes)

---

### Decision: Gradient Design System for Dashboard Components - 2025-10-23

**Context**: Dashboard needed visual refresh with modern design patterns. Need to establish consistent design language across all components while maintaining professional appearance for financial ERP system.

**Options Considered**:

1. **Flat Design (Single Colors)**

    - ✅ Pros: Simple, minimal, clean appearance
    - ❌ Cons: Less visually engaging, harder to create hierarchy, dated appearance

2. **Gradient Design System**

    - ✅ Pros: Modern appearance, creates visual hierarchy, engaging UI, professional look
    - ❌ Cons: Can be overwhelming if overused, requires careful color selection

3. **Neumorphism/3D Effects**
    - ✅ Pros: Trendy, unique appearance
    - ❌ Cons: Accessibility concerns, may look dated quickly, harder to implement

**Decision**: Gradient Design System with consistent color palette

**Rationale**: Gradients provide modern, professional appearance suitable for enterprise ERP while creating clear visual hierarchy. Careful application prevents overwhelming users. Color scheme aligned with AdminLTE 3 patterns ensures consistency with overall system design.

**Implementation**:

-   Purple gradients: Primary actions and charts (`#667eea → #764ba2`)
-   Orange/Yellow gradients: Warnings and pending items (`#f6d365 → #fda085`)
-   Pink/Red gradients: Announcements (`#f093fb → #f5576c`)
-   Cyan gradients: Information and activities (`#4facfe → #00f2fe`)
-   Teal/Purple gradients: Team sections (`#30cfd0 → #330867`)
-   Green gradients: Success states (`#11998e → #38ef7d`)
-   Red gradients: Danger/Overdue states (`#ee0979 → #ff6a00`)

**Review Date**: 2025-04-01 (review user feedback and design trends)

---

### Decision: Empty State Messaging Strategy - 2025-10-23

**Context**: Dashboard components showing no data (payreqs, realizations, team members) need appropriate messaging to inform users without creating concern.

**Options Considered**:

1. **Neutral Message ("No items found")**

    - ✅ Pros: Factual, clear
    - ❌ Cons: Feels negative, doesn't celebrate cleared workload

2. **Positive Messaging ("All caught up!")**

    - ✅ Pros: Encouraging, celebrates completion, improves UX
    - ❌ Cons: May seem dismissive if user expects data

3. **Actionable Message ("Create new item")**
    - ✅ Pros: Provides next steps
    - ❌ Cons: Not appropriate when no action needed (completed work)

**Decision**: Positive messaging with context ("All caught up!" + icon)

**Rationale**: Financial systems often create anxiety around pending tasks. Positive messaging acknowledges completed work and reduces stress. Combined with cheerful icon (check circle) and supplementary text, creates encouraging user experience while remaining professional.

**Implementation**:

-   Empty payreqs: "No ongoing payreqs" + "All caught up!" + green check icon
-   Empty realizations: "No ongoing realizations" + "All caught up!" + green check icon
-   Empty team: "No team data available" + "Team information will appear here" (neutral, as data absence is informational)

**Review Date**: 2025-04-01 (review user feedback)

---

### Decision: Currency Formatting with Type Safety - 2025-10-23

**Context**: Dashboard displays amounts from various sources (database queries, controller calculations). PHP's `number_format()` requires float but data sometimes arrives as strings, causing type errors.

**Options Considered**:

1. **Fix at Controller Level (ensure float return types)**

    - ✅ Pros: Type safety throughout, cleaner views
    - ❌ Cons: Requires changing multiple controllers, potential breaking changes

2. **Fix at View Level (cast to float before formatting)**

    - ✅ Pros: Localized fix, doesn't affect controllers, defensive programming
    - ❌ Cons: Repeated casting in views, addresses symptom not root cause

3. **Mixed Approach (controller returns strings, view displays as-is)**
    - ✅ Pros: Works if controller formats correctly
    - ❌ Cons: Breaks when re-formatting needed, discovered in team section issue

**Decision**: Cast to float at view level `(float)$amount` before `number_format()`

**Rationale**: Defensive programming approach provides type safety at point of use without requiring extensive controller refactoring. View-level casting catches edge cases and provides consistent handling regardless of data source. Small performance cost is negligible compared to reliability gain.

**Implementation**:

-   All `number_format()` calls updated to `number_format((float)$amount, ...)`
-   Applied to payreqs, realizations, and team section amounts
-   Special handling for team section: removed duplicate formatting by letting controller format with 2 decimals

**Review Date**: 2025-07-01 (consider standardizing controller return types)

---

### Decision: Warning-Based Validation for Approver Edits - 2025-10-22

**Context**: When approvers edit realization details, the total amount may differ from the original. Need to decide whether to block saves when amounts don't match or allow with warnings.

**Options Considered**:

1. **Block save if amounts don't match**

    - ✅ Pros: Enforces data integrity, prevents unintentional amount changes
    - ❌ Cons: Reduces approver flexibility, may require multiple edit cycles, blocks legitimate corrections

2. **Warning-only approach (allow save anyway)**

    - ✅ Pros: Approver flexibility, faster workflow, handles legitimate corrections
    - ❌ Cons: Potential for unnoticed errors, requires approver attention to warnings

3. **Require justification field when amounts differ**
    - ✅ Pros: Audit trail, forces acknowledgment of changes
    - ❌ Cons: Additional friction, may slow down legitimate corrections

**Decision**: Warning-only approach with visual indicators

**Rationale**: Approvers are trusted financial controllers who may need to correct submitted amounts. Blocking saves would require requestors to resubmit, creating unnecessary workflow delays. Visual warnings (yellow/orange badges, clear messaging) provide awareness while maintaining workflow efficiency. The `modified_by_approver` tracking provides audit trail without requiring manual justification.

**Implementation**:

-   Real-time total calculation with color-coded variance display
-   Warning alert when amounts differ (not blocking)
-   Success message mentions "document needs to be reprinted"
-   User list shows "⚠ Needs Reprint" badge
-   Database tracks modification timestamp and approver ID

**Review Date**: 2026-04-22

### Decision: Permission-Based Feature Access for Approver Edits - 2025-10-22

**Context**: Need to control which approvers can edit submitted realization details while maintaining security and audit compliance.

**Options Considered**:

1. **All approvers can edit by default**

    - ✅ Pros: Simple implementation, no permission management needed
    - ❌ Cons: Security risk, no granular control, potential for unauthorized modifications

2. **Permission-based access control**

    - ✅ Pros: Granular control, audit trail, role-based security, flexible assignment
    - ❌ Cons: Requires permission setup, admin overhead for permission assignment

3. **Hardcoded role check (e.g., only superadmin)**
    - ✅ Pros: Simple implementation, clear rules
    - ❌ Cons: Inflexible, requires code changes to adjust access

**Decision**: Permission-based access with `edit-submitted-realization` permission

**Rationale**: Spatie Laravel Permission already in use throughout application. Permission-based approach provides maximum flexibility while maintaining security. Different organizations/projects may have different approval workflows requiring different access levels. Blade `@can` directive provides clean UI implementation.

**Implementation**:

-   New permission: `edit-submitted-realization`
-   Blade directive: `@can('edit-submitted-realization')` around Edit Details button
-   Permission assignment through existing role management UI
-   No controller-level authorization (UI hiding sufficient for this feature)

**Review Date**: 2026-04-22

### Decision: Variance Display for Budget Tracking - 2025-10-22

**Context**: Need to provide visibility into differences between approved payreq amounts and actual realization detail totals for budget tracking and reconciliation purposes.

**Options Considered**:

1. **Display variance only in edit mode**

    - ✅ Pros: Cleaner UI in view mode, less information overload
    - ❌ Cons: Users must enter edit mode to see variance, less transparent

2. **Display variance in both view and edit modes**

    - ✅ Pros: Always visible, transparent budget tracking, helps users understand differences
    - ❌ Cons: Additional table row, slight UI complexity

3. **Calculate variance but don't display it**
    - ✅ Pros: Simplest UI
    - ❌ Cons: Users can't see budget differences, poor transparency

**Decision**: Display variance in both view and edit modes with real-time updates during editing

**Rationale**: Budget variance is critical financial information that users and approvers need for decision-making. Making it always visible improves transparency and helps identify discrepancies quickly. Real-time calculation during editing provides immediate feedback on budget impacts. Formula (Payreq Amount - Total Detail Amount) is simple and intuitive for users.

**Implementation**:

-   Added variance row in table footer for both approval pages
-   Formula: `Payreq Amount - Total Detail Amount`
-   Negative variance indicates overspending (total > payreq)
-   Positive variance indicates underspending (total < payreq)
-   Real-time JavaScript calculation updates variance during editing
-   Indonesian number formatting for consistency

**Review Date**: 2026-10-22

### Decision: Single Permission for Dual Approval Edit Workflows - 2025-10-22

**Context**: With edit functionality needed on both realization approval and payreq approval pages, need to decide whether to use one permission or separate permissions.

**Options Considered**:

1. **Single permission for both workflows**

    - ✅ Pros: Simpler administration, logical grouping (both edit realization details), fewer permissions to manage
    - ❌ Cons: Less granular control, can't restrict to only one workflow

2. **Separate permissions (edit-realization-approval, edit-payreq-approval)**

    - ✅ Pros: Maximum granular control, can assign differently
    - ❌ Cons: More complex permission management, likely both always assigned together anyway

3. **No permission, available to all approvers**
    - ✅ Pros: Simplest implementation
    - ❌ Cons: Security risk, no control over feature access

**Decision**: Single permission `edit-submitted-realization` for both workflows

**Rationale**: Both workflows edit the same underlying data (realization_details table). Approvers who need to edit one type likely need to edit both. Single permission reduces administrative overhead while providing necessary access control. The permission name focuses on what is being edited (submitted realizations) rather than which page is being used.

**Implementation**:

-   One permission: `edit-submitted-realization`
-   Guards Edit Details button on both pages with same `@can` directive
-   Both controllers use same updateDetails() logic pattern
-   Unified user experience across both approval types

**Review Date**: 2026-10-22

### Decision: Laravel 10+ with Modern Architecture - 2024-05-01

**Context**: Need for a robust, scalable accounting system with modern PHP practices

**Options Considered**:

1. **Laravel 10+ with new skeleton structure**

    - ✅ Pros: Latest features, improved performance, modern middleware approach, auto-discovery
    - ❌ Cons: Learning curve for new structure, some packages may not be compatible yet

2. **Laravel 9 with traditional structure**
    - ✅ Pros: Stable, well-documented, extensive package support
    - ❌ Cons: Older PHP version, missing modern features, traditional service provider approach

**Decision**: Laravel 10+ with new skeleton structure

**Rationale**: Future-proof architecture, better performance, modern PHP 8.1+ features, and improved developer experience

**Implementation**:

-   Service providers registered in `bootstrap/providers.php`
-   Middleware by class name in routes
-   Event listeners auto-registered with type hints
-   New view creation with `php artisan make:view`

**Review Date**: 2025-06-01

### Decision: Spatie Laravel Permission for RBAC - 2024-06-01

**Context**: Need for comprehensive role-based access control with granular permissions

**Options Considered**:

1. **Spatie Laravel Permission**

    - ✅ Pros: Mature, well-maintained, granular permissions, middleware support, extensive documentation
    - ❌ Cons: Additional package dependency, learning curve

2. **Custom RBAC implementation**

    - ✅ Pros: Full control, no external dependencies
    - ❌ Cons: Development time, security risks, maintenance burden

3. **Laravel Gates and Policies**
    - ✅ Pros: Built-in, lightweight
    - ❌ Cons: Limited granularity, more complex for complex permission structures

**Decision**: Spatie Laravel Permission

**Rationale**: Production-ready solution with proven security, comprehensive permission system, and excellent Laravel integration

**Implementation**:

-   Granular permissions for exchange rates (create, edit, delete, import, export)
-   Middleware protection on all routes
-   Exception handling with SweetAlert2 integration
-   Permission-based UI rendering

**Review Date**: 2025-06-01

### Decision: Yajra DataTables for Financial Reports - 2024-07-01

**Context**: Need for efficient handling of large financial datasets with search, pagination, and export capabilities

**Options Considered**:

1. **Yajra DataTables**

    - ✅ Pros: Laravel integration, server-side processing, built-in export features, extensive customization
    - ❌ Cons: Additional package dependency, learning curve

2. **Custom AJAX implementation**

    - ✅ Pros: Full control, no external dependencies
    - ❌ Cons: Development time, maintenance burden, potential performance issues

3. **Vue.js with custom components**
    - ✅ Pros: Modern frontend, reactive updates
    - ❌ Cons: Additional complexity, learning curve for team

**Decision**: Yajra DataTables

**Rationale**: Best balance of functionality, performance, and Laravel integration for financial reporting needs

**Implementation**:

-   Server-side processing for large datasets
-   Excel export integration
-   Search and filtering capabilities
-   Performance optimization with database indexes

**Review Date**: 2025-07-01

### Decision: Laravel Excel for Import/Export - 2024-08-01

**Context**: Need for bulk data import/export capabilities for financial data management

**Options Considered**:

1. **Laravel Excel (Maatwebsite)**

    - ✅ Pros: Laravel integration, extensive format support, chunk processing, validation
    - ❌ Cons: Additional package dependency, memory considerations for large files

2. **Custom CSV/Excel handling**

    - ✅ Pros: Full control, no external dependencies
    - ❌ Cons: Development time, format limitations, maintenance burden

3. **Direct database operations**
    - ✅ Pros: Fastest performance, direct control
    - ❌ Cons: Security risks, validation complexity, error handling challenges

**Decision**: Laravel Excel

**Rationale**: Production-ready solution with excellent Laravel integration, comprehensive format support, and built-in validation

**Implementation**:

-   Exchange rate bulk updates
-   Financial data exports
-   Template downloads
-   Chunk processing for large files

**Review Date**: 2025-08-01

### Decision: Performance Optimization Strategy - 2024-12-01

**Context**: Significant performance difference between localhost (1.51s) and server (15.29s) for DataTables

**Options Considered**:

1. **Database index optimization + query improvements**

    - ✅ Pros: Addresses root cause, significant performance gains, maintains UI consistency
    - ❌ Cons: Requires database migration, testing needed

2. **View rendering optimization (inline HTML)**

    - ✅ Pros: Faster rendering, reduced file I/O
    - ❌ Cons: UI changes, harder maintenance, potential security risks

3. **Caching implementation**
    - ✅ Pros: Immediate performance gains, reduced database load
    - ❌ Cons: Cache invalidation complexity, memory usage, data staleness

**Decision**: Database index optimization + query improvements

**Rationale**: Addresses the fundamental performance bottleneck while maintaining user experience and system reliability

**Implementation**:

-   Added critical database indexes for WHERE clauses and JOINs
-   Optimized date processing at database level
-   Improved search query efficiency
-   Maintained original view rendering for UI consistency

**Review Date**: 2025-03-01

# Architecture Decision Records

## ADR-005: Per-User DDS Department Code Implementation (2025-09-09)

### Status

Accepted

### Context

Initially, the Invoice Payment feature used a single department code from the `.env` file (`DDS_DEPARTMENT_CODE`) for all users. This approach limited flexibility as different users might need to work with different department codes when communicating with the DDS API.

### Decision

We implemented a user-specific DDS department code with the following architecture:

1. **User-level Configuration**: Added `dds_department_code` field to the `users` table
2. **Fallback Mechanism**: Maintained `.env` variable as fallback for backward compatibility
3. **Flexible Resolution**: Controller resolves department code from user first, then falls back to environment
4. **Enhanced Logging**: Added source tracking in logs (user vs. env)
5. **UI Integration**: Added field to user create/edit forms with validation

### Consequences

#### Positive

-   **Enhanced Flexibility**: Different users can work with different departments
-   **Backward Compatibility**: System continues to work with existing configuration
-   **Improved Debugging**: Logs now show source of department code
-   **Better User Experience**: Warning alerts when department code is missing
-   **Seamless Integration**: No changes needed to DDS API communication logic

#### Negative

-   **Additional Configuration**: Requires setting up department codes per user
-   **Data Migration**: Existing users need department codes assigned
-   **UI Complexity**: Additional field in user management forms
-   **Potential Confusion**: Two possible sources for department code

#### Risks

-   **Missing Configuration**: Users might not have department codes set
-   **Incorrect Codes**: Users might enter invalid department codes
-   **Permission Issues**: Users might need specific permissions for certain departments

### Implementation Details

#### Files Created/Modified

-   `database/migrations/2025_09_09_063420_add_dds_department_code_to_users_table.php` - Added field to users table
-   `app/Models/User.php` - Updated fillable attributes
-   `app/Http/Controllers/InvoicePaymentController.php` - Updated department code resolution
-   `app/Http/Controllers/UserController.php` - Added validation and handling for new field
-   `resources/views/users/index.blade.php` - Added field to create form
-   `resources/views/users/edit.blade.php` - Added field to edit form
-   `resources/views/invoice-payment/index.blade.php` - Added warning alert for missing department code

#### Key Features Implemented

-   User-specific department code storage
-   Environment variable fallback mechanism
-   Form field validation (nullable, string, max:20)
-   Warning alerts for missing configuration
-   Enhanced logging with source tracking

### Review Date

2025-12-09 (3 months from implementation)

## ADR-004: Invoice Payment Feature Implementation (2025-09-04)

### Status

Accepted

### Context

The project needed to integrate with a DDS (Document Distribution System) application to manage invoice payments. This required creating a new feature that could communicate with external APIs, display invoice data, and allow users to update payment statuses.

### Decision

We implemented a comprehensive Invoice Payment feature with the following architecture:

1. **Controller-based API Integration**: Used Laravel HTTP Client to communicate with DDS API endpoints
2. **DataTables Frontend**: Implemented DataTables for enhanced user experience with search, sorting, and pagination
3. **Modal-based Payment Updates**: Created a payment modal for updating invoice statuses
4. **Real-time Dashboard**: Built a dashboard with real-time statistics
5. **Permission-based Access**: Used Spatie Laravel Permission for access control

### Consequences

#### Positive

-   **Enhanced User Experience**: DataTables provide excellent search, sorting, and pagination capabilities
-   **Real-time Updates**: Tables refresh automatically after payment updates
-   **Secure Integration**: Proper API key authentication and CSRF protection
-   **Scalable Architecture**: Modular design allows for easy extension
-   **Comprehensive Logging**: Extensive logging for debugging and monitoring

#### Negative

-   **External Dependency**: System now depends on DDS API availability
-   **Complex Error Handling**: API integration requires robust error handling
-   **Environment Configuration**: Requires proper environment variable setup
-   **Performance Considerations**: API calls may introduce latency

#### Risks

-   **API Changes**: DDS API changes could break functionality
-   **Network Issues**: Network problems could affect invoice management
-   **Data Synchronization**: Potential for data inconsistencies between systems

### Implementation Details

#### Files Created/Modified

-   `app/Http/Controllers/InvoicePaymentController.php` - Main controller
-   `resources/views/invoice-payment/index.blade.php` - Frontend interface
-   `routes/cashier.php` - Route definitions

#### Environment Variables Required

-   `DDS_API_URL` - Base URL for DDS API
-   `DDS_API_KEY` - Authentication key for API access
-   `DDS_DEPARTMENT_CODE` - Department identifier

#### Key Features Implemented

-   Dashboard with invoice statistics
-   Waiting payment invoices table
-   Paid invoices table
-   Payment update functionality
-   Automatic table refresh
-   Manual refresh buttons
-   Error handling and logging

### Review Date

2025-12-04 (3 months from implementation)

---

## ADR-003: DataTables Integration (2024-12-31)

### Status

Accepted

### Context

The application needed to display large datasets with search, sorting, and pagination capabilities. Users required efficient ways to navigate through financial data.

### Decision

We integrated Yajra DataTables package for enhanced table functionality across the application.

### Consequences

#### Positive

-   **Improved User Experience**: Users can search, sort, and paginate data efficiently
-   **Reduced Server Load**: Client-side processing reduces server requests
-   **Consistent Interface**: Standardized table behavior across the application
-   **Responsive Design**: Tables adapt to different screen sizes

#### Negative

-   **JavaScript Dependency**: Requires JavaScript to be enabled
-   **Initial Load Time**: DataTables library increases initial page load time
-   **Complexity**: More complex than simple HTML tables

### Implementation Details

-   Used Yajra DataTables package
-   Implemented server-side processing for large datasets
-   Added custom styling for consistency
-   Integrated with existing AdminLTE theme

### Review Date

2025-06-30

---

## ADR-002: Role-Based Access Control (2024-12-31)

### Status

Accepted

### Context

The application needed fine-grained access control for different user roles and permissions. Security was a primary concern for financial data management.

### Decision

We implemented Spatie Laravel Permission package for role-based access control (RBAC).

### Consequences

#### Positive

-   **Flexible Permissions**: Granular control over user access
-   **Easy Management**: Simple permission assignment and revocation
-   **Audit Trail**: Clear tracking of user permissions
-   **Scalable**: Easy to add new roles and permissions

#### Negative

-   **Complexity**: More complex than simple role-based systems
-   **Performance**: Permission checks add overhead
-   **Maintenance**: Requires careful permission management

### Implementation Details

-   Used Spatie Laravel Permission package
-   Created middleware for permission checks
-   Implemented permission-based UI elements
-   Added permission seeding for initial setup

### Review Date

2025-06-30

---

## ADR-006: Comprehensive Database Schema Design (2025-01-15)

### Status

Accepted

### Context

The accounting system required a robust database schema to support complex financial workflows, multi-currency operations, approval processes, and external integrations while maintaining data integrity and performance.

### Decision

We implemented a comprehensive database schema with the following key design principles:

1. **Normalized Structure**: Proper normalization to eliminate data redundancy
2. **Foreign Key Constraints**: Enforced referential integrity across all relationships
3. **Soft Deletes**: Audit trail preservation for financial data
4. **Performance Indexes**: Strategic indexing for query optimization
5. **Multi-Currency Support**: Flexible currency and exchange rate management
6. **Approval Workflows**: Configurable approval stages and plans

### Consequences

#### Positive

-   **Data Integrity**: Foreign key constraints prevent orphaned records
-   **Audit Trail**: Soft deletes maintain complete transaction history
-   **Performance**: Strategic indexes provide 70-80% query performance improvement
-   **Flexibility**: Multi-currency support enables international operations
-   **Scalability**: Normalized structure supports growth and complexity
-   **Compliance**: Complete audit trail meets financial reporting requirements

#### Negative

-   **Complexity**: Complex relationships require careful query design
-   **Migration Overhead**: Schema changes require careful migration planning
-   **Storage**: Soft deletes increase storage requirements over time

### Implementation Details

#### Core Tables Implemented

-   **User Management**: users, departments, projects, roles, permissions
-   **Financial Core**: accounts, payreqs, realizations, cash_journals
-   **Budget Management**: rabs, anggarans, approval_stages, approval_plans
-   **Multi-Currency**: currencies, exchange_rates
-   **Document Management**: bilyets, dokumens, document_numbers
-   **External Integrations**: invoice_creations, verification_journals, lot_claims

#### Key Features

-   85+ migrations covering all financial entities
-   Comprehensive Eloquent relationships with proper foreign keys
-   Performance-optimized indexes on critical query fields
-   Soft delete implementation for audit trail preservation
-   Multi-currency support with daily exchange rate updates

### Review Date

2025-07-15 (6 months from implementation)

---

## ADR-007: Modular Architecture with Route Separation (2025-01-15)

### Status

Accepted

### Context

The accounting system needed to organize complex functionality across multiple modules (user management, financial operations, approvals, cashier operations, reporting) while maintaining clean separation of concerns and easy maintenance.

### Decision

We implemented a modular architecture with the following structure:

1. **Route Separation**: Separate route files for different modules
2. **Controller Organization**: Controllers organized by functional areas
3. **Service Layer**: Dedicated services for complex business logic
4. **Component-Based Views**: Reusable Blade components for consistent UI
5. **Import/Export Classes**: Dedicated classes for data processing

### Consequences

#### Positive

-   **Maintainability**: Clear separation makes code easier to maintain
-   **Scalability**: New modules can be added without affecting existing code
-   **Team Development**: Multiple developers can work on different modules
-   **Code Organization**: Related functionality grouped together
-   **Reusability**: Components and services can be reused across modules

#### Negative

-   **File Proliferation**: More files to manage and navigate
-   **Complexity**: Requires understanding of module boundaries
-   **Dependencies**: Modules may have interdependencies

### Implementation Details

#### Route Organization

-   `web.php`: Core authentication and general routes
-   `user_payreqs.php`: User payment request operations
-   `cashier.php`: Cashier operations and invoice payment
-   `approvals.php`: Approval workflow management
-   `verification.php`: Document verification processes
-   `cash_journals.php`: Cash journal operations
-   `accounting.php`: General ledger and accounting
-   `reports.php`: Reporting and analytics
-   `admin.php`: Administrative functions

#### Controller Structure

-   **Base Controllers**: Shared functionality and middleware
-   **Module Controllers**: Feature-specific controllers
-   **API Controllers**: External API integrations
-   **Service Controllers**: Business logic services

#### Service Layer

-   `LotService`: Official travel claim management
-   Custom services for complex business operations
-   External API integration services

### Review Date

2025-07-15 (6 months from implementation)

---

## ADR-001: Laravel Framework Choice (2024-12-31)

### Status

Accepted

### Context

We needed to choose a PHP framework for building a comprehensive accounting system with complex business logic, database operations, and user management.

### Decision

We chose Laravel framework for its robust features, active community, and excellent documentation.

### Consequences

#### Positive

-   **Rapid Development**: Laravel's features accelerate development
-   **Security**: Built-in security features and best practices
-   **Ecosystem**: Rich ecosystem of packages and tools
-   **Documentation**: Excellent documentation and community support

#### Negative

-   **Learning Curve**: Steep learning curve for complex features
-   **Performance**: Framework overhead compared to plain PHP
-   **Dependency**: Locked into Laravel ecosystem

### Implementation Details

-   Laravel 10.x with PHP 8.1+
-   Used Laravel's built-in features (Eloquent ORM, Blade templating, etc.)
-   Integrated with AdminLTE for frontend
-   Implemented custom middleware and services

### Review Date

2025-06-30

---

## ADR-008: Bilyet Import System Error Handling Strategy - 2025-09-11

**Context**: The Bilyet Excel import system was experiencing silent failures where data would upload to staging table but fail to import to final table, with no clear error messages to users or developers.

**Options Considered**:

1. **Option A**: Strict validation with blocking errors
    - ✅ Pros: Ensures data integrity, prevents invalid records
    - ❌ Cons: Blocks entire import for minor issues, poor user experience
2. **Option B**: Relaxed validation with warnings
    - ✅ Pros: Allows import to proceed, logs issues for review, better UX
    - ❌ Cons: May allow some invalid data through
3. **Option C**: Two-stage validation (staging + final)
    - ✅ Pros: Provides validation opportunities, allows data review
    - ❌ Cons: More complex, requires careful error handling

**Decision**: Option B + C (Relaxed validation with two-stage process)

**Rationale**:

-   Financial systems need data integrity but also operational efficiency
-   Two-stage import allows validation without blocking user workflow
-   Comprehensive logging provides audit trail for data quality issues
-   User-friendly error messages improve system usability

**Implementation**:

-   Modified `BilyetValidationService` to log warnings instead of throwing blocking errors
-   Enhanced `BilyetTempImport` with comprehensive logging at each stage
-   Simplified `BilyetController::import()` with direct record processing
-   Added detailed error reporting with actionable user feedback
-   Implemented proper Carbon date formatting for database compatibility

**Review Date**: 2025-12-31

---

## ADR-009: Bilyet Edit Dialog Date Field Formatting Strategy - 2025-09-16

**Context**: The Bilyet edit dialog was not populating the Bilyet Date field despite having valid data in the database, causing poor user experience during editing operations.

**Options Considered**:

1. **Option A**: Keep current implementation (Carbon objects in views)
    - ✅ Pros: No code changes required
    - ❌ Cons: Fields appear empty, poor user experience, confusing for users
2. **Option B**: Format dates in controller before passing to view
    - ✅ Pros: Centralized formatting logic
    - ❌ Cons: Requires controller changes, may affect other views
3. **Option C**: Format dates directly in Blade template
    - ✅ Pros: Simple fix, localized to specific view, maintains data types in controller
    - ❌ Cons: Formatting logic in view layer

**Decision**: Option C (Format dates directly in Blade template)

**Rationale**:

-   Laravel's Eloquent date casting returns Carbon instances, but HTML date inputs require Y-m-d format strings
-   View-level formatting is appropriate for display-specific formatting
-   Maintains clean separation between data layer and presentation layer
-   Simple, targeted fix without affecting other parts of the system

**Implementation**:

-   Updated `resources/views/cashier/bilyets/list_action.blade.php`
-   Added `.format('Y-m-d')` with proper null checks for both bilyet_date and cair_date fields
-   Used conditional formatting: `{{ $model->bilyet_date ? $model->bilyet_date->format('Y-m-d') : '' }}`
-   Applied same pattern to cair_date field for consistency

**Review Date**: 2025-12-31

## ADR-010: Superadmin Bilyet Edit Feature with Enhanced Status Validation - 2025-09-16

**Context**: Need to provide superadmin users with comprehensive edit capabilities for bilyet records while maintaining business rule integrity and audit trail requirements.

**Options Considered**:

1. **Option A**: Allow unrestricted superadmin editing without validation
    - ✅ Pros: Maximum flexibility for superadmins
    - ❌ Cons: No accountability, bypasses all business rules, poor audit trail
2. **Option B**: Create separate superadmin routes with enhanced validation
    - ✅ Pros: Proper business rule enforcement, comprehensive audit trail, justification requirements
    - ❌ Cons: More complex implementation, additional routes and validation logic
3. **Option C**: Extend existing edit functionality with role-based features
    - ✅ Pros: Reuses existing code, simpler implementation
    - ❌ Cons: Mixed concerns, harder to maintain, unclear separation of responsibilities

**Decision**: Option B (Create separate superadmin routes with enhanced validation)

**Rationale**:

-   Superadmin features require careful balance between administrative flexibility and business rule enforcement
-   Separate routes provide clear separation of concerns and security boundaries
-   Enhanced validation ensures business rules are respected while allowing necessary overrides
-   Comprehensive audit trail with justification requirements provides accountability
-   Dynamic UI guidance helps users understand business rules and requirements

**Implementation**:

-   **Routes**: Added `GET /edit` and `PUT /superadmin` routes for superadmin operations
-   **Controller**: Enhanced BilyetController with `edit()` and `superAdminUpdate()` methods
-   **Validation**: Created SuperAdminUpdateBilyetRequest with comprehensive field validation
-   **Status Logic**: Implemented existing business rules with superadmin override capability
-   **UI Enhancement**: Added dynamic status transition rules display and justification guidance
-   **Audit Trail**: Comprehensive logging of all changes with context and justification
-   **Security**: Role-based access control ensuring only superadmins can access edit functionality

**Review Date**: 2025-12-31

## ADR-011: Email Notification System Disabled for Development - 2025-09-16

**Context**: Bilyet status change notifications were causing TransportException errors due to missing mail server configuration (mailpit), preventing successful bilyet updates and blocking development workflow.

**Options Considered**:

1. **Option A**: Set up mail server infrastructure immediately
    - ✅ Pros: Complete functionality with email notifications
    - ❌ Cons: Requires additional infrastructure setup, delays development, complex configuration
2. **Option B**: Disable email notifications temporarily
    - ✅ Pros: Allows development to continue, preserves core functionality, easy to re-enable
    - ❌ Cons: Users won't receive email notifications during development
3. **Option C**: Use alternative notification methods (database only)
    - ✅ Pros: No external dependencies, notifications still tracked
    - ❌ Cons: Users must check system for notifications, less immediate than email

**Decision**: Option B (Disable email notifications temporarily)

**Rationale**:

-   Development workflow was being blocked by email configuration issues
-   Core bilyet functionality is more critical than email notifications
-   Audit trail continues to work, providing full change tracking
-   Easy to re-enable when mail server is properly configured
-   Allows focus on business logic without infrastructure distractions

**Implementation**:

-   **EventServiceProvider**: Commented out SendBilyetStatusNotification listener registration
-   **Documentation**: Added clear comments explaining disable and re-enable process
-   **Testing**: Verified bilyet updates work without TransportException errors
-   **Preserved Functionality**: Audit logging continues to work normally
-   **Future Ready**: Easy to re-enable when mail server is configured

**Review Date**: 2025-12-31

---

## ADR-012: Exchange Rate Automation Implementation - 2025-10-05

### Status

Accepted

### Context

The exchange rate management system required manual data entry from the official Kemenkeu Kurs Pajak website (https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak), which was time-consuming and error-prone. Users needed to manually check the website weekly and input exchange rates for USD, AUD, and SGD currencies.

### Decision

We implemented a comprehensive exchange rate automation system with the following architecture:

1. **Web Scraping Service**: Created `ExchangeRateScraperService` to parse Kemenkeu Kurs Pajak HTML table
2. **Database Enhancement**: Added automation tracking fields to `exchange_rates` table
3. **Console Command**: Implemented `UpdateExchangeRates` command with configurable options
4. **Scheduled Automation**: Set up weekly and daily automated updates via Laravel scheduler
5. **UI Enhancement**: Added automation status indicators to the exchange rates interface
6. **Configuration System**: Implemented configurable target currencies via environment variables

### Consequences

#### Positive

-   **Time Savings**: Eliminates 15-30 minutes of manual work weekly
-   **Accuracy**: Removes human transcription errors
-   **Consistency**: Always uses official Kemenkeu rates
-   **Flexibility**: Configurable target currencies (USD, AUD, SGD by default)
-   **Audit Trail**: Complete tracking of automated vs manual entries
-   **Daily Coverage**: Creates daily records for entire KMK effective period
-   **Real-time Status**: UI shows automation status and last update time

#### Negative

-   **External Dependency**: System now depends on Kemenkeu website availability
-   **Parsing Complexity**: HTML structure changes could break scraping
-   **Database Growth**: Daily records increase storage requirements

### Implementation Details

#### Files Created/Modified

-   `database/migrations/2025_10_05_145641_add_automation_fields_to_exchange_rates_table.php` - Added automation fields
-   `app/Services/ExchangeRateScraperService.php` - Web scraping service with DOM parsing
-   `app/Console/Commands/UpdateExchangeRates.php` - Automated update command
-   `app/Console/Kernel.php` - Scheduled automation (weekly + daily backup)
-   `app/Models/ExchangeRate.php` - Enhanced model with new fields
-   `config/exchange_rates.php` - Configuration for target currencies
-   `resources/views/exchange-rates/index.blade.php` - UI automation status display

#### Key Features Implemented

-   **DOM-based Parsing**: Robust HTML parsing using DOMDocument and XPath
-   **Configurable Currencies**: Environment variable `EXCHANGE_RATES_TARGET` (default: USD,AUD,SGD)
-   **Command Options**: `--currencies`, `--force`, `--no-expand` for flexible operation
-   **KMK Period Tracking**: Stores KMK number and effective date range
-   **Daily Expansion**: Creates records for each day in KMK effective period
-   **Change Tracking**: Calculates rate changes from previous periods
-   **Source Attribution**: Tracks manual vs automated entries
-   **UI Status Display**: Shows "Automated" badge with last update time and KMK number

#### Environment Configuration

```env
# Target currencies for automation (comma-separated)
EXCHANGE_RATES_TARGET=USD,AUD,SGD
```

#### Command Usage

```bash
# Standard automation (uses config)
php artisan exchange-rates:update --force

# Specific currencies
php artisan exchange-rates:update --currencies=USD,SGD --force

# Single date only (no daily expansion)
php artisan exchange-rates:update --no-expand --force
```

### Review Date

2025-12-05 (2 months from implementation)

---

## ADR-013: Dashboard Dual Exchange Rate Display Implementation - 2025-10-05

**Context**: User requested to display both external exchange rate (from exchangerate-api.com) and internal automated exchange rate (from Kemenkeu Kurs Pajak) side by side in the dashboard running text for comparison purposes. This would help users validate data accuracy and understand rate differences between sources.

**Options Considered**:

1. **Option A**: Replace existing external rate with internal rate only

    - ✅ Pros: Single source of truth, official government data
    - ❌ Cons: Loses external market rate comparison, reduces transparency

2. **Option B**: Show both rates side by side with source attribution

    - ✅ Pros: Full transparency, rate comparison, data validation capability
    - ❌ Cons: Slightly more complex UI, requires dual API calls

3. **Option C**: Toggle between sources with user preference
    - ✅ Pros: User choice, clean UI
    - ❌ Cons: More complex implementation, users might miss comparison

**Decision**: Implement Option B - Dual rate display with side-by-side comparison

**Rationale**:

-   Provides maximum transparency and data validation capability
-   Helps users understand rate differences between external market and official government sources
-   Maintains existing functionality while adding official rate visibility
-   Simple implementation with clear source attribution

**Implementation Details**:

#### API Endpoint

-   Created `/api/dashboard/exchange-rate-usd` route (no authentication required)
-   Fetches today's USD rate from automated exchange rate system
-   Returns JSON with rate, timestamp, KMK number, and effective date

#### Frontend Enhancement

-   Updated `resources/views/dashboard/run-text.blade.php` JavaScript
-   Dual API calls: external (exchangerate-api.com) + internal (Kemenkeu)
-   Side-by-side display with source attribution and timestamps
-   Graceful fallback when internal rate unavailable

#### Display Format

```
💱 External Rate: 1 USD = IDR 16.574,58 (Source: exchangerate-api.com) | Last Updated: 14.18 WIB 💱 | 💱 Official Rate: 1 USD = IDR 16.690 (Source: Kemenkeu Kurs Pajak) | Last Updated: 14.17 WIB 💱
```

#### Technical Features

-   Indonesian number formatting for both rates
-   Real-time updates every 5 minutes
-   Error handling with graceful degradation
-   Source attribution for transparency

**Consequences**:

✅ **Positive**:

-   Enhanced transparency and data validation
-   Rate comparison capability for users
-   Leverages existing automation system
-   Maintains existing external rate functionality
-   Clear source attribution builds trust

❌ **Negative**:

-   Slightly longer running text display
-   Requires dual API calls (minimal performance impact)
-   More complex JavaScript logic

**Files Modified**:

-   `resources/views/dashboard/run-text.blade.php` - Enhanced JavaScript for dual rate fetching
-   `routes/api.php` - Added dashboard API endpoint
-   `docs/architecture.md` - Updated external integrations section
-   `MEMORY.md` - Added implementation memory entry

### Review Date

2025-12-05 (2 months from implementation)

---

## ADR-014: Dashboard Exchange Rate Simplification - 2025-10-05

**Context**: User requested to simplify the dashboard exchange rate display by removing the external exchange rate (exchangerate-api.com) and keeping only the automated Kemenkeu Kurs Pajak rate. This change was made to focus on a single source of truth and reduce complexity.

**Options Considered**:

1. **Option A**: Keep dual rate display (current state)

    - ✅ Pros: Rate comparison, transparency, data validation
    - ❌ Cons: Complex display, dual API calls, longer running text

2. **Option B**: Show only external rate (exchangerate-api.com)

    - ✅ Pros: Market rate, real-time updates
    - ❌ Cons: Not official government rate, external dependency

3. **Option C**: Show only internal automated rate (Kemenkeu Kurs Pajak)
    - ✅ Pros: Official government rate, single source of truth, leverages automation
    - ❌ Cons: No external market comparison

**Decision**: Implement Option C - Single official rate display from Kemenkeu automation system

**Rationale**:

-   Provides official government exchange rate as single source of truth
-   Leverages our existing automation system investment
-   Simplifies user interface and reduces complexity
-   Eliminates external API dependency
-   Focuses on official tax/financial reporting requirements

**Implementation Details**:

#### Frontend Simplification

-   Removed external API call to exchangerate-api.com
-   Simplified JavaScript function from `fetchExchangeRates()` to `fetchExchangeRate()`
-   Updated display format to show only Kemenkeu rate
-   Maintained 5-minute refresh interval

#### Display Format

```
💱 Exchange Rate: 1 USD = IDR 16.690 (Source: Kemenkeu Kurs Pajak) | Last Updated: 14.17 WIB 💱
```

#### Error Handling

-   Graceful fallback when internal rate unavailable
-   Clear source attribution maintained
-   Indonesian number formatting preserved

**Consequences**:

✅ **Positive**:

-   Simplified user interface
-   Single source of truth (official government rate)
-   Reduced API calls and complexity
-   Focus on official tax reporting requirements
-   Leverages existing automation investment

❌ **Negative**:

-   Loss of external market rate comparison
-   No rate validation against market sources
-   Reduced transparency about rate differences

**Files Modified**:

-   `resources/views/dashboard/run-text.blade.php` - Simplified JavaScript for single rate fetching
-   `docs/architecture.md` - Updated external integrations description
-   `docs/decisions.md` - Added this decision record

### Review Date

2025-12-05 (2 months from implementation)

---

## ADR-015: SAP Business Partners Sync with Advanced Features - 2026-01-08

**Context**: Business Partners (Customers, Suppliers, Leads) master data was maintained manually in the `customers` table, causing data drift and missing critical information like credit limits, VAT status, and contact details. Faktur creation lacked credit limit validation, and administrators had to use command-line tools to sync SAP master data.

**Options Considered**:

1. **Option A**: Continue manual maintenance of Business Partners data
   - ✅ Pros: No code changes required, maintains current workflow
   - ❌ Cons: Data drift, missing critical information, manual errors, no credit limit validation

2. **Option B**: Sync Business Partners only, no advanced features
   - ✅ Pros: Simpler implementation, addresses core sync need
   - ❌ Cons: Misses opportunity for change tracking, credit validation, unified UI

3. **Option C**: Complete sync system with advanced features (Chosen)
   - ✅ Pros: Complete solution with change tracking, credit validation, unified UI, auto-sync to customers table
   - ❌ Cons: More complex implementation, additional database fields

**Decision**: Implement comprehensive SAP Business Partners sync with advanced features including change detection, credit limit validation, unified sync UI, and automatic customer/vendor synchronization.

**Rationale**:

- Business Partners data is critical for financial operations (credit management, VAT compliance, contact information)
- Change tracking provides audit trail for compliance and helps identify data drift
- Credit limit validation prevents financial risks proactively
- Unified sync UI improves admin experience and reduces manual command-line operations
- Auto-syncing to `customers` table maintains backward compatibility while leveraging rich SAP data
- Change detection service enables proactive monitoring of master data changes

**Implementation**:

#### Database Schema

- Created `sap_business_partners` table with comprehensive fields (code, name, type, active, phone, email, address, vat_liable, federal_tax_id, credit_limit, balance, metadata, last_synced_at)
- Added change tracking fields (previous_name, previous_active, name_changed_at, status_changed_at)
- Indexes on code (unique), type, active, and last_synced_at for performance

#### Services & Controllers

- **SapService**: Added `getBusinessPartners()` method to fetch from SAP B1 Service Layer
- **SapMasterDataSyncService**: Extended with `syncBusinessPartners()` method with chunked upserts, change detection, and transaction safety
- **CustomerAutoSyncService**: Auto-syncs Customers and Vendors from Business Partners
- **BusinessPartnerChangeDetectionService**: Tracks changes and provides statistics
- **BusinessPartnerController**: Admin UI with DataTable, filtering, statistics, and sync functionality
- **SapMasterDataSyncController**: Unified UI for syncing all master data types

#### Integration Features

- Credit limit validation in `FakturController::store()` prevents invoice creation when credit exceeded
- `Customer` model relationship to `SapBusinessPartner` via code
- Unified sync UI at `/admin/sap-master-data-sync` for all master data types
- Statistics endpoints for monitoring and reporting
- Change detection with audit trail

#### User Interface

- Business Partners admin page with DataTable, type/status filters, and sync button
- Unified master data sync page with individual and bulk sync options
- Sidebar menu items for Business Partners and Master Data Sync
- Real-time sync progress indicators and toastr notifications

**Consequences**:

✅ **Positive**:

- Complete Business Partners data sync from SAP B1 (2,366+ records successfully synced)
- Proactive credit limit validation prevents financial risks
- Change tracking provides audit trail for compliance
- Unified sync UI improves admin experience
- Auto-sync maintains backward compatibility with existing customers table
- Statistics and change detection enable proactive monitoring

❌ **Negative**:

- Additional database storage for change tracking fields
- More complex sync logic requires maintenance
- Initial sync time for large datasets (2,366+ records)

**Files Created/Modified**:

- `app/Models/SapBusinessPartner.php` - Business Partner model
- `database/migrations/2026_01_08_000857_create_sap_business_partners_table.php`
- `database/migrations/2026_01_08_002446_add_change_tracking_to_sap_business_partners_table.php`
- `app/Services/CustomerAutoSyncService.php`
- `app/Services/BusinessPartnerChangeDetectionService.php`
- `app/Http/Controllers/Admin/BusinessPartnerController.php`
- `app/Http/Controllers/Admin/SapMasterDataSyncController.php`
- `app/Http/Controllers/UserPayreq/FakturController.php` (credit validation)
- `resources/views/admin/business-partners/index.blade.php`
- `resources/views/admin/sap-master-data-sync/index.blade.php`
- `routes/admin.php` (new routes)
- `resources/views/templates/partials/sidebar.blade.php` (menu items)

**Review Date**: 2026-04-08 (3 months from implementation)

---