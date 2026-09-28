# Bilyet & BAPSB Administration (Bilyet Giro, Cheque, Letter of Authority)

The **Bilyet Administration** module records the bank securities a unit holds — **Bilyet Giro (BG)**, **Cheque**, and **Letter of Authority (LOA)** — from physical receipt through release, settlement (**cair**), up to cancellation (**void**), with a full audit trail. The **BAPSB** feature (**Berita Acara Pemeriksaan Surat Berharga Bank** — Bank Securities Inspection Report) runs every month per project: it inspects the physical securities against system data, prints a report for signature, stores the scanned PDF, and requests validation from the **Accounting HO** team.

## Table of Contents

- [Introduction: Bilyet Administration and BAPSB](#introduction-bilyet-administration-and-bapsb)
- [Who uses this module](#who-uses-this-module)
- [Getting Started: menus, permissions, and short flow](#getting-started-menus-permissions-and-short-flow)
- [Registering a New Bilyet](#registering-a-new-bilyet)
- [Importing Bilyets in Bulk from Excel](#importing-bilyets-in-bulk-from-excel)
- [Bilyet status and available actions](#bilyet-status-and-available-actions)
- [Releasing, Settling and Voiding a Bilyet](#releasing-settling-and-voiding-a-bilyet)
- [Bilyet List, Filters, and Dashboard](#bilyet-list-filters-and-dashboard)
- [Audit Trail and Bilyet History](#audit-trail-and-bilyet-history)
- [Filling Cheque / Bilyet in Bank Transaction](#filling-cheque--bilyet-in-bank-transaction)
- [Creating the Monthly BAPSB](#creating-the-monthly-bapsb)
- [Printing the BAPSB and Signature Blocks](#printing-the-bapsb-and-signature-blocks)
- [Uploading the Signed PDF and Submitting the BAPSB](#uploading-the-signed-pdf-and-submitting-the-bapsb)
- [Validating BAPSB as Accounting HO](#validating-bapsb-as-accounting-ho)
- [Compliance, Deadline, and Outstanding List](#compliance-deadline-and-outstanding-list)
- [Permissions Reference](#permissions-reference)
- [Common Tasks](#common-tasks)
- [Troubleshooting: Empty Cheque / Bilyet Dropdown and Other Issues](#troubleshooting-empty-cheque--bilyet-dropdown-and-other-issues)
- [Quick Reference: Statuses, URLs, and Glossary](#quick-reference-statuses-urls-and-glossary)

## Introduction: Bilyet Administration and BAPSB

Two things this module covers:

- **Bilyet Administration** — the master record of bank securities per giro account: number, type, bilyet date, settlement date, amount, and status. Every report and physical inspection reads from this master.
- **BAPSB** — the monthly inspection document per project. It captures the physical position of bilyets at period end, the per-line inspection result (physically present or not, storage location), the summary per security type, and the month's movements. One report per project per month.

If you are new to the module, start with **Registering a New Bilyet** and **Importing Bilyets in Bulk from Excel**; a BAPSB cannot be created for an account before its bilyets are registered.

## Who uses this module

| User | Role in this module |
|------|---------------------|
| **Cashier / accounting site** (`000H`, `021C`, `022C`, `025C`) | Register bilyets, import from Excel, release/settle/void bilyets, prepare and submit their unit's BAPSB |
| **Accounting HO** (validator) | Validate submitted BAPSB documents, monitor the central outstanding list |
| **BO** (`001H`) | Same as any other unit: handle bilyets and the BAPSB for the BO account |
| **Admin / superadmin** | Grant permissions, change account flags, repair bilyet data through the superadmin edit page |
| **Auditor / internal control** | Read the **Audit Trail**, per-bilyet **History**, and the **Reports** tab |

**Savings accounts (tabungan)** do not issue bank securities, so they do not need a BAPSB (see **Compliance, Deadline, and Outstanding List**).

## Getting Started: menus, permissions, and short flow

**Menus:**

- **Cashier → Administrasi Bilyet** (requires the **`akses_bilyet`** permission) — opens the **Dashboard** tab.
- **Cashier → BAPSB** (requires the **`akses_bapsb`** permission) — the list of inspection reports per unit.
- The **Giro** master (bank accounts) lives under **Accounting → Giro** (requires **`akses_giro`**).

**Tabs inside Bilyet Administration** (navigation card at the top of the page): **Dashboard** | **List** | **Upload** | **Audit Trail** | **Reports**.

**Short flow for bilyet handling:**

1. Receive the physical Bilyet Giro / Cheque / LOA.
2. Register it **one by one** with **+ Bilyet** on **List**, or **in bulk** via **Upload** (Excel template).
3. A bilyet stays **onhand** until you **release** it (hand it to the payee); when the funds clear, mark it **cair**; if cancelled, mark it **void**.
4. Every change is logged automatically in the **Audit Trail** and in that bilyet's **History**.
5. Before it can be referenced from a payment, the bilyet must be registered against the right account so it appears in the **Cheque / Bilyet** dropdown on **Bank Transactions**.
6. Each month, prepare the **BAPSB** for your project, print it, sign it, upload the scanned PDF, then **Submit for validation**.

## Registering a New Bilyet

1. Open **Cashier → Administrasi Bilyet**, then click the **List** tab.
2. Click **+ Bilyet** at the top right of the **Bilyet List** card (this button requires the **`add_bilyet`** permission).
3. Complete the **New Bilyet** modal (see the field table below), then click **Save**.
4. On success the page shows a confirmation message. The new row appears once a filter is applied (see **Bilyet List, Filters, and Dashboard**).

The system rejects duplicates: if the **Prefix + Bilyet No** combination already exists, you get *Bilyet number already exists*.

### Fields on the New Bilyet form

| Field | Required | Notes |
|-------|----------|-------|
| **Prefix** | Yes | Bilyet number prefix (max 10 characters), e.g. `GH`. |
| **Bilyet No** | Yes | Bilyet number without prefix (max 30 characters). |
| **Bilyet Type** | Yes | Form options: **Cek**, **BG**, **LOA**. In the list and detail views the type is shown as **CEK** (Check), **BILYET** (Bilyet Giro), or **LOA** (Letter of Authority). |
| **Giro** | Yes | The giro account that owns the bilyet (list from the **Giro** master: `acc_no - acc_name`). |
| **Bilyet Date** | No | The date printed on the bilyet. |
| **Cair Date** | No | Settlement date (must be on or after the bilyet date). |
| **Remarks** | No | Free-text note (max 500 characters). |
| **Amount** | No | Bilyet amount (IDR, cannot be negative). |
| **Upload bilyet** | No | Optional attachment: PDF, JPG/JPEG, or PNG, maximum **2 MB**. |

### Status is derived automatically on save

The **New Bilyet** form does not ask for a status. It is computed from the completeness of the data:

| What you filled in | Resulting status |
|--------------------|------------------|
| Amount + Bilyet Date + Cair Date | **cair** |
| Amount **or** Bilyet Date | **release** |
| Everything empty | **onhand** |

Need another status? The **superadmin-only** edit page offers the full status list (**On Hand**, **Release**, **Cair**, **Void**) and validates the transition.

### Update Many — change several bilyets at once

The **Update Many** button (permission **`add_bilyet`**) opens a modal that updates many bilyets in one go:

- **Bilyet Date**, **Purpose** (note), and **Amount** to be applied.
- **Select Bilyets (Onhand only)** — only bilyets with status **onhand** can be selected.
- Click **Save**; you get *Successfully updated N bilyets.* and every change is logged as **Bulk Updated** in the audit trail.

## Importing Bilyets in Bulk from Excel

Use this when you receive a long list of bilyets (dozens or hundreds of rows) from a unit or a bank.

1. Open **Cashier → Administrasi Bilyet** → **Upload** tab (the **Upload Bilyets** card).
2. Click **download template** (green button) — file `bilyet_template.xlsx`.
3. Fill in the template. The columns the system recognises, matching the template file:

| Column | Content |
|--------|---------|
| `acc_no` | Giro account number (must already exist in the **Giro** master) |
| `prefix` | Bilyet number prefix (required) |
| `nomor` | Bilyet number (required) |
| `type` | Type: `cek`, `bilyet`, or `loa` |
| `bilyet_date` | Bilyet date |
| `cair_date` | Settlement date (leave empty when not settled yet) |
| `amount` | Amount |
| `remarks` | Remarks |

4. Save the file, then click **Upload** on the **Upload Bilyets** page and choose the file: **.xls or .xlsx**, maximum **10 MB** (larger files or non-Excel files are rejected immediately with a warning).
5. Valid rows go to the **staging table** (`bilyet-temps`) — not straight into the master. Review the list in the **Upload Bilyets** table (columns **Nomor**, **status**, **Giro Acc**, **Type**, **BilyetD**, **CairD**, **Amount**, **Loan**). Rows whose account is unknown show up as problems; use **Empty Table** to clear staging when needed.
6. Click **Import** (orange button) → modal **Import Bilyets to DB** → fill in **Receive Date** (the physical receipt date, required) → click **Import**.
7. The **Import** button stays disabled while any staged row has no valid giro account or duplicates exist. On success you get *Import completed successfully! N records imported*. Rows without a valid account are skipped and the staging table is cleared automatically.

**Note:** the status of each imported row follows the same rule as manual registration (see **Status is derived automatically on save**).

If the import finishes but nothing was imported, the usual cause is that the **account numbers in the Excel file do not exist in the system**. Fix `acc_no`, upload again, then Import.

## Bilyet status and available actions

| Status | Meaning | Available actions |
|--------|---------|-------------------|
| **onhand** | Physical bilyet is in the unit, not yet handed over | edit/release button, **Void**, **View Details**, **History**; **delete** (permission `delete_bilyet`) |
| **release** | Handed over to the payee, not yet settled | **Cairkan** button, **Void**, **View Details**, **History** |
| **cair** | Settled by the bank | **View Details**, **History** (no further changes) |
| **void** | Cancelled; remarks, amount, and dates stay intact | **View Details**, **History** |

Status transitions enforced by the system:

| From | Allowed to |
|------|------------|
| **onhand** | **release**, **void** |
| **release** | **cair**, **void** |
| **cair** | — (final) |
| **void** | — (final) |

An invalid transition is rejected with *Invalid status transition from … to …*. A **superadmin** can force the change through the superadmin edit page, provided a reason of at least 10 characters is entered in the remarks field; that reason is recorded automatically inside the bilyet remarks as *SUPERADMIN OVERRIDE*.

## Releasing, Settling and Voiding a Bilyet

All actions run from the icon buttons in the **Action** column of the **Bilyet List** table (permission **`akses_bilyet`**). Bilyets from other projects are visible to **admin/superadmin** only.

### Release (handing the bilyet over)

1. On a bilyet with status **onhand**, click the pencil icon (**Edit/Release**).
2. The **Release {Type} no {Nomor}** modal opens. Fill in **Bilyet Date**, **Cair Date (Optional)**, **Amount**, and **Purpose** (note) — existing values are pre-filled.
3. Click **Update**. Once a date/amount is present, the status moves to **release**.

### Settle (cair)

1. On a bilyet with status **release**, click the money icon (**Cairkan**).
2. The **Cairkan {Type} no {Nomor}** modal opens: **Bilyet Date**, **Amount**, and **Purpose** are read-only; **Cair Date** is required.
3. If this bilyet is a loan payment (`purpose` = loan payment with a linked installment), the **Create SAP Outgoing Payment** checkbox appears — when ticked, the system creates an **Outgoing Payment** in SAP B1 for the linked installment's AP Invoice.
4. Click **Cairkan** → the status becomes **cair**.

### Void (cancellation)

1. On a bilyet with status **onhand** or **release**, click the ban icon (**Void**).
2. The confirmation modal states: *Status will be changed to VOID*, and other data (remarks, amount, dates) remains unchanged.
3. Click **Void**.

### Dedicated stage lists

Stage-specific lists are also available at the routes below (open the address directly; there is no menu tab):

| Page | Address | Distinct column |
|------|---------|-----------------|
| **Cair** | `/cashier/bilyets/cair` | **CairD** column |
| **Release** | `/cashier/bilyets/release` | **CairD** column |
| **Void** | `/cashier/bilyets/void` | **VoidD** column |

Each row has an **edit** button → modal **Edit Data for {Type} no {Nomor}**: **Bilyet Date**, **Cair Date**, **Purpose**, **Amount**, and the **Is VOID?** select (**NO**/**YES**). Choose **YES** to mark the bilyet as **void**.

## Bilyet List, Filters, and Dashboard

### Dashboard tab

Shows the bilyet position per bank account (table of **Bank Account** × **Cek**, **BG**, **LoA**, **Debit**, **Total**, **Amount**) in four cards: **Onhand**, **Release**, **Due This Month**, and **Void**. Use it to see which securities the unit still holds.

### List tab

The **Bilyet List** card holds a table with columns: **#**, **Nomor**, **Bank | Account**, **Type**, **BilyetD**, **CairD**, **Status**, **IDR**, a checkbox column, and **Action**.

Important: the table shows **no** data before a filter is applied — the page displays the **Gunakan Filter** hint above it. Fill in a filter and click **Filter**:

| Filter | Values |
|--------|--------|
| **Status** | Semua Status, **onhand**, **release**, **cair**, **void** |
| **Bank Account** | One giro account |
| **Nomor Bilyet** | Number search |
| **Tanggal Dari** / **Tanggal Sampai** | Date range |
| **Amount From** / **Amount To** | Amount range |

Click **Reset** to clear the filters.

Tick rows (or the header checkbox) to open the **Selected Summary** panel: **Selected Items**, **Total Amount**, **Average**, and **Status Mix**. Keyboard shortcuts: **Ctrl+A** selects all, **Esc** clears the selection. The **Update Many** button on this card automatically loads the **onhand** bilyets you ticked.

Icon buttons in the **Action** column: edit/release (pencil), **Cairkan** (money), **Void** (ban), **View Details** (eye), **History** (clock), **delete** (trash, only for **onhand** and with `delete_bilyet`), and **Superadmin Edit** (cog) for users with the **superadmin** role.

### Reports tab

The **Bilyet Reports & Analytics** card offers **Date From**, **Date To**, and **Project** filters, with **Load Report** (loads metrics: bilyet counts, status/type distribution, monthly trends, bank distribution, most active users) and **Export** to download the data. Report data comes from the bilyet module's report endpoints.

## Audit Trail and Bilyet History

### Audit Trail (all bilyets)

Open the **Audit Trail** tab from the navigation card. Filters: **Action** (**All Actions**, **Created**, **Updated**, **Status Changed**, **Voided**, **Bulk Updated**), **Date From**, and **Date To**. Table columns: **Date & Time**, **Action**, **Bilyet**, **User**, **Changes**, **IP Address**, **Actions**.

### Per-bilyet history

Click the **History** icon on a bilyet row (or the **View** link from a BAPSB page) to open the history page: bilyet identity, status, type, and a chronological **timeline** of every activity with the user and IP address.

## Filling Cheque / Bilyet in Bank Transaction

The **Cheque / Bilyet** field on **Cashier → Bank Transactions** (the **Create** or **Edit** form) links a bank transaction to the bilyet behind it.

1. Open **Cashier → Bank Transactions → Create**.
2. Choose the **Bank Account** first.
3. The **Cheque / Bilyet** dropdown then loads for that account. The default option **— none —** means no bilyet is linked.
4. Pick a bilyet. The option label contains the number (`prefix` + `nomor`), bilyet date, amount, status, and project, e.g. `GH123456 · 2026-09-19 · 1.500.000 · release · 025C`.
5. Save the transaction.

Things to know:

- The list contains **only** bilyets of the selected bank account, i.e. bilyets whose `bilyets.giro_id` points to a giro whose **`sap_account`** equals the transaction's **Bank Account**.
- This field is an **application reference only**. The bilyet number is **not sent to SAP** and does **not** change the bilyet's status, amount, or dates.
- If the bilyet does not match the account, the system rejects the save with *The selected cheque/bilyet does not belong to the selected bank account.* (or *… is not linked to a valid bank giro account.* when the bilyet's giro is missing).
- The transaction **detail** page shows a **Cheque / Bilyet** row with the bilyet number, date, amount, and status, linking to that bilyet's history.

If the dropdown is empty, see **Troubleshooting: Empty Cheque / Bilyet Dropdown and Other Issues**.

## Creating the Monthly BAPSB

A **BAPSB** is the physical inspection report of bank securities: **one document per project per month**. It requires the **`akses_bapsb`** permission.

1. Open **Cashier → BAPSB** (card **Bank Securities Inspection Report (BAPSB)**).
2. Click **+ BAPSB**. The **Create BAPSB** page opens with the **Header** card.
3. Choose **Period** (the last 18 months) and **Project**. Only projects with a giro account flagged `needs_bilyet` can be selected. Changing Period or Project reloads the bilyet list.
4. Fill in **Report date** (the report date, default today), **Checked by 1**, **Checked by 2** (both required — the inspector names), and **Approved by (optional)**. **Approved by** is genuinely optional: a site unit may leave it empty because final validation happens in Accounting HO.
5. Review the bilyet list that appears automatically (see the next section) and fill in the physical inspection result per line.
6. Check the **Summary** card: count and total amount per type — **Bilyet Giro**, **Checks**, **LOA** — plus **Mutations this period** (**Settled** = bilyets settled that month, **Voided** = bilyets voided that month).
7. Click **Save draft**. The report number is generated automatically as **BAPSB-0001/{project}/{MM-YYYY}**, e.g. `BAPSB-0001/025C/09-2026`. Initial validation status: **pending**.

If the list is empty the page shows the warning *No on-hand / released bilyets for this period on giro accounts. Register bilyets first or choose another period.* and **Save draft** is disabled. The system also blocks a duplicate report for the same project and period (*BAPSB for this project and period already exists.*) and verifies that the line list still matches the master at save time (*Bilyet list does not match the selected period. Refresh and try again.*).

### Which bilyets appear in the BAPSB

The list is pulled automatically from the bilyet master (never retyped), using these criteria:

- the selected project;
- status **onhand** or **release** (the physical document should still exist);
- **bilyet_date** on or before period end;
- **cair_date** empty or after period end;
- only accounts flagged **`needs_bilyet`** (default: an account of type **giro** needs it, type **tabungan** does not);
- type **debit** is excluded.

Rows are grouped per bank account; the group title uses `sap_account — acc_no (acc_name)` when the giro's `sap_account` is filled in.

### Filling the per-line inspection result

Each bilyet row shows **Type**, **Number**, **Date**, **Amount**, and **Status** (from the master; not editable here). What you fill in:

| Column | Content |
|--------|---------|
| **Physical** | Radio **Present** (physically available) or **Missing** — required |
| **Location** | Storage location: **Brankas Site**, **Lemari Besi Accounting HO**, **Brankas BO**, **Safe Deposit Box Bank**, or **Lainnya** — required |
| **Note** | Location note, e.g. safe number or details when **Lainnya** is chosen |
| **Remarks** | Discrepancy / inspection findings |

After saving, the inspection result can still be corrected: from the detail page click **Edit** while the report has not been submitted. The **edit** page uses the same Header and Summary cards and recalculates the summary from the latest master data.

## Printing the BAPSB and Signature Blocks

1. From the detail page or the list, click **Print**. The print page opens in a new tab and the browser print dialog starts automatically.
2. The print page shows the heading **BERITA ACARA PEMERIKSAAN SURAT BERHARGA BANK**, the report number, **Project**, **Period**, and **Date**, followed by a table with **#**, **Type**, **Number**, **Bank account**, **Date**, **Amount**, **Physical** (**Ada**/**Tidak ada**), and **Location**.
3. Below it the **Summary** table is printed (Bilyet Giro, Checks, LOA, Settled, Voided).
4. The signature block has four columns:

| Printed column | Content |
|----------------|---------|
| **Prepared by** | Name of the preparer (automatic, from the user who saved the draft) |
| **Checked by 1** | The value you entered in **Checked by 1** |
| **Checked by 2** | The value you entered in **Checked by 2** |
| **Approved by** | The value of **Approved by**; printed as `—` when left empty |

5. Print the document, collect the signatures for each column, then scan the result to **PDF**.

## Uploading the Signed PDF and Submitting the BAPSB

1. Open the report detail page, then under the **Upload signed PDF** card choose the scanned file.
2. Click **Upload PDF**. Limits: **PDF only**, maximum **5 MB**. The file is stored as a document of type **`bapsb`** for that project with validation status **pending**. Uploading again replaces the previous PDF.
3. Click **Submit for validation**. The button activates only after a PDF exists; otherwise you get *Upload the signed PDF before submitting.* After submitting, the message *BAPSB submitted for HO validation.* appears and the submission timestamp is recorded.
4. Once submitted the report is locked: the **Edit** button disappears (*Submitted BAPSB cannot be edited.*) and the PDF cannot be replaced (*Cannot replace PDF after submission.*). If something is wrong, coordinate with an administrator — the interface offers no cancel/unlock action.

## Validating BAPSB as Accounting HO

Validation is performed by users holding the **`validate_bapsb_report`** permission (see **Permissions Reference**).

1. When reports have been submitted, the **BAPSB pending validation** card appears on the dashboard (the count of reports already submitted and still **pending**); click it to open the outstanding list.
2. Alternatively open **Cashier → BAPSB → /cashier/bapsb/outstanding** (`Late submissions by unit` and `Pending HO validation`).
3. Open a report from the **Action** column of the **Pending HO validation** table (the **View** button).
4. On the detail page, in the **HO validation** card: fill in **Note (optional)**, then click **Validate BAPSB**.
5. The report status moves from **pending** to **validated**, and its PDF is marked validated as well, together with the validator name and timestamp.

Current implementation note: only **Validate** is available. If a report must go back to the unit, use the **Note** field and communicate outside the application — there is no reject/unlock button in the interface.

## Compliance, Deadline, and Outstanding List

- **Submission deadline:** the **5th of the following month** (end of day) for the previous month's period.
- **Unit warning:** when the previous month's period is still unsubmitted past the deadline, users with **`akses_bapsb`** in that unit see the warning banner **BAPSB submission overdue** with the period and due date, plus a **Create BAPSB** link.
- **Central outstanding list:** the page **/cashier/bapsb/outstanding** (permission **`validate_bapsb_report`**) shows the **Late submissions by unit** card with columns **Project**, **Period**, **Deadline** for the last 12 months through the current month, plus the **Pending HO validation** card (columns **Number**, **Project**, **Submitted**).
- **No blocking:** a late BAPSB only produces a warning and the outstanding list. Cashier transactions keep working normally (unlike PCBC).
- **Not applicable to savings accounts:** the requirement only applies to projects with a giro account flagged `needs_bilyet`.
- **Not yet available:** the cross-month compliance recap (which unit was late and how often) is still planned.

## Permissions Reference

| Permission | Purpose |
|------------|---------|
| **`akses_bilyet`** | The **Cashier → Administrasi Bilyet** menu and all bilyet pages (Dashboard, List, Upload, Audit Trail, Reports) |
| **`add_bilyet`** | The **+ Bilyet** and **Update Many** buttons; the right to create bilyets |
| **`delete_bilyet`** | The delete button on bilyets with status **onhand** |
| **`akses_bapsb`** | The **Cashier → BAPSB** menu and all unit BAPSB pages |
| **`validate_bapsb_report`** | The **HO validation** card (the **Validate BAPSB** action), the outstanding page **/cashier/bapsb/outstanding**, and the **BAPSB pending validation** dashboard card |
| **`akses_giro`** | The **Accounting → Giro** master (source of the account list and the `sap_account` column) |
| **`akses_cashier_modal`**, etc. | Unrelated to bilyets; listed only to avoid confusion |

Defaults from the **BAPSB** seeder: **`akses_bapsb`** is granted to the roles **superadmin**, **admin**, **cashier**, and **head_cashier**; **`validate_bapsb_report`** is granted to the **head_cashier** role, the **admin**/**superadmin** roles, and the Accounting HO users listed in that seeder.

The bilyet **edit** page (all fields) is restricted to users with the **superadmin** role.

## Common Tasks

**1. Register a single bilyet from a physical document**
**Cashier → Administrasi Bilyet → List → + Bilyet** → fill Prefix, Bilyet No, Bilyet Type, Giro, Bilyet Date, Amount → **Save** → click **Filter** on the **Bilyet List** card to confirm the row appears.

**2. Import hundreds of bilyets from a bank list**
**Administrasi Bilyet → Upload → download template** → fill `acc_no`/`prefix`/`nomor`/`type`/`bilyet_date`/`cair_date`/`amount`/`remarks` → **Upload** → review staging → **Import** → fill **Receive Date** → **Import**.

**3. Mark a bilyet as settled**
**List** → **Filter** status **release** → money icon (**Cairkan**) → enter **Cair Date** (required) → tick **Create SAP Outgoing Payment** when this is a loan payment that must be posted to SAP → **Cairkan**.

**4. Link a cheque to a bank transaction**
**Cashier → Bank Transactions → Create** → choose the **Bank Account** → pick the bilyet in **Cheque / Bilyet** (optional) → save. A cheque missing from the list is either not registered for that account or the account's `sap_account` is empty.

**5. Register a bilyet that has already been settled**
Fill **Amount**, **Bilyet Date**, and **Cair Date** together on the **New Bilyet** form → the status becomes **cair** immediately.

**6. Prepare last month's BAPSB**
**Cashier → BAPSB → + BAPSB** → choose **Period** and **Project** → fill **Report date**, **Checked by 1**, **Checked by 2** → fill **Physical**, **Location**, **Note**, **Remarks** per line → check **Summary** → **Save draft** → **Print** → sign → upload the PDF → **Submit for validation**.

**7. Monitor BAPSB arrears across all units (HO)**
Dashboard → **BAPSB pending validation** card, or **/cashier/bapsb/outstanding** → **Late submissions by unit** (project, period, deadline) and **Pending HO validation** cards → **View** → **Validate BAPSB**.

**8. Trace who changed a bilyet**
**Administrasi Bilyet → List** → **History** icon on the bilyet row (full per-bilyet timeline), or the **Audit Trail** tab filtered by **Action**/**Date From**/**Date To** for all bilyets.

## Troubleshooting: Empty Cheque / Bilyet Dropdown and Other Issues

### 1. The Cheque / Bilyet dropdown on Bank Transaction is empty

This is the most common case. The dropdown only contains bilyets that satisfy **both** conditions:

1. **The bilyet is registered** for that account (a bilyet whose `giro_id` is the selected giro) in **Administrasi Bilyet**; and
2. **The account's Giro master `sap_account` column is filled in** (`Accounting → Giro`).

If either is missing, the list is simply empty with no error message. Fixes:

- Make sure the transaction's **Bank Account** is selected — before that the dropdown only holds **— none —**.
- Check the bilyet listing for that account (**List** → filter **Bank Account**). If empty, register bilyets or import them from Excel.
- Check the **Giro** master: `sap_account` must be filled in and must match the **Bank Account** used by the bank transaction exactly. If empty, ask Accounting HO/an administrator to complete it. Giro accounts already completed include `017C` → `11201028` and `022C` → `11201006`; inactive projects (e.g. `023C`) are not completed yet.
- If options appear and then vanish, check whether the **Bank Account** was changed after picking a bilyet — the list reloads for the active account. A failed load also raises the notice *Failed to load cheque/bilyet options*.
- On save, *The selected cheque/bilyet does not belong to the selected bank account.* means the bilyet and account do not pair: pick another bilyet from that account.

### 2. The Administrasi Bilyet or BAPSB menu is missing / Access Denied

Missing permission. Ask an administrator for **`akses_bilyet`** (Administrasi Bilyet menu) or **`akses_bapsb`** (BAPSB menu). BAPSB validation and the outstanding page need the separate **`validate_bapsb_report`**.

### 3. The + Bilyet or Update Many button is missing

Both buttons need **`add_bilyet`**. If **+ Bilyet** is present but the row **Action** column is empty, the row belongs to another project — rows from other projects are shown to **admin/superadmin** only.

### 4. The Bilyet List table is empty although data exists

The table only loads after a filter is applied: fill at least one filter (e.g. **Status**) and click **Filter**. Without a filter the system deliberately returns an empty result and shows the **Gunakan Filter** hint.

### 5. Bilyet number already exists

The **Prefix + Bilyet No** combination is taken. Check it with the **Nomor Bilyet** filter; fix the prefix/number if it really is a duplicate, or update the existing bilyet instead.

### 6. Invalid status transition from …

A bilyet with status **cair** or **void** can no longer change, and transitions must follow the rules in **Bilyet status and available actions**. Only a **superadmin** can repair this case, through the superadmin edit page, with a reason of at least 10 characters in the remarks.

### 7. The Excel import processes nothing

- Make sure the format is **.xls/.xlsx** and the size is ≤ **10 MB**.
- **Import** is disabled while any row lacks a valid giro account or duplicates exist: clear staging (**Empty Table**), fix `acc_no`, and upload again.
- The message *Import completed but no records were imported…* means the `acc_no` values are not in the **Giro** master — fix the account numbers (avoid Excel scientific notation).
- Columns `prefix` and `nomor` are required on every row; `amount` must be a number ≥ 0.

### 8. BAPSB: No on-hand / released bilyets for this period on giro accounts

Causes: no bilyet is registered for that project/period yet; or every bilyet was **cair**/**void** before period end; or the account is not flagged `needs_bilyet` (a savings account). Register the bilyets first, or choose another Period. Changing the `needs_bilyet` flag per account is not available in the Giro master interface yet — ask an administrator/developer to change it.

### 9. BAPSB: BAPSB for this project and period already exists

A project can only have one report per month. Open the existing report from **Cashier → BAPSB** and continue there (drafts are editable, submitted ones are not).

### 10. Submit for validation is inactive / the PDF can no longer be uploaded

**Submit for validation** stays inactive until a PDF exists (*Upload the signed PDF before submitting.*). Conversely, *Cannot replace PDF after submission.* means the report was already submitted — its PDF and contents can no longer change.

### 11. BAPSB is not found through the top-bar Search Menu

Menu search currently lists **Administrasi Bilyet**, not **BAPSB**. Use the sidebar **Cashier → BAPSB** or the direct address `/cashier/bapsb`.

### 12. HELP Assistant answers still use the older manual

An administrator runs `php artisan help:reindex` on the server after this manual is updated.

## Quick Reference: Statuses, URLs, and Glossary

### Bilyet status map

| System code | Badge on screen | Meaning |
|-------------|-----------------|---------|
| `onhand` | Onhand | Physically in the unit, not handed over |
| `release` | Release | Handed over, not settled |
| `cair` | Cair | Settled by the bank (final) |
| `void` | Void | Cancelled (final) |

### Bilyet types

| System code | Form option | Display label |
|-------------|-------------|---------------|
| `cek` | Cek | Check / CEK |
| `bilyet` | BG | Bilyet Giro / BILYET |
| `loa` | LOA | Letter of Authority / LOA |
| `debit` | not offered | excluded from BAPSB |

### BAPSB statuses

| Value | Meaning on screen |
|-------|-------------------|
| Draft (no **submitted_at**) | Not submitted; still editable, PDF replaceable |
| Submitted (**Submitted** badge) | Submitted; locked |
| `pending` | Awaiting Accounting HO validation |
| `validated` | Validated by HO |

### Key page addresses

| Page | URL / route |
|------|-------------|
| Bilyet Administration — Dashboard | `/cashier/bilyets?page=dashboard` |
| Bilyet Administration — List | `/cashier/bilyets?page=list` |
| Bilyet Administration — Upload | `/cashier/bilyets?page=upload` |
| Audit Trail | `/cashier/bilyets/audit` |
| Reports | `/cashier/bilyets/reports` |
| Cair / Release / Void lists | `/cashier/bilyets/cair`, `/cashier/bilyets/release`, `/cashier/bilyets/void` |
| Import staging | `/cashier/bilyet-temps` |
| BAPSB — list | `/cashier/bapsb` |
| BAPSB — create | `/cashier/bapsb/create` |
| BAPSB — print | `/cashier/bapsb/{id}/print` |
| BAPSB — outstanding | `/cashier/bapsb/outstanding` |
| Bank Transactions | `/cashier/bank-transactions` |
| Giro master | `/accounting/giros` |

### Glossary

| Term | Meaning |
|------|---------|
| **Bilyet** | A bank security: Bilyet Giro, Cheque, or Letter of Authority |
| **Bilyet Giro (BG)** | A fund-transfer instruction issued by a giro account holder |
| **Cheque / Check** | A written payment order to the bank |
| **LOA** | Letter of Authority — handled as a bank security |
| **Giro** | The bank account that issues the securities; source of the **Giro** field list |
| **`sap_account`** | SAP bank account code on the Giro master; links a bilyet to the transaction's **Bank Account** |
| **`needs_bilyet`** | Giro master flag: the account needs a BAPSB (default: giro = yes, tabungan = no) |
| **onhand / release / cair / void** | The four bilyet lifecycle statuses |
| **BAPSB** | Berita Acara Pemeriksaan Surat Berharga Bank — the monthly physical inspection report per project |
| **Cair** | Action/status for a bilyet settled by the bank (**Settled**) |
| **Release** | Action/status for handing the bilyet to the payee |
| **Void** | Action/status cancelling the bilyet |
| **Period mutations** | Count of bilyets settled (**Settled**) and voided (**Voided**) in the report month |
| **Checked by 1 / Checked by 2** | Inspector names entered by the preparer and printed in the signature block |
| **Approved by** | The optional BAPSB form field printed in the **Approved by** signature column |
