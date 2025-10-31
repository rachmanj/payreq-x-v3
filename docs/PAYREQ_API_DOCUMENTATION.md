# Payment Request API Documentation

## Overview

The Payment Request API provides secure access for external applications to create, retrieve, and manage payment requests in the accounting system. This API supports both advance and reimburse payment request types with comprehensive workflow management.

## Base URL

```
https://your-domain.com/api
```

## Authentication

All API requests require a custom API key to be included in the request headers.

### API Key Header

```
X-API-Key: YOUR_API_KEY
```

**Note**: API keys are provided by system administrators. Contact your administrator to obtain an API key.

### Authentication Flow

1. Administrator generates API key via admin panel
2. Raw key is provided once (e.g., `ak_abc123def456...`)
3. External application stores the raw key securely
4. Include key in `X-API-Key` header for all requests
5. System validates key and tracks usage

### Security Features

-   One-way SHA256 hashing (keys cannot be recovered if lost)
-   Instant activation/deactivation
-   Usage tracking (last_used_at timestamp)
-   Application-level permissions (future enhancement)

## API Endpoints Summary

All endpoints require authentication via `X-API-Key` header.

| Method | Endpoint                   | Description                                |
| ------ | -------------------------- | ------------------------------------------ |
| GET    | `/api/payreqs`             | List payment requests with filtering       |
| GET    | `/api/payreqs/{id}`        | Get detailed payment request information   |
| POST   | `/api/payreqs/advance`     | Create advance payment request             |
| POST   | `/api/payreqs/reimburse`   | Create reimburse payment request           |
| POST   | `/api/payreqs/{id}/cancel` | Cancel draft payment request               |
| GET    | `/api/payreqs/rabs`        | Get available RAB/Budget list for employee |

## Quick Start

### Typical Workflow

1. **Get Available Budgets** (optional, but recommended for 000H/APS projects):

    ```bash
    GET /api/payreqs/rabs?employee_id=123
    ```

2. **Create Payment Request**:

    ```bash
    POST /api/payreqs/advance
    {
      "employee_id": 123,
      "remarks": "Travel expenses",
      "amount": 5000000,
      "rab_id": 10,
      "submit": true
    }
    ```

3. **Check Status**:
    ```bash
    GET /api/payreqs/{id}
    ```

### Common Use Cases

**Use Case 1**: Create draft for later review

```json
POST /api/payreqs/advance
{ "employee_id": 123, "remarks": "...", "amount": 1000000, "submit": false }
```

**Use Case 2**: Direct submission with budget

```json
POST /api/payreqs/advance
{ "employee_id": 123, "remarks": "...", "amount": 5000000, "rab_id": 10, "submit": true }
```

**Use Case 3**: Reimbursement with multiple items

```json
POST /api/payreqs/reimburse
{
  "employee_id": 123,
  "remarks": "Office supplies",
  "submit": true,
  "details": [
    {"description": "Paper", "amount": 500000},
    {"description": "Ink", "amount": 300000}
  ]
}
```

## Endpoints

### 1. List Payment Requests

Retrieve a paginated list of payment requests with optional filtering.

**Endpoint**: `GET /api/payreqs`

**Headers**:

```
X-API-Key: YOUR_API_KEY
Accept: application/json
```

**Query Parameters**:

| Parameter     | Type    | Required | Description                                                         |
| ------------- | ------- | -------- | ------------------------------------------------------------------- |
| employee_id   | integer | No       | Filter by employee/user ID                                          |
| project       | string  | No       | Filter by project code                                              |
| department_id | integer | No       | Filter by department ID                                             |
| status        | string  | No       | Filter by status (draft, submitted, approved, rejected, paid, etc.) |
| type          | string  | No       | Filter by type (advance, reimburse, other)                          |
| date_from     | date    | No       | Filter from date (YYYY-MM-DD)                                       |
| date_to       | date    | No       | Filter to date (YYYY-MM-DD)                                         |
| amount_from   | number  | No       | Filter minimum amount                                               |
| amount_to     | number  | No       | Filter maximum amount                                               |
| per_page      | integer | No       | Results per page (default: 15)                                      |

