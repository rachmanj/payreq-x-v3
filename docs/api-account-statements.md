**Purpose**: Account statement retrieval via SAP B1 Service Layer (replaces SAP-Bridge)
**Last Updated**: 2026-07-20

## Overview

Cashier SAP Transactions and Bank Reconciliation fetch GL account statements directly from SAP B1 Service Layer through `App\Services\SapService::getAccountStatement()`.

The former SAP-Bridge microservice (`SAP_BRIDGE_*` env keys / `App\Services\SapBridge\*`) has been removed.

## Configuration

| Env | Config key | Default | Notes |
|---|---|---|---|
| `SAP_ACCOUNT_STATEMENT_MODE` | `services.sap.account_statement.mode` | `auto` | `auto` \| `sql` \| `odata` |
| `SAP_ACCOUNT_STATEMENT_UNIT_UDF` | `services.sap.account_statement.unit_udf` | `MIS_UnitNo` | Source-doc UDF alias on **OIGE/ODLN** (normalized to `U_MIS_UnitNo`). Not a JDT1 column. Blank = omit unit_no |
| `SAP_ACCOUNT_STATEMENT_ODATA_LOOKBACK_START` | `services.sap.account_statement.odata_lookback_start` | _(empty)_ | Opening-balance floor for OData mode; empty = 1 year before start |

Also requires the existing Service Layer credentials: `SAP_SERVER_URL`, `SAP_DB_NAME`, `SAP_USER`, `SAP_PASSWORD`.

## Strategies

1. **`sql` / `auto` (preferred)** — Service Layer `SQLQueries` on `OJDT`/`JDT1`
   - Opening balance: `SUM(Debit)` − `SUM(Credit)` where `RefDate < start_date` (this SL rejects `SUM(Debit-Credit)` and quoted identifiers)
   - Period lines via `OJDT`/`JDT1`; `doc_num` = `OJDT.BaseRef`; `doc_type` = `JDT1.TransType` mapped to labels (see `docs/je_daily.sql`)
   - **`unit_no`**: OIGE/ODLN are **not accessible** via SQLQueries on this SL. After lines load, enrich TransType `60`/`15` rows by batch OData lookup on `InventoryGenExits` / `DeliveryNotes` (`DocNum` + configured UDF). Null for other TransTypes.
   - Running / closing balances computed in PHP; SAP `YYYYMMDD` dates normalized to `Y-m-d`
2. **`odata` / `auto` fallback** — `JournalEntries` (no `$expand`; `JournalEntryLines` is already a collection property)
   - Used when SQLQueries is unavailable or fails
   - Opening balance lookback defaults to 1 year before `start_date` (chunked by month); set `SAP_ACCOUNT_STATEMENT_ODATA_LOOKBACK_START` to override
   - `doc_type` labels applied when TransType-like codes are present
   - **`unit_no` stays null** on pure OData path (lines lack reliable numeric TransType for the same enrichment)

Probe SQLQueries availability:

```bash
php artisan sap:probe-sql-queries
php artisan sap:probe-sql-queries --forget-cache
```

## Consumers

| Feature | Entry point |
|---|---|
| Cashier → SAP Transactions | `POST cashier/sap-transactions/data` → `SapTransactionController@data` |
| Bank Reconciliation GL fetch | `FetchSapGlLinesJob` |

## Response shape

Same payload shape formerly returned by SAP-Bridge:

- `account` — `{ id, code, name, account_type }` (from local `sap_accounts` / `accounts`)
- `start_date`, `end_date`
- `opening_balance`, `closing_balance`
- `transactions[]` — `posting_date`, `doc_num`, `doc_type`, `tx_num`, `description`, `debit_amount`, `credit_amount`, `project_code`, `unit_no`, `running_balance`, …
- `summary` — `total_debit`, `total_credit`, `transaction_count`

Date range for the cashier UI is still limited to **6 months**.
