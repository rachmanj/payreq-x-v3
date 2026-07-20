# Bank reconciliation (Cashier)

The **Bank Reconciliation** module matches **bank statement lines** (from a Rekening Koran PDF or manual entry) to **SAP GL lines** for a bank account (**Giro**) and calendar month. Matching uses **opposite debit/credit polarity** (bank debit pairs with SAP credit). Submit is allowed only when a formal **reconciliation statement** is reconciled: adjusted bank balance equals adjusted book balance.

## How to use Bank Reconciliation

Follow this end-to-end flow:

1. **Upload Rekening Koran (PDF)** — open **Cashier** → **Rekening Koran**. On the **Dashboard**, click an empty month cell for an account. The upload modal opens with account and month locked. Choose the PDF and upload (requires **`upload_koran`**). Skip this step if you will use **manual** bank lines only.
2. **Start reconciliation** — after upload, click the same filled cell → **Mulai rekonsiliasi** / **Start reconciliation**, or open **Cashier** → **Bank Reconciliation** → **Create**.
3. **Fill Create form** — choose **source mode** (**AI** = parse PDF, or **Manual** = type bank lines yourself), **Giro**, **period** (month), and for AI mode the **Rekening Koran** document. Submit. AI mode starts as **processing** (PDF parse + SAP fetch queued). Manual mode starts as **in review** (SAP fetch queued; you add bank lines yourself).
4. **Wait for queue workers** — ensure `php artisan queue:work` is running. Refresh the reconciliation **Review** page until bank and SAP line counts appear. If a job fails, a red **Last error** banner shows the reason (also in session notes).
5. **Set opening / closing balances** — on Review, fill **Opening — Bank**, **Closing — Bank**, **Opening — Book**, **Closing — Book**, then **Save balances**. AI parse and SAP fetch fill these when available; you can edit them. **Both closing balances are required** before submit.
6. **Match lines** — use **Auto-match**, or select bank + SAP lines (bottom bar shows selection nets) and **Match selected as group**. Use **Unmatch** to remove a match group. Optionally **Exclude** unmatched lines with a reason, or set **Type** (reconciling category) on unmatched lines.
7. **Check reconciliation statement** — the yellow statement bar must show **Reconciled** (unexplained difference near zero). Submit stays disabled while **Incomplete** or **Not reconciled**.
8. **Submit for validation** — click **Submit for validation**. Editing is locked. Validators with **`validate_bank_reconciliation`** receive a notification.
9. **Validator** — open **Bank Reconciliation** → **Pending validation** tab, or the dashboard card. **Validate** (approve → **completed**, open **Report**) or **Reject** (preparer is notified with the reason).
10. **Report / export** — after approval (or anytime you can view the session), open **Report** to print, or **Export Excel** for the statement file.

**Short path:** Upload Koran → Create (AI or Manual) → Save closing balances → Auto-match / manual match → statement **Reconciled** → **Submit for validation** → validator **Validate** → **Report** / **Export Excel**.

## Who can open it

Open **Cashier** → **Bank Reconciliation**. You need permission **`akses_koran`** (same area as **Rekening Koran**). Routes also enforce **`akses_koran`**. Elevated roles (**admin**, **superadmin**, **cashier**, **approver_bo**, **cashier_bo**, **corsec**) see all projects; others only see Giros in their **project**.

You can type **Bank Reconciliation** in the top-bar **Search Menu here** field (with **`akses_koran`**).

## Prerequisites

- A **Giro** (bank account) exists; for SAP fetch, **`sap_account`** on the Giro (or a fallback bank **Account** for the project) should be set.
- For **AI** mode: a **Rekening Koran** document (**type** `koran`) for that Giro and month.
- Queue worker running for parse, SAP fetch, and auto-match jobs.

## Upload Rekening Koran from the dashboard

Open **Cashier** → **Rekening Koran** (**Dashboard**). Click a month cell:

- **Empty cell** — upload modal with locked account/month (**`upload_koran`**). Duplicate uploads for the same account/month are rejected.
- **Filled cell** — view upload date, open PDF, go to bank reconciliation, or delete PDF (**`delete_koran`**; delete disabled when reconciliation is pending validation or completed).

Small icons on each cell show reconciliation status (not started, processing, in review, pending validation, done).

## Starting a new reconciliation

**From Koran dashboard:** click a filled month cell → **Mulai rekonsiliasi** / start reconciliation.

