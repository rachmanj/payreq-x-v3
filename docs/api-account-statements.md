**Purpose**: Public JSON API for retrieving account statements from SAP-Bridge
**Last Updated**: 2025-11-16

## Overview

This API allows external systems to retrieve detailed account statements from the SAP-Bridge accounting module using `account_code`. It returns opening balance, transactions with running balances, and closing balance for a specified date range.

Authentication is handled via a shared API key stored in the environment as `SAP_BRIDGE_API_KEY` and sent by clients in the `x-sap-bridge-api-key` header.

---

## Endpoint

- **Method**: `GET`
- **URL**: `/api/account-statements`
- **Auth**: API key header

### Authentication

- **Env variable**: `SAP_BRIDGE_API_KEY`
- **Request header**: `x-sap-bridge-api-key: <your_api_key_here>`

If the header is missing or does not match the configured key, the API returns:

```json
{
  "message": "Unauthorized."
}
```

Status code: `401 Unauthorized`

---

## Request Parameters

All parameters are sent as **query string** parameters.

- **account_code** (required, string)
  - The account code from the `accounts.code` column (e.g. `"11501004"`).
  - Must exist in `accounts` and be active (`is_active = 1`).
- **start_date** (required, string, `YYYY-MM-DD`)
  - Start of the reporting period (by `posting_date`).
- **end_date** (required, string, `YYYY-MM-DD`)
  - End of the reporting period (by `posting_date`).
  - Must be greater than or equal to `start_date`.

**Date range constraints**:

- The period between `start_date` and `end_date` **must not exceed 6 months**.
- If it does, the API returns:

```json
{
  "message": "Date range cannot exceed 6 months."
}
```

Status code: `422 Unprocessable Entity`

---

## Response Format

On success, the endpoint returns HTTP `200 OK` with JSON matching the output of `AccountStatementService::getAccountStatement`:

- **account**: basic account metadata
  - `id` (integer)
  - `code` (string)
  - `name` (string)
  - `account_type` (string: `"ASSET" | "LIABILITY" | "EQUITY" | "REVENUE" | "EXPENSE"`)
- **start_date** (string, `YYYY-MM-DD`)
- **end_date** (string, `YYYY-MM-DD`)
- **opening_balance** (number)
- **closing_balance** (number)
- **transactions** (array of objects)
  - `id` (integer)
  - `posting_date` (string, `YYYY-MM-DD`)
  - `doc_num` (string or null)
  - `doc_type` (string or null)
  - `tx_num` (string or null)
  - `description` (string or null)
  - `debit_amount` (number)
  - `credit_amount` (number)
  - `project_code` (string or null)
  - `department_name` (string or null)
  - `unit_no` (string or null)
  - `running_balance` (number) — calculated running balance after this transaction
- **summary**
  - `total_debit` (number)
  - `total_credit` (number)
  - `transaction_count` (integer)

All numeric amounts are returned as raw numbers (no formatting), suitable for external PDF/Excel generation.

---

## Example Request

Example HTTP request:

```http
GET /api/account-statements?account_code=11501004&start_date=2025-01-01&end_date=2025-03-31 HTTP/1.1
Host: your-sap-bridge-domain.test
x-sap-bridge-api-key: YOUR_API_KEY_HERE
Accept: application/json
```

Example `curl`:

```bash
curl -X GET "http://localhost:8000/api/account-statements?account_code=11501004&start_date=2025-01-01&end_date=2025-03-31" \
  -H "x-sap-bridge-api-key: YOUR_API_KEY_HERE" \
  -H "Accept: application/json"
```

---

## Example Success Response (truncated)

```json
{
  "account": {
    "id": 1,
    "code": "11501004",
    "name": "Inventories - Sparepart",
    "account_type": "ASSET"
  },
  "start_date": "2025-01-01",
  "end_date": "2025-03-31",
  "opening_balance": 10000,
  "closing_balance": 14500,
  "transactions": [
    {
      "id": 123,
      "posting_date": "2025-01-05",
      "doc_num": "GI-0001",
      "doc_type": "Goods Issue",
      "tx_num": "TX-10001",
      "description": "Sparepart issue",
      "debit_amount": 0,
      "credit_amount": 500,
      "project_code": "022C",
      "department_name": "Plant",
      "unit_no": "ADT 015",
      "running_balance": 9500
    }
  ],
  "summary": {
    "total_debit": 6000,
    "total_credit": 1500,
    "transaction_count": 5
  }
}
```

---

## Error Responses

- **401 Unauthorized**
  - Missing or invalid API key.
  - Body: `{"message": "Unauthorized."}`

- **404 Not Found**
  - `account_code` not found or account is inactive.
  - Body: `{"message": "Account not found or inactive."}`

- **422 Unprocessable Entity**
  - Validation errors (e.g. invalid dates, range > 6 months).
  - Example body:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "start_date": ["The start date does not match the format Y-m-d."]
  }
}
```

Or for range violation:

```json
{
  "message": "Date range cannot exceed 6 months."
}
```

---

## Notes for Integrators

- Use `account_code` from SAP B1 exports (mapped into `accounts.code`) for stable integration keys.
- Keep each API call within a 6-month window; for longer periods, split into multiple requests.
- Use the returned numeric fields directly when generating your own PDF/Excel reports.

---

## Testing & Verification

- **Manual Test (2025-11-16)**  
  Command (PowerShell):

  ```powershell
  Invoke-WebRequest -Uri "http://localhost:8000/api/account-statements?account_code=11501004&start_date=2025-01-01&end_date=2025-03-31" `
    -Headers @{ "x-sap-bridge-api-key" = "mantapjiwa"; "Accept" = "application/json" }
  ```

  Result: `HTTP 200 OK` with payload containing `account`, `opening_balance`, `closing_balance`, `transactions`, and `summary`. This confirms the middleware, validation, and service integration work end-to-end with real data.



