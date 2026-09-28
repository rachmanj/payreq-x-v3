# In-App Help (HELP)

The **Help** panel answers how-to questions from inside the working screen, using passages from the official manuals in `docs/manuals/` plus the navigation hints in `docs/help-navigation.json`.

## What the HELP panel is and where to open it

- Click the question-mark icon (**?**) in the top bar to open the **Help** panel as a modal.
- The panel has two tabs: **How-to** (usage questions) and **Report / request** (bug reports and feature requests).
- Answers are built from the indexed manuals. When a topic is not covered, HELP says so plainly instead of guessing.

## Asking a question in the How-to tab

1. Open the **Help** panel → **How-to** tab.
2. Type your question in **Your question** (maximum 4000 characters), e.g. *How do I create the monthly BAPSB?* or *Why is the Cheque / Bilyet dropdown empty?*
3. Click **Ask**. The answer appears below the button, followed by the list of manual sources used.
4. If the answer is incomplete, rephrase the question using the exact menu/button names shown on screen (e.g. **Cashier → BAPSB**, **+ Bilyet**, **Submit for validation**).

## Sending a bug report or feature request (Report / request tab)

1. Open the **Help** panel → **Report / request** tab.
2. Fill in **Type** (**Bug** or **Feature request**), **Title**, **Description**, and **Steps to reproduce (optional)**.
3. Click **Submit**. The report is stored in the application and emailed to the notification address configured by an administrator (`HELP_FEEDBACK_NOTIFY_EMAIL`); without that configuration it is stored without an email.

## Who can use HELP (permission `akses_help`)

The `help.ask` and `help.feedback` endpoints require the **`akses_help`** permission and are rate-limited to 30 requests per minute per user. If the panel does not answer (or you get *Access Denied*), ask an administrator for **`akses_help`**.

## When the HELP answer is empty or off target

- If the answer reads *This topic is not covered in the indexed manuals…*, the topic is genuinely missing from the manuals — it does not mean the feature is absent.
- Check that the latest manuals were indexed: an administrator must run `php artisan help:reindex` after documentation changes.
- For a topic that does exist in a manual but gets a weak match, include the terms the manual uses (menu names, buttons, table columns, status codes) so the match improves.
- Report the gap through the **Report / request** tab so the manual can be fixed.

## For administrators: refreshing HELP knowledge (`help:reindex`)

1. Update or add manual files under `docs/manuals/` (the `*-id.md` and `*-en.md` pair) and, when needed, `docs/help-navigation.json`.
2. Run `php artisan help:reindex` on the server.
3. The command reads every `docs/manuals/*.md` file and `docs/help-navigation.json`, splits them per `##` heading, and stores the result as HELP knowledge (batching follows `HELP_REINDEX_BATCH_SIZE`, default 20).

Related configuration lives in `config/help.php`: `HELP_SIMILARITY_THRESHOLD` (default 0.22), `HELP_TOP_K` (default 6), `HELP_REINDEX_BATCH_SIZE`, `HELP_FEEDBACK_NOTIFY_EMAIL`, plus small boosts for manuals matching the user's locale.

## Writing manuals so HELP can find them

- Keep manuals in `docs/manuals/`, always as a pair `*-id.md` (Bahasa Indonesia) and `*-en.md` (English).
- Use a single `#` for the title and `##` to split sections: each `##` section becomes one search chunk.
- Write section headings using the keywords users actually search for (menu names, buttons, status codes).
- Quote menu and button labels exactly as shown in the application — HELP must not infer UI names.
- Add an entry to `docs/help-navigation.json` for “where is the menu?” questions (menu path, route, permission, keywords).
- After adding a manual, register it in this folder's `README.md`.

## Manuals in this folder (related manuals)

| Topic | English | Bahasa Indonesia |
|-------|---------|------------------|
| Getting started | `getting-started-en.md` | `getting-started-id.md` |
| Bank reconciliation | `bank-reconciliation-manual-en.md` | `bank-reconciliation-manual-id.md` |
| RAB / Anggaran | `anggaran-manual-en.md` | `anggaran-manual-id.md` |
| Realization — scan fuel receipts (AI) | `realization-fuel-receipt-scan-manual-en.md` | `realization-fuel-receipt-scan-manual-id.md` |
| Manual Journal Entry | `manual-journal-entry-manual-en.md` | `manual-journal-entry-manual-id.md` |
| SAP Sync — VJ validation before posting | `sap-sync-vj-validation-manual-en.md` | `sap-sync-vj-validation-manual-id.md` |
| **Bilyet administration & BAPSB** (Bilyet Giro / Cheque / LOA, monthly securities inspection) | `bilyet-administration-manual-en.md` | `bilyet-administration-manual-id.md` |
| In-app help (HELP) | `in-app-help-manual-en.md` | `in-app-help-manual-id.md` |

## Technical reference (developer notes)

- HELP routes: `routes/help.php` (`help.ask`, `help.feedback`, middleware `permission:akses_help`, `throttle:30,1`).
- Controller: `app/Http/Controllers/Help/HelpController.php`; services: `app/Services/Help/` (`HelpAssistantService`, `HelpManualChunker`, AI provider client).
- Index command: `php artisan help:reindex` (`app/Console/Commands/HelpReindexCommand.php`).
- Configuration: `config/help.php`. Panel UI: `resources/views/templates/partials/help-panel.blade.php`.
