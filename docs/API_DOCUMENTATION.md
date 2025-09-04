# DDS Invoice API Documentation

## **Overview**

The DDS Invoice API provides secure access to invoice data for external applications. This API allows you to retrieve invoices by department location code with comprehensive filtering and pagination capabilities.

## **Base URL**

```
https://your-domain.com/api/v1
```

## **Authentication**

All API requests require an API key to be included in the request headers.

### **API Key Header**

```
X-API-Key: YOUR_DDS_API_KEY
```

**Note**: Replace `YOUR_DDS_API_KEY` with the actual API key provided by your system administrator.

## **Rate Limiting**

The API implements rate limiting to prevent abuse:

-   **Hourly Limit**: 100 requests per hour per API key
-   **Minute Limit**: 20 requests per minute per API key
-   **Daily Limit**: 1000 requests per day per API key

### **Rate Limit Headers**

Response headers include rate limit information:

```
X-RateLimit-Limit-Hourly: 100
X-RateLimit-Remaining-Hourly: 99
X-RateLimit-Reset-Hourly: 1737457800
X-RateLimit-Limit-Minute: 20
X-RateLimit-Remaining-Minute: 19
X-RateLimit-Reset-Minute: 1737457860
```

### **Rate Limit Exceeded Response**

When rate limits are exceeded:

```json
{
    "success": false,
    "error": "Rate limit exceeded",
    "message": "Hourly rate limit exceeded. Please try again later.",
    "retry_after": 3600
}
```

## **Endpoints**

### **1. Health Check**

Check API status without authentication.

**Endpoint**: `GET /api/health`

**Headers**: None required

**Response**:

```json
{
    "status": "healthy",
    "timestamp": "2025-01-21T10:30:00Z",
    "version": "1.0.0"
}
```

### **2. Get Available Departments**

Retrieve list of available departments with their location codes.

**Endpoint**: `GET /api/v1/departments`

**Headers**:

```
X-API-Key: YOUR_DDS_API_KEY
Accept: application/json
```

**Response**:

```json
{
    "success": true,
    "data": {
        "departments": [
            {
                "id": 1,
                "name": "Accounting",
                "location_code": "000HACC",
                "akronim": "ACC"
            },
            {
                "id": 2,
                "name": "Finance",
                "location_code": "001HFIN",
                "akronim": "FIN"
            }
        ]
    },
    "meta": {
        "total_departments": 2,
        "requested_at": "2025-01-21T10:30:00Z"
    }
}
```

### **3. Get Invoices by Department**

Retrieve invoices for a specific department by location code.

**Endpoint**: `GET /api/v1/departments/{location_code}/invoices`

**Parameters**:

-   `{location_code}` (path): Department location code (e.g., "000HACC", "001HFIN")

**Query Parameters**:

-   `status` (optional): Filter by invoice status
    -   Values: `open`, `closed`, `overdue`, `cancelled`
-   `date_from` (optional): Filter invoices from date (format: YYYY-MM-DD)
-   `date_to` (optional): Filter invoices to date (format: YYYY-MM-DD)

**Headers**:

```
X-API-Key: YOUR_DDS_API_KEY
Accept: application/json
```

**Example Request**:

