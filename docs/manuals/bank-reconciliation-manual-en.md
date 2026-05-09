# Bank reconciliation (Cashier)

This module matches **bank statement lines** (from a Rekening Koran PDF) to **SAP GL lines** for a given bank account (Giro) and month.

## Who can open it

Open **Cashier** in the top menu, then **Bank Reconciliation**. You also need permission **akses_koran** (same area as **Rekening Koran**). Elevated roles (for example **admin**, **superadmin**, **cashier**) can see all reconciliations; other users only see reconciliations for bank accounts in their **project**.

## Prerequisites

- A **Giro** (bank account) record exists in the system.
- A **Rekening Koran** document (**type** `koran`) is uploaded for that Giro in **Rekening Koran** (Cashier → Rekening Koran), for the period you want to reconcile.

## Starting a new reconciliation

1. Go to **Cashier** → **Bank Reconciliation**.  
2. Use the action to open **Create** (URL path: `/cashier/bank-reconciliation/create`).  
3. Choose the **Giro** (bank account).  
4. Choose the **period** (month).  
5. Choose the **Rekening Koran** document (`koran`) for that Giro.  
6. Submit. The system creates a reconciliation in **processing** status and queues:
   - parsing the bank statement PDF into **bank statement lines**;
   - fetching **SAP GL lines** for that account/period.

Wait for the queue workers to finish. Refresh the reconciliation detail page to see updated line counts.

## Reconciliation detail screen

On the reconciliation **show** page you can:

- **Re-parse statement** — queues the PDF parsing job again (if the Koran document is attached).  
- **Fetch SAP lines** — queues another SAP GL fetch.  
- **Auto-match** — queues automatic matching of bank lines to SAP lines.  
- **Manual match** — select one or more bank statement lines and one or more SAP GL lines to form a **match group**.  
- **Unmatch** — remove an existing match group (not available after completion).  

Use the **status** endpoint or UI refresh to confirm bank line count, SAP line count, and match groups increase as jobs complete.

## Completing reconciliation

When matching is satisfactory, use **Complete** to mark the reconciliation **completed**. After that:

- Further auto-match, manual match, and unmatch actions are blocked.  
- You are redirected to the **report** view for this reconciliation.

## Permissions and troubleshooting

If you cannot see **Bank Reconciliation**, ask an administrator for **akses_koran** (and appropriate Cashier access). If parsing or SAP jobs stay empty, verify the Koran PDF is valid, queue workers are running, and SAP bridge/settings are configured for GL fetch.
