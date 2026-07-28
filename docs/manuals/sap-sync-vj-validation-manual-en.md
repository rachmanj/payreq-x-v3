# Verification Journal validation before SAP Sync

Before a **Verification Journal (VJ)** can be posted to SAP B1, it must pass a **validation** step. A user with permission **`validate_vj`** reviews the journal on the **SAP Sync** show page and clicks **Validate** or **Reject**. **Submit to SAP B1** stays disabled until status is **Validated**.

This applies to **realization-based** VJs and **bank** VJs (`type = bank`).

## How to use VJ validation

End-to-end flow:

1. **VJ is created** — from **Cashier** → **Verification Journal** (realization flow) or **Cashier** → **Bank Transaction** (bank VJ). New journals start with validation status **Pending**.
2. **Validator reviews** — open **Accounting** → **SAP Sync**, pick the project tab (e.g. **022C**), open the journal from the list, or use the dashboard counter (below). On the show page, read the **Validation** card and journal lines.
3. **Validate or Reject** — if correct, click **Validate**. If not, click **Reject**, enter **Reason for rejection** (required), then **Confirm Reject**.
4. **SAP submission** — after **Validated**, users who can submit see **Submit to SAP B1** on the same show page. **Pending** or **Rejected** journals cannot be submitted.
5. **If rejected** — the creator sees a red **Verification Journal(s) Rejected** banner on any page (with **Review & Fix**). They correct the journal, which automatically returns it to **Pending** for re-validation (no separate resubmit button).
6. **Re-validation** — the validator sees the journal again in the **VJ pending validation** dashboard count and project list (**Pending** badge), then **Validate** or **Reject** again.

**Short path (validator):** Dashboard **VJ pending validation** card → **Accounting** → **SAP Sync** → project list → open VJ → **Validate** → **Submit to SAP B1**.

**Short path (creator after reject):** Red rejection banner → **Review & Fix** → **Edit Details** → save changes → wait for validator.

## Who can open SAP Sync

Open **Accounting** → **SAP Sync**. You need **`akses_sap_sync`**. Project tabs (**000H**, **001H**, **022C**, etc.) list journals for that project. Users with BO-only roles (**approver_bo**, **cashier_bo** without **admin** / **superadmin** / **cashier** / **approver**) are limited to project **001H**.

You can type **SAP Sync** in the top-bar **Search Menu here** field when you have **`akses_sap_sync`**.

## Validation card on the SAP Sync show page

The right-hand **Validation** card shows:

| Status | Meaning |
|--------|---------|
| **Pending** | Waiting for a validator with **`validate_vj`** |
| **Validated** | Approved; **Submit to SAP B1** is allowed (if you have submit permission) |
| **Rejected** | Sent back to the creator with a reason |

When **Rejected**, the card shows **Rejected — reason from reviewer**. Action buttons **Validate** and **Reject** appear only for validators while status is **Pending** and the journal is not yet posted to SAP.

## Validator dashboard counter

Users with **`validate_vj`** see **VJ pending validation** on the main dashboard:

- **Action Center** — warning card when the count is greater than zero (links to **Accounting** → **SAP Sync** dashboard).
- **KPI tiles** — success card when the count is zero (“Nothing pending”).

The count includes only journals with status **Pending**. **Rejected** and **Validated** journals are excluded. BO-restricted validators count only **001H** project journals.

## Creator notification after rejection

Creators do **not** need access to **SAP Sync** to learn about a rejection. If you created the journal (`created_by`), a dismissible red banner appears at the top of the app:

- Title: **Verification Journal(s) Rejected**
- Shows journal number, project, rejector, time, and **Reason**
- **Review & Fix** opens:
  - **Bank** VJs → **Cashier** → **Bank Transaction** edit page
  - Other VJs → **Edit Details** page for that journal

The banner disappears automatically after you save a correction (status returns to **Pending**).

## Fixing and resubmitting a rejected VJ

There is **no separate “Resubmit for validation” button**. Resubmission is automatic:

1. Open **Review & Fix** from the rejection banner (or go to **Edit Details** / bank transaction edit if you already know the screen).
2. Correct account, cost center, description, or other allowed fields.
3. **Save** the detail change.

Saving resets validation to **Pending**, clears the rejection reason, and removes the banner. The validator’s **VJ pending validation** count increases by one.

While status is **Rejected**:

- **Edit Details** — only the **creator** or a user with **`edit_verification_project`** (and not already posted to SAP).
- **Update SAP** and **Cancel SAP** — disabled for everyone until the journal is no longer rejected.
- **Submit to SAP B1** — not available until re-validated.

### Bank VJs after rejection

When a bank VJ is rejected, its bank transaction status returns to **draft**. The creator edits it on **Cashier** → **Bank Transaction**, then uses **Submit** again. That also sets validation back to **Pending** and clears the rejection reason.

## SAP Sync project list

Each project tab shows a **validation_status** column:

- **Pending** (yellow badge)
- **Validated** (green)
- **Rejected** (red)

Bulk SAP submission checkboxes appear only for **Validated** journals that are not yet posted.

## Permissions

| Permission / role | Purpose |
|-------------------|---------|
| **`akses_sap_sync`** | Open **Accounting** → **SAP Sync** menus and lists |
| **`validate_vj`** | **Validate** / **Reject** on the SAP Sync show page; see **VJ pending validation** dashboard count |
| **`edit_verification_project`** | **Edit Details** on journals you did not create (project-scoped editors) |
| **Creator** (`created_by`) | **Edit Details** on own journals (when not posted) |
| **superadmin**, **admin**, **cashier** | **Update SAP** / **Cancel SAP** on show page (disabled while **Rejected**) |
| **superadmin**, **admin**, **cashier**, **cashier_bo** | Change **Project** on a detail line in **Edit Details** |

Default assignment: **`validate_vj`** is seeded for **superadmin** and **admin**; administrators can grant it to other roles in **Role** management under **SAP Integration**.

## Troubleshooting

- **Cannot see Validate / Reject** — you need **`validate_vj`**, journal must be **Pending**, and it must not already have an SAP journal number.
- **Submit to SAP B1 missing** — journal must be **Validated** first; check the **Validation** card.
- **Creator did not see rejection** — only the user who created the journal sees the banner; refresh the page after rejection.
- **Rejected but Edit Details missing** — you must be the creator or have **`edit_verification_project`**; posted journals cannot be edited.
- **VJ pending validation count seems wrong** — **Rejected** journals are not counted; only **Pending**. BO validators only see **001H**.
- **Outdated HELP answers** — administrator runs `php artisan help:reindex` after manual updates.