**Response (200 OK)**:

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nomor": "PR/000H/2025/001",
            "user_id": 123,
            "type": "advance",
            "amount": "5000000.00",
            "remarks": "Travel expenses for Jakarta trip",
            "status": "submitted",
            "project": "000H",
            "department_id": 5,
            "created_at": "2025-10-27T10:30:00.000000Z",
            "requestor": {
                "id": 123,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "department": {
                "id": 5,
                "department_name": "Finance"
            },
            "rab": {
                "id": 10,
                "rab_no": "RAB/2025/001"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 73,
        "from": 1,
        "to": 15
    }
}
```

### 2. Get Payment Request Details

Retrieve detailed information about a specific payment request with formatted, user-friendly field values.

**Endpoint**: `GET /api/payreqs/{id}`

**Headers**:

```
X-API-Key: YOUR_API_KEY
Accept: application/json
```

**Response (200 OK)**:

```json
{
    "success": true,
    "data": {
        "id": 1,
        "nomor": "PR/000H/2025/001",
        "user_id": 123,
        "user_name": "John Doe",
        "user_email": "john@example.com",
        "type": "advance",
        "amount": "5000000.00",
        "remarks": "Travel expenses for Jakarta business trip",
        "status": "submitted",
        "project": "000H",
        "department_id": 5,
        "department_name": "Finance Department",
        "rab_id": 10,
        "rab_code": "RAB/2025/001",
        "rab_description": "Annual operational budget",
        "submit_at": "27-Oct-2025 10:35",
        "created_at": "27-Oct-2025 10:30",
        "updated_at": "27-Oct-2025 10:35",
        "approved_at": null,
        "canceled_at": null,
        "requestor": {
            "id": 123,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "department": {
            "id": 5,
            "department_name": "Finance Department"
        },
        "rab": {
            "id": 10,
            "rab_no": "RAB/2025/001",
            "description": "Annual operational budget"
        },
        "approval_plans": [
            {
                "id": 1,
                "document_type": "payreq",
                "approver_id": 45,
                "approver_name": "Manager Name",
                "approver_email": "manager@example.com",
                "status": 0,
                "approver": {
                    "id": 45,
                    "name": "Manager Name",
                    "email": "manager@example.com"
                }
            }
        ]
    }
}
```

**Response Enhancements**:

-   **user_name**: Employee/requestor full name (added for convenience)
-   **user_email**: Employee/requestor email address
-   **department_name**: Department name instead of just ID
-   **rab_code**: RAB/Budget code (rab_no or nomor)
-   **rab_description**: RAB/Budget description
-   **approver_name**: Approver full name in each approval plan
-   **approver_email**: Approver email address
-   **Date Formatting**: All dates formatted as `dd-mmm-yyyy hh:mm` (e.g., "27-Oct-2025 10:35")
    -   submit_at
    -   created_at
    -   updated_at
    -   approved_at
    -   canceled_at

**Response (404 Not Found)**:

```json
{
    "success": false,
    "message": "Payment request not found"
}
```

### 3. Create Advance Payment Request

Create a new advance payment request with optional immediate submission.

**Endpoint**: `POST /api/payreqs/advance`

**Headers**:

```
X-API-Key: YOUR_API_KEY
Content-Type: application/json
Accept: application/json
```

**Request Body**:

```json
{
    "employee_id": 123,
    "remarks": "Travel expenses for Jakarta business trip",
    "amount": 5000000,
    "rab_id": 10,
    "submit": true
}
```

**Request Fields**:

| Field       | Type    | Required | Description                                                         |
| ----------- | ------- | -------- | ------------------------------------------------------------------- |
| employee_id | integer | Yes      | User/employee ID making the request                                 |
| remarks     | string  | Yes      | Purpose/description (max 1000 chars)                                |
| amount      | number  | Yes      | Request amount (min: 0)                                             |
| rab_id      | integer | No\*     | Budget (RAB) ID (\*Required for projects 000H/APS when submit=true) |
| submit      | boolean | No       | true = submit for approval, false = save as draft (default: false)  |

**Response (201 Created)**:

```json
{
    "success": true,
    "message": "Payment request created successfully",
    "data": {
        "payreq": {
            "id": 1,
            "nomor": "PR/000H/2025/001",
            "user_id": 123,
            "type": "advance",
            "amount": "5000000.00",
            "remarks": "Travel expenses for Jakarta business trip",
            "status": "submitted",
            "project": "000H",
            "department_id": 5,
            "rab_id": 10,
            "created_at": "2025-10-27T10:30:00.000000Z",
            "requestor": {...},
            "department": {...},
            "rab": {...}
        },
        "approval_status": "submitted",
        "approvers_count": 3
    }
}
```

**Response (422 Validation Error - RAB Required)**:

```json
{
    "success": false,
    "message": "RAB is required for projects 000H and APS when submitting"
}
```

**Response (422 Validation Error - No Approval Plan)**:

```json
{
    "success": false,
    "message": "No approval plan found for this project/department. Payment request saved as draft.",
    "data": {
        "payreq": {...},
        "approval_status": "draft",
        "approvers_count": 0
    }
}
```

### 4. Create Reimburse Payment Request

Create a new reimburse payment request with expense details.

**Endpoint**: `POST /api/payreqs/reimburse`

**Headers**:

```
X-API-Key: YOUR_API_KEY
Content-Type: application/json
Accept: application/json
```

**Request Body**:

```json
{
    "employee_id": 123,
    "remarks": "Office supplies and fuel expenses",
    "rab_id": 10,
    "submit": true,
    "details": [
        {
            "description": "Printer paper and ink",
            "amount": 500000,
            "qty": 2,
            "uom": "pcs"
        },
        {
            "description": "Fuel for company vehicle",
            "amount": 300000,
            "unit_no": "TRK-001",
            "nopol": "B 1234 XYZ",
            "type": "fuel",
            "qty": 50,
            "uom": "liter",
            "km_position": 12500
        }
    ]
}
```

**Request Fields**:

| Field                 | Type    | Required | Description                                                |
| --------------------- | ------- | -------- | ---------------------------------------------------------- |
| employee_id           | integer | Yes      | User/employee ID                                           |
| remarks               | string  | Yes      | Purpose/description (max 1000 chars)                       |
| rab_id                | integer | No\*     | Budget (RAB) ID (\*Required for 000H/APS when submit=true) |
| submit                | boolean | No       | Submit for approval (default: false)                       |
| details               | array   | Yes      | Array of expense items (min: 1)                            |
| details[].description | string  | Yes      | Item description (max 500 chars)                           |
| details[].amount      | number  | Yes      | Item amount (min: 0)                                       |
| details[].unit_no     | string  | No       | Unit/equipment number (max 50 chars)                       |
| details[].nopol       | string  | No       | Vehicle license plate (max 50 chars)                       |
| details[].type        | string  | No       | Expense type (max 50 chars)                                |
| details[].qty         | number  | No       | Quantity (min: 0)                                          |
| details[].uom         | string  | No       | Unit of measure (max 20 chars)                             |
| details[].km_position | number  | No       | Kilometer position for vehicles (min: 0)                   |

**Response (201 Created)**:

```json
{
    "success": true,
    "message": "Reimburse payment request created successfully",
    "data": {
        "payreq": {
            "id": 2,
            "nomor": "PR/000H/2025/002",
            "type": "reimburse",
            "amount": "800000.00",
            "status": "submitted",
            ...
        },
        "realization": {
            "id": 1,
            "payreq_id": 2,
            "nomor": "RZ/000H/2025/001",
            "status": "reimburse-submitted",
            "realization_details": [
                {
                    "id": 1,
                    "description": "Printer paper and ink",
                    "amount": "500000.00",
                    ...
                },
                {
                    "id": 2,
                    "description": "Fuel for company vehicle",
                    "amount": "300000.00",
                    ...
                }
            ]
        },
        "approval_status": "submitted",
        "approvers_count": 3
    }
}
```

### 5. Cancel Draft Payment Request

Cancel a payment request that is still in draft status.

**Endpoint**: `POST /api/payreqs/{id}/cancel`

**Headers**:

```
X-API-Key: YOUR_API_KEY
Accept: application/json
```

**Response (200 OK)**:

```json
{
    "success": true,
    "message": "Payment request cancelled successfully"
}
```

**Response (422 Validation Error)**:

```json
{
    "success": false,
    "message": "Only draft payment requests can be cancelled. Current status: submitted"
}
```

### 6. Get Available RAB/Budget List

Retrieve active RAB (Budget) data available for a specific employee. This includes project-level, department-level, and user-level budgets.

**Endpoint**: `GET /api/payreqs/rabs`

**Headers**:

```
X-API-Key: YOUR_API_KEY
Accept: application/json
```

**Query Parameters**:

| Parameter   | Type    | Required | Description                                   |
| ----------- | ------- | -------- | --------------------------------------------- |
| employee_id | integer | Yes      | User/employee ID to get available budgets for |

**Response (200 OK)**:

```json
{
    "success": true,
    "data": [
        {
            "id": 10,
            "nomor": "ANG/000H/2025/001",
            "rab_no": "RAB/2025/001",
            "description": "Annual operational budget for Finance Department",
            "project": "000H",
            "rab_project": "000H",
            "department_id": 5,
            "usage": "department",
            "amount": "100000000.00",
            "balance": "75000000.00",
            "status": "approved",
            "date": "2025-01-01",
            "periode_anggaran": "2025-12-31",
            "periode_ofr": null,
            "created_by": 45,
            "created_by_user": {
                "id": 45,
                "name": "Finance Manager"
            }
        },
        {
            "id": 15,
            "nomor": "ANG/000H/2025/002",
            "rab_no": "RAB/2025/002",
            "description": "IT Infrastructure upgrade project",
            "project": "000H",
            "rab_project": "000H",
            "department_id": 8,
            "usage": "project",
            "amount": "50000000.00",
            "balance": "50000000.00",
            "status": "approved",
            "date": "2025-02-15",
            "periode_anggaran": "2025-12-31",
            "periode_ofr": "2025-03-01",
            "created_by": 12,
            "created_by_user": {
                "id": 12,
                "name": "IT Director"
            }
        }
    ],
    "summary": {
        "total": 15,
        "project_rabs": 8,
        "department_rabs": 5,
        "user_rabs": 2
    }
}
```

**Response (422 Validation Error)**:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "employee_id": ["The employee id field is required."]
    }
}
```

**RAB Usage Types**:

-   **project**: Budget available to all employees in the project
-   **department**: Budget available to all employees in the department
-   **user**: Budget created by and available to the specific employee

**RAB Status**:

-   Only RABs with status `approved` or `close` are returned
-   All returned RABs have `is_active = true`

**Use Case**: External applications can use this endpoint to display available budgets when creating payment requests, helping users select the appropriate budget for their request.

## Business Rules

### RAB (Budget) Validation

For projects **000H** and **APS**:

-   RAB is **required** when `submit=true`
-   Validation error returned if RAB missing during submission
-   RAB is optional for draft creation (`submit=false`)

For other projects:

-   RAB is optional

### Approval Workflow

1. **Draft Mode** (`submit=false`):

    - Payment request created with `status=draft`
    - Can be edited and deleted
    - No approval plan created
    - No document number assigned yet

2. **Submit Mode** (`submit=true`):
    - System checks for approval plan (based on project + department)
    - If approval plan exists:
        - Status changed to `submitted`
        - Official document number generated
        - Approval plans created for each approver
        - Cannot be edited or deleted
    - If no approval plan found:
        - Stays as `draft`
        - Error message returned
        - Can try submitting again later

### Project and Department Auto-Population

-   API automatically extracts `project` and `department_id` from employee record
-   External apps **do not** provide these fields
-   Ensures data consistency and security

### Amount Calculation

**Advance Payment Requests**:

-   Amount is provided directly in request

**Reimburse Payment Requests**:

-   Total amount calculated as sum of all detail items
-   Payreq amount updated automatically
-   External apps should calculate totals for validation

## Error Codes

| Status Code | Description                                 |
| ----------- | ------------------------------------------- |
| 200         | Success                                     |
| 201         | Created successfully                        |
| 401         | Unauthorized (invalid/missing API key)      |
| 404         | Resource not found                          |
| 422         | Validation error or business rule violation |
| 500         | Internal server error                       |

## Error Response Format

All error responses follow this structure:

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Error message for this field"]
    }
}
```

## Example Workflows

### Workflow 1: Create Draft, Review, Then Submit

**Step 1**: Create as draft

```bash
POST /api/payreqs/advance
{
    "employee_id": 123,
    "remarks": "Conference registration",
    "amount": 2000000,
    "submit": false
}
```

**Step 2**: Review in web interface (users can edit if needed)

**Step 3**: Submit via web interface OR API cancel + recreate with submit=true

### Workflow 2: Direct Submission

```bash
POST /api/payreqs/advance
{
    "employee_id": 123,
    "remarks": "Urgent travel advance",
    "amount": 3000000,
    "rab_id": 15,
    "submit": true
}
```

### Workflow 3: Reimburse with Multiple Items

```bash
POST /api/payreqs/reimburse
{
    "employee_id": 123,
    "remarks": "Monthly operational expenses",
    "rab_id": 15,
    "submit": true,
    "details": [
        {
            "description": "Office supplies",
            "amount": 450000,
            "qty": 1,
            "uom": "lot"
        },
        {
            "description": "Taxi fare",
            "amount": 150000
        },
        {
            "description": "Client lunch meeting",
            "amount": 400000
        }
    ]
}
```

## Testing with cURL

### Get List of Payment Requests

```bash
curl -X GET "http://localhost:8000/api/payreqs?status=submitted&per_page=10" \
  -H "X-API-Key: ak_your_api_key_here" \
  -H "Accept: application/json"