```bash
curl -X GET "https://your-domain.com/api/v1/departments/000HACC/invoices?status=open" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Response**:

```json
{
    "success": true,
    "data": {
        "invoices": [
            {
                "id": 1,
                "invoice_number": "INV-001",
                "faktur_no": "FK-001",
                "invoice_date": "2025-01-15",
                "receive_date": "2025-01-20",
                "supplier_name": "Supplier ABC",
                "supplier_sap_code": "SUP001",
                "po_no": "PO-001",
                "receive_project": "PRJ001",
                "invoice_project": "PRJ001",
                "payment_project": "PRJ001",
                "currency": "IDR",
                "amount": 1000000.0,
                "invoice_type": "regular",
                "payment_date": "2025-02-15",
                "paid_by": "John Doe",
                "remarks": "Sample invoice",
                "status": "open",
                "sap_doc": "DOC001",
                "cur_loc": "000HACC",
                "department_location_code": "000HACC",
                "department_name": "Accounting",
                "additional_documents": [
                    {
                        "document_no": "DOC-001",
                        "document_date": "2025-01-15",
                        "document_type": "supporting"
                    }
                ],
                "distribution": {
                    "id": 1,
                    "distribution_number": "DIS-001",
                    "type": "Internal",
                    "origin_department": "Accounting",
                    "destination_department": "Finance",
                    "status": "sent",
                    "created_by": "John Doe",
                    "created_at": "2025-01-15 10:00:00",
                    "sender_verified_at": "2025-01-15 10:05:00",
                    "sent_at": "2025-01-15 10:10:00",
                    "received_at": null,
                    "receiver_verified_at": null,
                    "has_discrepancies": false,
                    "notes": "Regular monthly distribution"
                }
            }
        ]
    },
    "meta": {
        "department_location": "000HACC",
        "department_name": "Accounting",
        "total_invoices": 1,
        "requested_at": "2025-01-21T10:30:00Z",
        "filters_applied": {
            "status": "open"
        }
    }
}
```

### **4. Get Wait-Payment Invoices by Department**

Retrieve invoices that are waiting for payment (payment_date IS NULL) for a specific department.

**Endpoint**: `GET /api/v1/departments/{location_code}/wait-payment-invoices`

**Parameters**:

-   `{location_code}` (path): Department location code (e.g., "000HACC", "001HFIN")

**Query Parameters**:

-   `status` (optional): Filter by invoice status
    -   Values: `open`, `closed`, `overdue`, `cancelled`
-   `date_from` (optional): Filter invoices from date (format: YYYY-MM-DD)
-   `date_to` (optional): Filter invoices to date (format: YYYY-MM-DD)
-   `project` (optional): Filter by project code (searches invoice_project, payment_project, receive_project)
-   `supplier` (optional): Filter by supplier name or SAP code

**Headers**:

```
X-API-Key: YOUR_DDS_API_KEY
Accept: application/json
```

**Example Request**:

```bash
curl -X GET "https://your-domain.com/api/v1/departments/000HACC/wait-payment-invoices?status=open&project=PRJ001" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Response**: Same structure as regular invoices endpoint, but with `payment_status: "waiting_payment"` in meta.

### **5. Get Paid Invoices by Department**

Retrieve invoices that have been paid (payment_date IS NOT NULL) for a specific department.

**Endpoint**: `GET /api/v1/departments/{location_code}/paid-invoices`

**Parameters**:

-   `{location_code}` (path): Department location code (e.g., "000HACC", "001HFIN")

**Query Parameters**:

-   `status` (optional): Filter by invoice status
    -   Values: `open`, `closed`, `overdue`, `cancelled`
-   `date_from` (optional): Filter invoices from date (format: YYYY-MM-DD)
-   `date_to` (optional): Filter invoices to date (format: YYYY-MM-DD)
-   `project` (optional): Filter by project code (searches invoice_project, payment_project, receive_project)
-   `supplier` (optional): Filter by supplier name or SAP code

**Headers**:

```
X-API-Key: YOUR_DDS_API_KEY
Accept: application/json
```

**Example Request**:

```bash
curl -X GET "https://your-domain.com/api/v1/departments/000HACC/paid-invoices?date_from=2025-01-01&date_to=2025-01-31" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**Response**: Same structure as regular invoices endpoint, but with `payment_status: "paid"` in meta.

### **6. Update Invoice Payment**

Update payment information for a specific invoice.

**Endpoint**: `PUT /api/v1/invoices/{invoice_id}/payment`

**Parameters**:

-   `{invoice_id}` (path): Invoice ID (integer)

**Request Body**:

```json
{
    "payment_date": "2025-01-27",
    "status": "closed",
    "remarks": "Payment completed via bank transfer",
    "payment_project": "PRJ001",
    "sap_doc": "SAP-2025-001"
}
```

**Required Fields**:

-   `payment_date`: Date when payment was made (format: YYYY-MM-DD)

**Optional Fields**:

-   `status`: Invoice status (`open`, `closed`, `overdue`, `cancelled`)
-   `remarks`: Additional notes about the payment
-   `payment_project`: Project code for payment
-   `sap_doc`: SAP document reference

**Headers**:

```
X-API-Key: YOUR_DDS_API_KEY
Accept: application/json
Content-Type: application/json
```

**Example Request**:

```bash
curl -X PUT "https://your-domain.com/api/v1/invoices/1/payment" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_date": "2025-01-27",
    "status": "closed",
    "remarks": "Payment completed via bank transfer"
  }'
