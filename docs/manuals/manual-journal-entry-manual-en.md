# Manual Journal Entry (Accounting)

The **Manual Journal Entry** module lets authorized accounting staff create balanced journal vouchers in AccountingOne, optionally from reusable **JE Templates**, then **Submit to SAP B1** so the entry is posted in SAP. This is separate from **Verification Journal** (cashier verification workflow).

## Who can open it

Open the left sidebar **Accounting** → **Journal Entries** or **JE Templates**.

You need permission **`create_manual_journal_entry`**. If the menu items are missing, ask an administrator to grant that permission (typically **superadmin**, **admin**, **cashier**, or **approver** roles).

You can also type **Journal Entries** or **JE Templates** in the top-bar **Search Menu here** field.

Top navbar **Accounting** dropdown lists the same links when you have the permission.

## Journal Entries list

**Accounting** → **Journal Entries** (`/accounting/journal-entries`) shows all manual journal entries in a searchable table.

Columns include **Number**, **Date**, **Memo**, **Status** (Draft / Posted / Failed / Reversed), **SAP Journal No**, and **Created By**.

Use **New Journal Entry** to create a draft. Row actions: **View** (eye), **Edit** and **Delete** (draft only), **Print**.

## Create a manual journal entry

1. Open **Accounting** → **Journal Entries** → **New Journal Entry**.
2. Fill **Date** (required), optional **Reference** and **Memo**.
3. Optional — **Load from Template**: choose a template from the dropdown. Lines load automatically (account, Dr/Cr, project, cost center, description). You still enter **amounts** on each line.
4. In **Journal Lines**, add or edit rows:
   - **Account** — type to search; pick from autocomplete suggestions.
   - **Dr/Cr** — Debit or Credit.
   - **Amount** — required on each line.
   - **Project** and **Cost Center** — select when required by your chart of accounts.
   - **Description** — line memo.
5. Use **Add Line** for more rows (minimum two lines). Footer shows **Total Debit**, **Total Credit**, and **Difference** — difference must be **0.00** before save.
6. Click **Save Journal Entry**.

After save you are redirected to the **show** page. The system assigns a number like **JE-000001**.

## Edit or delete a draft

On the journal **show** page, while status is **Draft** (not yet posted to SAP):

- **Edit** — change header and lines (balance rules still apply).
- **Delete** — from the list row action (trash) or only when still editable.

Posted or reversed entries cannot be edited or deleted in AccountingOne.

## Submit to SAP B1

On the **show** page, for a balanced **Draft** entry:

1. Review header and **Journal Lines**.
2. Click **Submit to SAP B1** and confirm the dialog.
3. On success, status becomes **Posted**, **SAP Journal No** is filled, and **Submission History** appears.

Submit is allowed for users with roles **superadmin**, **admin**, **cashier**, or **approver** (in addition to **`create_manual_journal_entry`**).

If submission fails, status shows **Failed** and **Last Error** displays the SAP message. Fix data if needed (while still draft) or contact support.

## Reverse in SAP B1

After a successful post, users with permission **`cancel_sap_journal`** see **Reverse in SAP B1** on the show page.

1. Click **Reverse in SAP B1**.
2. Enter **Reason** (required) in the modal.
3. Confirm. SAP cancels the journal; the entry is marked **Reversed**.

## Print voucher

Click **Print** on the show page or list row. Opens **Journal Voucher** in a new tab (number, date, reference, memo, lines, prepared by).

## JE Templates

**Accounting** → **JE Templates** (`/accounting/journal-entries/templates`) lists shared templates (global — any authorized user can use any template).

### Create or edit a template

1. **New Template** (or edit from the list).
2. Enter **Name** and optional **Description**.
3. Define **Journal Lines** with account, Dr/Cr, project, cost center, description. **Default Amount** is optional (used as a hint when loading into a new JE).
4. **Save Template**.

Templates do not post to SAP by themselves. When creating a JE, use **Load from Template** to copy line layout, then enter amounts and save.

## Status summary

| Status | Meaning |
|--------|---------|
| **Draft** | Saved in AccountingOne only; editable |
| **Posted** | Submitted to SAP B1 successfully |
| **Failed** | SAP submission attempted but failed |
| **Reversed** | Posted journal was cancelled in SAP B1 |

## Related permissions

| Permission | Purpose |
|------------|---------|
| **`create_manual_journal_entry`** | Menu, list, create, edit, delete draft, templates, submit UI |
| **`cancel_sap_journal`** | **Reverse in SAP B1** on posted entries |

## Help assistant

Click the **?** icon in the top bar (**Help**). On the **How-to** tab, ask questions such as “How do I create a manual journal entry?” or “How do I submit JE to SAP?”. Answers use these manuals after `php artisan help:reindex`.