```

### Create Advance Payment Request (Draft)

```bash
curl -X POST "http://localhost:8000/api/payreqs/advance" \
  -H "X-API-Key: ak_your_api_key_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "employee_id": 1,
    "remarks": "Test advance payment request",
    "amount": 1000000,
    "submit": false
  }'
```

### Create Advance Payment Request (Submit)

```bash
curl -X POST "http://localhost:8000/api/payreqs/advance" \
  -H "X-API-Key: ak_your_api_key_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "employee_id": 1,
    "remarks": "Business trip to Surabaya",
    "amount": 5000000,
    "rab_id": 10,
    "submit": true
  }'
```

### Create Reimburse Payment Request

```bash
curl -X POST "http://localhost:8000/api/payreqs/reimburse" \
  -H "X-API-Key: ak_your_api_key_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "employee_id": 1,
    "remarks": "Office expenses reimbursement",
    "submit": true,
    "details": [
      {
        "description": "Printer cartridge",
        "amount": 350000,
        "qty": 2,
        "uom": "pcs"
      },
      {
        "description": "Parking fees",
        "amount": 50000
      }
    ]
  }'
```

### Get Payment Request Details

```bash
curl -X GET "http://localhost:8000/api/payreqs/1" \
  -H "X-API-Key: ak_your_api_key_here" \
  -H "Accept: application/json"