```

**PowerShell**:

```powershell
$body = @{
    payment_date = "2025-01-27"
    status = "closed"
    remarks = "Payment completed via bank transfer"
} | ConvertTo-Json

Invoke-RestMethod -Uri "https://your-domain.com/api/v1/invoices/1/payment" -Method PUT -Headers @{"X-API-Key"="YOUR_DDS_API_KEY"; "Accept"="application/json"; "Content-Type"="application/json"} -Body $body
```

**Response**:

```json
{
    "success": true,
    "message": "Invoice payment updated successfully",
    "data": {
        "id": 1,
        "invoice_number": "INV-001",
        "faktur_no": "FK-001",
        "invoice_date": "2025-01-15",
        "receive_date": "2025-01-20",
        "supplier_name": "Supplier ABC",
        "supplier_sap_code": "SUP001",
        "po_no": "PO-001",
        "receive_project": "PRJ001",
        "invoice_project": "PRJ001",
        "payment_project": "PRJ001",
        "currency": "IDR",
        "amount": 1000000.0,
        "invoice_type": "regular",
        "payment_date": "2025-01-27",
        "paid_by": "John Doe",
        "remarks": "Payment completed via bank transfer",
        "status": "closed",
        "sap_doc": "SAP-2025-001",
        "cur_loc": "000HACC",
        "department_location_code": "000HACC",
        "department_name": "Accounting"
    },
    "meta": {
        "updated_at": "2025-01-27T10:30:00Z",
        "payment_status": "paid"
    }
}
```

### **7. Get Invoice by Document Number**

Retrieve invoice information by searching for a document number (invoice number or additional document number).

**Endpoint**: `GET /api/v1/documents/{document_number}`

**Parameters**:

-   `document_number` (path, required): Document number to search for (can be invoice number or additional document number)

**Headers**:

```
X-API-Key: YOUR_DDS_API_KEY
Accept: application/json
```

**Example Request**:

```bash
curl -X GET "https://your-domain.com/api/v1/documents/INV001" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

**PowerShell**:

```powershell
Invoke-RestMethod -Uri "https://your-domain.com/api/v1/documents/INV001" -Method GET -Headers @{"X-API-Key"="YOUR_DDS_API_KEY"; "Accept"="application/json"}
```

**Response**:

```json
{
    "success": true,
    "message": "Document found successfully",
    "data": {
        "id": 1,
        "invoice_number": "INV001",
        "faktur_no": null,
        "invoice_date": "2025-08-20",
        "receive_date": "2025-08-22",
        "supplier_name": "ABADI TOWER",
        "supplier_sap_code": "VABTOIDR01",
        "po_no": "PO001",
        "receive_project": "000H",
        "invoice_project": "017C",
        "payment_project": "001H",
        "currency": "IDR",
        "amount": "2350000.00",
        "invoice_type": "Item",
        "payment_date": "2025-08-27",
        "paid_by": "John Doe",
        "remarks": "Payment completed via bank transfer",
        "status": "closed",
        "sap_doc": null,
        "cur_loc": "000HACC",
        "department_location_code": "000HACC",
        "department_name": "Accounting",
        "additional_documents": [
            {
                "id": 1,
                "document_no": "DOC001",
                "document_date": "2025-08-25",
                "document_type": "Supporting Document"
            }
        ],
        "distribution": {
            "id": 1,
            "distribution_number": "DIST001",
            "type": "Internal",
            "origin_department": "Accounting",
            "destination_department": "Finance",
            "status": "completed",
            "created_by": "John Doe",
            "created_at": "2025-08-25 10:30:00",
            "sender_verified_at": "2025-08-25 10:35:00",
            "sent_at": "2025-08-25 10:40:00",
            "received_at": "2025-08-25 14:20:00",
            "receiver_verified_at": "2025-08-25 14:25:00",
            "has_discrepancies": false,
            "notes": null
        }
    },
    "meta": {
        "document_number_searched": "INV001",
        "found_by": "invoice_number",
        "requested_at": "2025-01-27T10:30:00Z"
    }
}
```

**Error Response (Document Not Found)**:

```json
{
    "success": false,
    "error": "Not found",
    "message": "Document not found"
}
```

**Error Response (Empty Document Number)**:

```json
{
    "success": false,
    "error": "Bad request",
    "message": "Document number is required"
}
```

## **Available Department Location Codes**

Based on your DepartmentSeeder, the following location codes are available:

| Department                  | Location Code | Akronim |
| --------------------------- | ------------- | ------- |
| Accounting                  | 000HACC       | ACC     |
| Cashier HO                  | 000HCASHO     | CASHO   |
| Logistic                    | 000HLOG       | LOG     |
| Finance                     | 001HFIN       | FIN     |
| Plant                       | 000HPLANT     | PLANT   |
| Procurement                 | 000HPROC      | PROC    |
| Operation & Production      | 000HOPR       | OPR     |
| Safety                      | 000HSHE       | SHE     |
| Information Technology      | 000HIT        | IT      |
| Research & Development      | 000HRND       | RND     |
| Warehouse 017C              | 017CWH        | WH017   |
| Warehouse 021C              | 021CWH        | WH021   |
| Warehouse 022C              | 022CWH        | WH022   |
| Warehouse 023C              | 023CWH        | WH023   |
| Warehouse 025C              | 025CWH        | WH025   |
| Design & Construction       | 000HDNC       | DNC     |
| Relationship & Coordination | 000HRNC       | RNC     |
| APS - Arka Project Support  | 000HAPS       | APS     |
| Corporate Secretary         | 001HCORSEC    | CORSEC  |
| Internal Audit & System     | 000HIAS       | IAS     |
| Management / BOD            | 000HBOD       | BOD     |
| Human Capital & Support     | 000HHCS       | HCS     |

## **Data Fields**

### **Invoice Fields**

| Field                      | Type    | Description                                     |
| -------------------------- | ------- | ----------------------------------------------- |
| `invoice_number`           | string  | Internal invoice number                         |
| `faktur_no`                | string  | Official faktur number                          |
| `invoice_date`             | date    | Date when invoice was issued                    |
| `receive_date`             | date    | Date when invoice was received                  |
| `supplier_name`            | string  | Name of the supplier                            |
| `supplier_sap_code`        | string  | Supplier's SAP code                             |
| `po_no`                    | string  | Purchase order number                           |
| `receive_project`          | string  | Project code for receiving                      |
| `invoice_project`          | string  | Project code for invoice                        |
| `payment_project`          | string  | Project code for payment                        |
| `currency`                 | string  | Currency code (e.g., IDR, USD)                  |
| `amount`                   | decimal | Invoice amount                                  |
| `invoice_type`             | string  | Type of invoice                                 |
| `payment_date`             | date    | Date when payment was made                      |
| `paid_by`                  | string  | Name of the user who made the payment           |
| `remarks`                  | string  | Additional notes or comments                    |
| `status`                   | string  | Current invoice status                          |
| `sap_doc`                  | string  | SAP document reference                          |
| `cur_loc`                  | string  | Current location code of the invoice            |
| `department_location_code` | string  | Department location code (same as cur_loc)      |
| `department_name`          | string  | Name of the department where invoice is located |

### **Additional Document Fields**

| Field           | Type   | Description      |
| --------------- | ------ | ---------------- |
| `document_no`   | string | Document number  |
| `document_date` | date   | Document date    |
| `document_type` | string | Type of document |

### **Distribution Fields**

| Field                    | Type     | Description                                   |
| ------------------------ | -------- | --------------------------------------------- |
| `id`                     | integer  | Distribution ID                               |
| `distribution_number`    | string   | Unique distribution number                    |
| `type`                   | string   | Distribution type name                        |
| `origin_department`      | string   | Name of the department sending documents      |
| `destination_department` | string   | Name of the department receiving documents    |
| `status`                 | string   | Current distribution status                   |
| `created_by`             | string   | Name of the user who created the distribution |
| `created_at`             | datetime | When the distribution was created             |
| `sender_verified_at`     | datetime | When sender verification was completed        |
| `sent_at`                | datetime | When documents were sent                      |
| `received_at`            | datetime | When documents were received                  |
| `receiver_verified_at`   | datetime | When receiver verification was completed      |
| `has_discrepancies`      | boolean  | Whether there are discrepancies in documents  |
| `notes`                  | string   | Additional notes about the distribution       |

**Note**: Only the latest distribution where the `destination_department` matches the requested department is included in the response. This shows where the invoice was last sent to or is currently located.

## **Error Responses**

### **400 Bad Request**

**Empty Location Code:**

```json
{
    "success": false,
    "error": "Invalid location code",
    "message": "Location code cannot be empty"
}
```

**Invalid Location Code:**