**From Bank Reconciliation menu:**

1. **Cashier** → **Bank Reconciliation** → **Create**.
2. Choose **AI** or **Manual** source mode, **Giro**, **period**, and (AI only) **Rekening Koran** document.
3. Submit. AI: queues PDF parsing and SAP GL fetch. Manual: queues SAP fetch only; add bank lines on Review.

## Opening and closing balances

On the Review page, the **Opening / closing balances** card stores:

| Field | Meaning |
|-------|---------|
| **Opening — Bank** / **Closing — Bank** | Balances from the bank statement (often from AI parse) |
| **Opening — Book** / **Closing — Book** | Balances from SAP (often from Fetch SAP) |

Click **Save balances** after editing. The reconciliation statement uses **closing** balances:

- **Adjusted bank** = Closing bank + sum of unmatched **book** (SAP) line nets  
- **Adjusted book** = Closing book − sum of unmatched **bank** line nets  
- **Unexplained difference** = Adjusted bank − Adjusted book (must be near **0** to submit)

If either closing balance is missing, status shows **Incomplete — enter closing balances**.

## Reconciliation detail screen (Review)

On the review (**show**) page you can:

- **Re-parse PDF (AI)** / **Link & Parse** — re-queue PDF parsing. Confirms first: replaces bank lines and clears related match groups.
- **Fetch SAP lines** — re-queue SAP GL fetch. Confirms first: replaces SAP lines and clears related match groups.
- **Auto-match** — exact, fuzzy (text / AI), and split (N:M) matching. Manual match groups are kept when re-running auto-match.
- **Manual match** — checkboxes on unmatched lines; bottom bar shows selection nets; **Match selected as group** when bank net + SAP net ≈ 0.
- **Add / Edit / Delete bank line** — for manual lines or corrections (unmatched only).
- **Exclude / Include** — remove a line from statement totals with a required reason (or bring it back).
- **Type** — optional reconciling category on unmatched lines (annotation for the report): e.g. deposit in transit, outstanding payment, bank charge not booked, credit/interest not booked.
- **Submit for validation** — enabled only when the statement is **Reconciled**.

Watch the yellow **Reconciliation statement** bar (closing / adjusted balances / unexplained difference) and any red **Last error** banner from failed jobs.

## Validation (validator role)

Users with **`validate_bank_reconciliation`** who did **not** prepare or submit the session can:

- Use the **Pending validation** tab on **Bank Reconciliation**.
- Use the **Bank reconciliation pending validation** card on the main dashboard.
- Receive a **notification** when someone submits a session for validation.
- On a **pending validation** review page: **Validate** (approve → **completed**, open **Report**) or **Reject** (return with reason; preparer is notified).

## Reconciliation report and Excel export

Open **Report** from the review page or the Koran dashboard. The report shows the formal statement (balance per bank / books, reconciling items by category, adjusted balances, unexplained difference, excluded lines, sign-off). Use **Print**, or **Export Excel** to download the statement workbook.

## Permissions

| Permission | Purpose |
|------------|---------|
| **`akses_koran`** | Access **Rekening Koran** and **Bank Reconciliation** (menu and routes) |
| **`upload_koran`** | Upload Koran PDF from the Koran dashboard |
| **`delete_koran`** | Delete Koran PDF (project-scoped; blocked when reconciliation is locked) |
| **`validate_bank_reconciliation`** | Approve or reject submitted reconciliations |

## Troubleshooting

- **Menu missing / Access Denied** — ask an administrator for **`akses_koran`**.
- **Last error banner / status failed** — read the notes (SAP account missing, SAP connection, PDF parse failure). Fix the cause, then re-run **Fetch SAP** or **Re-parse**.
- **Empty SAP lines** — check Giro **`sap_account`**, Service Layer config, and queue workers.
- **Cannot Submit for validation** — save both **closing** balances; match or classify outstanding items until unexplained difference ≈ 0. Excluding a line removes it from totals (use sparingly; prefer matching or reconciling types).
- **Re-parse / Fetch wiped my matches** — expected after confirm; those actions replace lines and clear related match groups.
- **Cannot delete PDF** — reconciliation may be **pending validation** or **completed**.
- **Validator not notified** — ensure queue worker is running and mail/database notifications are configured.
- **Outdated HELP answers** — administrator runs `php artisan help:reindex` after manual updates.