```

### Cancel Draft Payment Request

```bash
curl -X POST "http://localhost:8000/api/payreqs/1/cancel" \
  -H "X-API-Key: ak_your_api_key_here" \
  -H "Accept: application/json"
```

### Get Available RAB/Budget List

```bash
curl -X GET "http://localhost:8000/api/payreqs/rabs?employee_id=1" \
  -H "X-API-Key: ak_your_api_key_here" \
  -H "Accept: application/json"
```

## Rate Limiting

Currently, no rate limiting is enforced. This may be added in future versions.

## Troubleshooting

### Common Issues and Solutions

#### 1. "Invalid or inactive API key" (401 Error)

**Possible Causes**:

-   API key is incorrect or contains extra spaces
-   API key has been deactivated by administrator
-   Header name is incorrect (must be exactly `X-API-Key`)

**Solutions**:

-   Verify the API key is correct (including the `ak_` prefix)
-   Check with administrator if key is active
-   Ensure header name is `X-API-Key` (case-sensitive)
-   Remove any trailing spaces or line breaks from the key

#### 2. "RAB is required for projects 000H and APS when submitting" (422 Error)

**Cause**: Employee belongs to project 000H or APS and you're trying to submit without RAB

**Solution**:

-   Use the `/api/payreqs/rabs?employee_id=X` endpoint to get available RABs
-   Include `rab_id` in your request when `submit=true`
-   OR set `submit=false` to create as draft without RAB

#### 3. "No approval plan found" (422 Error with draft payreq returned)

**Cause**: No approval workflow is configured for the employee's project/department

**Solution**:

-   Contact administrator to set up approval stages
-   Payreq is saved as draft - can be submitted later via web interface
-   Check the returned `payreq` object in the response data

#### 4. "Validation failed" with field errors (422 Error)

**Cause**: Required fields are missing or invalid

**Solution**:

-   Check the `errors` object in the response for specific field issues
-   Ensure all required fields are provided:
    -   Advance: `employee_id`, `remarks`, `amount`
    -   Reimburse: `employee_id`, `remarks`, `details` (array with description and amount)
-   Verify data types match (numbers for amounts, strings for remarks, etc.)

#### 5. Empty or missing data in response

**Cause**: Related records (department, RAB, approver) may not exist

**Solution**:

-   Check if the employee has valid `department_id` set
-   Verify `rab_id` exists in the `anggarans` table
-   Ensure approval plans are configured for the project/department

### Debugging Tips

1. **Check Response Headers**: Look for error details in the response body
2. **Verify Employee Data**: Ensure the employee exists and has valid project/department
3. **Test with Postman**: Use Postman or similar tools for easier debugging
4. **Check Logs**: Server logs contain detailed error information
5. **Start Simple**: Test with minimal required fields first, then add optional fields

### Getting Help

If you encounter issues not covered here:

1. Check the full error message in the response
2. Review the endpoint documentation for required fields
3. Contact your system administrator
4. Provide the full error response when requesting support

## Best Practices

1. **Store API Keys Securely**: Never commit API keys to version control
2. **Handle Errors Gracefully**: Always check `success` field in responses
3. **Validate Before Sending**: Pre-validate data on client side to reduce errors
4. **Log API Calls**: Maintain audit logs of API interactions
5. **Test in Development**: Use development environment before production
6. **Handle Timeouts**: Implement timeout handling for network issues
7. **Use HTTPS**: Always use HTTPS in production environments

## Support and Contact

For API key generation, technical support, or questions:

-   Contact: IT Administrator
-   Documentation: `/docs/PAYREQ_API_DOCUMENTATION.md`
-   System Version: 1.1.1
-   Last Updated: October 28, 2025

## Changelog

### Version 1.1.1 (2025-10-28) - Bug Fix Release

-   **FIXED**: Approval plans relationship error in Payreq model
    -   Corrected foreign key from default to `document_id`
    -   Added `document_type = 'payreq'` condition
    -   Resolved "Unknown column 'approval_plans.payreq_id'" error
-   **FIXED**: Date formatting error when dates are stored as strings
    -   Added Carbon instance checking before calling `format()`
    -   Gracefully handles both Carbon objects and string dates
    -   Prevents "Call to a member function format() on string" error
-   **FIXED**: RAB/Anggaran relationship references
    -   Changed from `rab` to `anggaran` throughout API controller
    -   Ensures correct data loading from `anggarans` table
-   **IMPROVED**: Response now includes `rab` key for backward compatibility

### Version 1.1.0 (2025-10-28)

-   **NEW**: Get Available RAB/Budget List endpoint (`GET /api/payreqs/rabs`)
    -   Retrieve active budgets for employee (project, department, user levels)
    -   Filter by employee_id
    -   Returns summary statistics (total, by usage type)
-   **ENHANCED**: Get Payment Request Details response formatting
    -   Added `user_name` and `user_email` fields
    -   Added `department_name` field
    -   Added `rab_code` and `rab_description` fields
    -   Added `approver_name` and `approver_email` in approval plans
    -   All dates formatted as `dd-mmm-yyyy hh:mm` (e.g., "27-Oct-2025 10:35")
-   **IMPROVED**: API documentation with endpoints summary table
-   **IMPROVED**: Enhanced field descriptions and response examples

### Version 1.0.0 (2025-10-27)

-   Initial release
-   Support for advance and reimburse payment requests
-   Custom API key authentication
-   Draft and submit workflows
-   Comprehensive validation and error handling
-   5 core endpoints: list, show, create advance, create reimburse, cancel
