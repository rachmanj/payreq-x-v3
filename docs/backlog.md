# Backlog (ideas, not committed)

- **VJ Soft UI:** Extract CSS from `accounting/sap-sync/show.blade.php` into `resources/css/vj-soft-ui.css` (Vite) or `resources/views/partials/vj-soft-ui-styles.blade.php` when a second page adopts the style; see `docs/VJ-SOFT-UI.md`.
- Optional: apply VJ Soft UI to Manual Journal Entry show page (`accounting/journal-entries/{id}`).
- Optional: e-mail or in-app reminder before end of week for PCBC upload.
- Optional: move exempt project list from `config/pcbc_compliance.php` to admin-managed settings (DB) without deploy.
- Optional: expand automated tests for `PcbcComplianceService` (week boundary edge cases, exempt users).
- Optional: feature tests for PCBC validate/reject routes and for compliance queries ignoring non-`validated` `dokumens`.
- Optional: feature tests for cashier bank reconciliation — manual **N:M** **`POST …/match`** (validation vs tolerance **0.005**), **`POST …/unmatch/{reconciliation_match_group}`**, and completed reconciliation guardrails.

_Prioritise and promote items to `docs/todo.md` when scheduled._