```json
{
    "success": false,
    "error": "Invalid location code",
    "message": "Department with the specified location code not found"
}
```

**Invalid Query Parameters:**

```json
{
    "success": false,
    "error": "Validation failed",
    "message": "Invalid query parameters",
    "errors": {
        "status": ["The selected status is invalid."],
        "date_from": [
            "The date from field is required when date to is present."
        ],
        "date_to": ["The date to must be a date after or equal to date from."]
    }
}
```

### **401 Unauthorized**

Missing or invalid API key:

```json
{
    "success": false,
    "error": "Unauthorized",
    "message": "Invalid or missing API key"
}
```

### **404 Not Found**

Department location code not found:

```json
{
    "success": false,
    "error": "Invalid location code",
    "message": "Department with the specified location code not found"
}
```

### **429 Too Many Requests**

Rate limit exceeded:

```json
{
    "success": false,
    "error": "Rate limit exceeded",
    "message": "Minute rate limit exceeded. Please slow down your requests.",
    "retry_after": 60
}
```

### **500 Internal Server Error**

Server-side error:

```json
{
    "success": false,
    "error": "Internal server error",
    "message": "An error occurred while processing your request"
}
```

## **Usage Examples**

### **Example 1: Get All Open Invoices for Accounting Department**

```bash
curl -X GET "https://your-domain.com/api/v1/departments/000HACC/invoices?status=open" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

### **Example 2: Get Invoices for Finance Department with Date Range**

```bash
curl -X GET "https://your-domain.com/api/v1/departments/001HFIN/invoices?date_from=2025-01-01&date_to=2025-01-31" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

### **Example 3: Get All Invoices for Warehouse**

```bash
curl -X GET "https://your-domain.com/api/v1/departments/017CWH/invoices" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

### **Example 4: Get Overdue Invoices for IT Department**

```bash
curl -X GET "https://your-domain.com/api/v1/departments/000HIT/invoices?status=overdue" \
  -H "X-API-Key: YOUR_DDS_API_KEY" \
  -H "Accept: application/json"
```

## **Best Practices**

### **1. Data Handling**

-   All invoices are returned in a single response
-   Handle large datasets appropriately in your application
-   Consider implementing client-side pagination if needed

### **2. Error Handling**

-   Check `success` field in all responses
-   Handle different HTTP status codes appropriately
-   Implement retry logic for rate limit errors

### **3. Rate Limiting**

-   Monitor rate limit headers
-   Implement exponential backoff for retries
-   Cache responses when possible to reduce API calls

### **4. Data Processing**

-   Validate response data structure
-   Handle null values gracefully
-   Process additional documents as nested arrays

## **Security Considerations**

### **API Key Security**

-   Keep your API key secure and confidential
-   Never expose API keys in client-side code
-   Rotate API keys periodically
-   Monitor API usage for suspicious activity

### **Data Access**

-   API keys provide access to all department data
-   Implement appropriate access controls in your application
-   Log and monitor API usage
-   Respect data privacy and compliance requirements

## **Monitoring and Logging**

### **API Access Logs**

All API requests are logged with the following information:

-   IP address of the requesting client
-   User agent string
-   API endpoint accessed
-   Timestamp of the request
-   Success/failure status
-   Rate limit information

### **Log Monitoring**

Monitor logs for:

-   Unauthorized access attempts
-   Rate limit violations
-   High-volume usage patterns
-   Error patterns and system issues

## **Support and Contact**

For technical support or questions about the API:

1. **Check Logs**: Review Laravel logs for error details
2. **Verify Configuration**: Ensure API key and endpoint URLs are correct
3. **Test Endpoints**: Use the provided test script to verify functionality
4. **Contact Administrator**: Reach out to your system administrator for assistance

## **Changelog**

### **Version 1.0.0 (2025-01-21)**

-   Initial API release
-   Invoice retrieval by department
-   Department listing endpoint
-   Comprehensive filtering (no pagination)
-   Rate limiting and security features
-   Complete logging and monitoring

## **Rate Limits Summary**

| Limit Type | Requests | Time Window | Reset             |
| ---------- | -------- | ----------- | ----------------- |
| Hourly     | 100      | 1 hour      | Every hour        |
| Minute     | 20       | 1 minute    | Every minute      |
| Daily      | 1000     | 24 hours    | Daily at midnight |

## **Response Time Expectations**

-   **Simple Queries**: < 500ms
-   **Filtered Queries**: < 1000ms
-   **Large Result Sets**: < 2000ms
-   **Complex Filters**: < 3000ms

## **Data Freshness**

-   All data is retrieved in real-time from the database
-   No caching is implemented on the server side
-   External applications should implement appropriate caching strategies
-   Data reflects the current state at the time of the request

## **Complete Error Scenarios**

### **Authentication Errors (401 Unauthorized)**

| Scenario                   | Request                                                                  | Response                                                                               |
| -------------------------- | ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------- |
| **Missing API Key Header** | `GET /api/v1/departments/000HACC/invoices` (no headers)                  | `{"success": false, "error": "Unauthorized", "message": "Invalid or missing API key"}` |
| **Invalid API Key**        | `GET /api/v1/departments/000HACC/invoices` with `X-API-Key: INVALID_KEY` | `{"success": false, "error": "Unauthorized", "message": "Invalid or missing API key"}` |

### **Location Code Errors (400 Bad Request)**

| Scenario                  | Request                                         | Response                                                                                                                   |
| ------------------------- | ----------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| **Empty Location Code**   | `GET /api/v1/departments//invoices`             | `{"success": false, "error": "Invalid location code", "message": "Location code cannot be empty"}`                         |
| **Invalid Location Code** | `GET /api/v1/departments/INVALID_CODE/invoices` | `{"success": false, "error": "Invalid location code", "message": "Department with the specified location code not found"}` |

