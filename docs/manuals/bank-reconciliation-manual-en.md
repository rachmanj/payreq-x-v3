# Bank reconciliation (Cashier)

The **Bank Reconciliation** module matches **bank statement lines** (from a Rekening Koran PDF) to **SAP GL lines** for a bank account (**Giro**) and month.

## How to use Bank Reconciliation

Follow this end-to-end flow:

1. **Upload Rekening Koran (PDF)** — open **Cashier** → **Rekening Koran**. On the **Dashboard**, click an empty month cell for an account. The upload modal opens with account and month locked. Choose the PDF and upload (requires **`upload_koran`**).
2. **Start reconciliation** — after upload, click the same filled cell. In the modal, use **Mulai rekonsiliasi** / **Start reconciliation**, or open **Cashier** → **Bank Reconciliation** → **Create**.
3. **Fill Create form** — choose **Giro**, **period** (month), and the **Rekening Koran** document. Submit. Status starts as **processing**; PDF parsing and SAP GL fetch are queued.
4. **Wait for queue workers** — ensure `php artisan queue:work` is running. Refresh the reconciliation **Review** page until bank and SAP line counts appear.
5. **Match lines** — use **Auto-match**, or select bank + SAP lines and **Match selected**. Use **Unmatch** to remove a match group.
6. **Check balance** — the sticky totals bar should be balanced (difference near zero) before submit.
7. **Submit for validation** — click **Submit for validation**. Editing is locked until a validator approves or rejects.
8. **Validator** (**`validate_bank_reconciliation`**) — open **Bank Reconciliation** → **Pending validation** tab, or the **Bank reconciliation pending validation** card on the main dashboard. Click **Validate** on a session, then **Validate** (approve) or **Reject** on the review page.
9. **Report** — after approval, status is **completed** and validated. Open **Report** / reconciliation report to print the summary.

**Short path:** Upload Koran → Create reconciliation → Auto-match / manual match → **Submit for validation** → validator **Validate** → **Report**.

## Who can open it

Open **Cashier** in the top menu, then **Bank Reconciliation**. You need permission **`akses_koran`** (same area as **Rekening Koran**). Elevated roles (**admin**, **superadmin**, **cashier**, **approver_bo**, **cashier_bo**, **corsec**) see all reconciliations; others only see accounts in their **project**.

You can also type **Bank Reconciliation** in the top-bar **Search Menu here** field (with **`akses_koran`**).

## Prerequisites

- A **Giro** (bank account) exists in the system.
- A **Rekening Koran** document (**type** `koran`) is uploaded for that Giro and month.

## Upload Rekening Koran from the dashboard

Open **Cashier** → **Rekening Koran** (**Dashboard**). Click a month cell:

- **Empty cell** — upload modal with locked account/month (**`upload_koran`**). Duplicate uploads for the same account/month are rejected.
- **Filled cell** — view upload date, open PDF, go to bank reconciliation, or delete PDF (**`delete_koran`**; delete disabled when reconciliation is pending validation or completed).

Small icons on each cell show reconciliation status (not started, processing, in review, pending validation, done).

## Starting a new reconciliation

**From Koran dashboard:** click a filled month cell → **Mulai rekonsiliasi** / start reconciliation in the modal.

**From Bank Reconciliation menu:**

1. **Cashier** → **Bank Reconciliation**.
2. Open **Create** (`/cashier/bank-reconciliation/create`).
3. Choose **Giro**, **period**, and **Rekening Koran** document.
4. Submit. The system queues PDF parsing and SAP GL fetch.

## Reconciliation detail screen (Review)

On the review (**show**) page you can:

- **Re-parse statement** — re-queue PDF parsing.
- **Fetch SAP lines** — re-queue SAP GL fetch.
- **Auto-match** — automatic matching.
- **Manual match** — select bank and SAP lines → match as one group.
- **Unmatch** — remove a match group (not available when locked).
- **Submit for validation** — send to validator when balanced.

Refresh the page while queue jobs finish.

## Validation (validator role)

Users with **`validate_bank_reconciliation`** who did **not** prepare the session can:

- Use the **Pending validation** tab on **Bank Reconciliation**.
- Use the **Bank reconciliation pending validation** card on the main dashboard.
- On a **pending validation** review page: **Validate** (approve → **completed**, open **Report**) or **Reject** (return with reason).

## Reconciliation report

After validator approval, open **Report** from the review page or the green cell icon on the Koran dashboard. The report includes balance summary, outstanding lines, and **Print**.

## Permissions

| Permission | Purpose |
|------------|---------|
| **`akses_koran`** | Access **Rekening Koran** and **Bank Reconciliation** |
| **`upload_koran`** | Upload Koran PDF from the Koran dashboard |
| **`delete_koran`** | Delete Koran PDF (project-scoped; blocked when reconciliation is locked) |
| **`validate_bank_reconciliation`** | Approve or reject submitted reconciliations |

## Troubleshooting

- **Menu missing** — ask an administrator for **`akses_koran`**.
- **Empty parsing / SAP** — check valid PDF, queue workers, SAP B1 Service Layer settings.
- **Cannot Submit for validation** — ensure bank vs book difference is balanced.
- **Cannot delete PDF** — reconciliation may be **pending validation** or **completed**.
- **Outdated HELP answers** — administrator runs `php artisan help:reindex` after manual updates.