### **Validation Errors (400 Bad Request)**

| Scenario                | Request                                                                            | Response                                                                                                                                                                      |
| ----------------------- | ---------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Invalid Status**      | `GET /api/v1/departments/000HACC/invoices?status=invalid_status`                   | `{"success": false, "error": "Validation failed", "message": "Invalid query parameters", "errors": {"status": ["The selected status is invalid."]}}`                          |
| **Invalid Date Format** | `GET /api/v1/departments/000HACC/invoices?date_from=invalid-date`                  | `{"success": false, "error": "Validation failed", "message": "Invalid query parameters", "errors": {"date_from": ["The date from does not match the format Y-m-d."]}}`        |
| **Date Range Error**    | `GET /api/v1/departments/000HACC/invoices?date_from=2025-12-31&date_to=2025-01-01` | `{"success": false, "error": "Validation failed", "message": "Invalid query parameters", "errors": {"date_to": ["The date to must be a date after or equal to date from."]}}` |

### **Rate Limiting Errors (429 Too Many Requests)**

| Scenario                  | Request                           | Response                                                                                                                                          |
| ------------------------- | --------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Minute Limit Exceeded** | Multiple requests within 1 minute | `{"success": false, "error": "Rate limit exceeded", "message": "Minute rate limit exceeded. Please slow down your requests.", "retry_after": 60}` |
| **Hourly Limit Exceeded** | Multiple requests within 1 hour   | `{"success": false, "error": "Rate limit exceeded", "message": "Hourly rate limit exceeded. Please try again later.", "retry_after": 3600}`       |

### **Server Errors (500 Internal Server Error)**

| Scenario                       | Response                                                                                                             |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------- |
| **Database Connection Issues** | `{"success": false, "error": "Internal server error", "message": "An error occurred while processing your request"}` |
| **Unexpected Exceptions**      | `{"success": false, "error": "Internal server error", "message": "An error occurred while processing your request"}` |

## **Error Handling Best Practices**

### **1. Always Check Response Status**

```javascript
if (response.success === false) {
    // Handle error based on response.error and response.message
    console.error(`API Error: ${response.error} - ${response.message}`);
}
```

### **2. Handle HTTP Status Codes**

-   **400**: Client error - check request parameters
-   **401**: Authentication error - verify API key
-   **429**: Rate limit error - implement exponential backoff
-   **500**: Server error - retry later or contact support

### **3. Implement Retry Logic for Rate Limits**

```javascript
if (response.status === 429) {
    const retryAfter = response.data.retry_after || 60;
    setTimeout(() => {
        // Retry the request
    }, retryAfter * 1000);
}
```

### **4. Log All Errors for Debugging**

```javascript
console.error("API Request Failed:", {
    url: requestUrl,
    status: response.status,
    error: response.data.error,
    message: response.data.message,
});
```

---

**Last Updated**: 2025-01-21  
**API Version**: 1.0.0  
**Status**: Production Ready
