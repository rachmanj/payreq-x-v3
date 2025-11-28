**Purpose**: REST API reference for ARKFleet Equipment Fleet Management System  
**Last Updated**: 2025-10-30  
**API Version**: 1.0

# ARKFleet API Documentation

## Overview

ARKFleet provides a REST API for accessing equipment and project data. The API is designed for lightweight data access and integration with external systems or mobile applications.

### Base URL

```
http://your-domain.com/api
```

### Authentication

Currently, all equipment and project endpoints are **public** (no authentication required). The `/user` endpoint requires Laravel Sanctum authentication.

### Response Format

All responses are in JSON format with appropriate HTTP status codes.

---

## API Endpoints

### 1. Equipment List

Get a list of equipment with optional filtering.

#### Request

```http
GET /api/equipments
```

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `project_code` | string | No | Filter by project code (e.g., 000H, 017C) |
| `current_project_id` | integer | No | Filter by project ID (legacy, use project_code instead) |
| `status` | string | No | Filter by status: ACTIVE, IN-ACTIVE, SCRAP, SOLD (case-insensitive) |
| `unitstatus_id` | integer | No | Filter by unit status ID (legacy, use status instead) |
| `plant_group` | string | No | Filter by plant group name (e.g., Excavator, Dump Truck) |
| `plant_group_id` | integer | No | Filter by plant group ID (legacy, use plant_group instead) |

> **Recommended**: Use human-readable parameters (`project_code`, `status`, `plant_group`) for better API clarity. ID-based parameters are supported for backward compatibility.

#### Example Requests

```bash
# Get all equipment
GET /api/equipments

# Get equipment by project code (RECOMMENDED)
GET /api/equipments?project_code=000H

# Get ACTIVE equipment
GET /api/equipments?status=ACTIVE

# Get equipment by plant group name
GET /api/equipments?plant_group=Excavator

# Multiple filters with human-readable parameters
GET /api/equipments?project_code=017C&status=ACTIVE

# Get SCRAP equipment in specific project
GET /api/equipments?project_code=APS&status=SCRAP

# Legacy: Get equipment by project ID (still supported)
GET /api/equipments?current_project_id=5

# Legacy: Get RFU equipment by status ID (still supported)
GET /api/equipments?unitstatus_id=1&plant_group_id=3
```

#### Response

**Status Code**: `200 OK`

```json
{
  "count": 2,
  "data": [
    {
      "id": 123,
      "unit_no": "EX-001",
      "description": "Excavator PC200-8",
      "active_date": "2023-01-15",
      "nomor_polisi": "B 1234 XYZ",
      "serial_no": "SN-PC200-2023-001",
      "chasis_no": "CH-12345678",
      "engine_model": "SAA6D107E-1",
      "machine_no": "MN-87654321",
      "bahan_bakar": "Solar",
      "warna": "Yellow",
      "capacity": 1.2,
      "remarks": "Good condition, regular maintenance",
      "project_code": "PRJ-2023-01",
      "project_id": 5,
      "plant_group": "Excavator",
      "plant_group_id": 3,
      "model": "PC200-8",
      "model_id": 15,
      "unitstatus": "RFU",
      "unitstatus_id": 1,
      "asset_category": "Mayor",
      "asset_category_id": 1,
      "plant_type": "Digger",
      "plant_type_id": 2
    },
    {
      "id": 124,
      "unit_no": "DT-002",
      "description": "Dump Truck HD785",
      "active_date": "2023-02-20",
      "nomor_polisi": "B 5678 ABC",
      "serial_no": "SN-HD785-2023-002",
      "chasis_no": "CH-98765432",
      "engine_model": "SA12V140E-1",
      "machine_no": "MN-11223344",
      "bahan_bakar": "Solar",
      "warna": "Yellow",
      "capacity": 95.0,
      "remarks": "High productivity hauler",
      "project_code": "PRJ-2023-01",
      "project_id": 5,
      "plant_group": "Dump Truck",
      "plant_group_id": 7,
      "model": "HD785-7",
      "model_id": 32,
      "unitstatus": "RFU",
      "unitstatus_id": 1,
      "asset_category": "Mayor",
      "asset_category_id": 1,
      "plant_type": "Hauler",
      "plant_type_id": 1
    }
  ]
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `count` | integer | Total number of equipment matching filters |
| `data` | array | Array of equipment objects |

#### Equipment Object Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Equipment database ID |
| `unit_no` | string | Unique equipment unit number |
| `description` | string | Equipment description |
| `active_date` | date | Date when equipment became active |
| `nomor_polisi` | string | License plate number (for vehicles) |
| `serial_no` | string | Serial number |
| `chasis_no` | string | Chassis/frame number |
| `engine_model` | string | Engine model specification |
| `machine_no` | string | Machine/engine number |
| `bahan_bakar` | string | Fuel type (Solar/Pertalite/Premium) |
| `warna` | string | Color |
| `capacity` | decimal | Equipment capacity (tons/cubic meters) |
| `remarks` | text | Additional notes |
| `project_code` | string | Current project code (nullable) |
| `project_id` | integer | Current project ID (nullable) |
| `plant_group` | string | Equipment group name |
| `plant_group_id` | integer | Equipment group ID |
| `model` | string | Equipment model number |
| `model_id` | integer | Equipment model ID |
| `unitstatus` | string | Status name (RFU/RFM/BD/Standby) |
| `unitstatus_id` | integer | Status ID |
| `asset_category` | string | Asset category (Mayor/Minor) |
| `asset_category_id` | integer | Asset category ID |
| `plant_type` | string | Plant type (Digger/Hauler/Support) |
| `plant_type_id` | integer | Plant type ID |

---

### 2. Equipment Detail by Unit Number

Get detailed information about a specific equipment by its unit number.

#### Request

```http
GET /api/equipments/by-unit/{unit_no}
```

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `unit_no` | string | Yes | Equipment unit number (e.g., EX-001) |

#### Example Requests

```bash
# Get equipment EX-001
GET /api/equipments/by-unit/EX-001

# Get equipment DT-002
GET /api/equipments/by-unit/DT-002
```

#### Success Response

**Status Code**: `200 OK`

```json
{
  "id": 123,
  "unit_no": "EX-001",
  "description": "Excavator PC200-8",
  "active_date": "2023-01-15",
  "nomor_polisi": "B 1234 XYZ",
  "serial_no": "SN-PC200-2023-001",
  "chasis_no": "CH-12345678",
  "engine_model": "SAA6D107E-1",
  "machine_no": "MN-87654321",
  "bahan_bakar": "Solar",
  "warna": "Yellow",
  "capacity": 1.2,
  "unit_pic": "John Doe",
  "assign_to": 15,
  "remarks": "Good condition, regular maintenance",
  "project": {
    "id": 5,
    "project_code": "PRJ-2023-01",
    "bowheer": "PT Construction Company",
    "location": "Jakarta"
  },
  "plant_group": {
    "id": 3,
    "name": "Excavator"
  },
  "model": {
    "id": 15,
    "model_no": "PC200-8"
  },
  "unitstatus": {
    "id": 1,
    "name": "RFU"
  },
  "asset_category": {
    "id": 1,
    "name": "Mayor"
  },
  "plant_type": {
    "id": 2,
    "name": "Digger"
  },
  "created_at": "2023-01-10T08:30:00.000000Z",
  "updated_at": "2023-10-28T14:22:15.000000Z"
}
```

#### Error Response (Not Found)

**Status Code**: `404 Not Found`

```json
{
  "message": "Equipment not found",
  "unit_no": "INVALID-001"
}
```

#### Response Fields

Same as equipment list endpoint, but relationships are returned as nested objects instead of flat fields.

---

### 3. Projects List

Get a list of all active projects.

#### Request

```http
GET /api/projects
```

#### Query Parameters

None

#### Example Request

```bash
GET /api/projects
```

#### Response

**Status Code**: `200 OK`

```json
[
  {
    "project_code": "PRJ-2023-01",
    "bowheer": "PT Construction Company A",
    "location": "Jakarta"
  },
  {
    "project_code": "PRJ-2023-02",
    "bowheer": "PT Mining Company B",
    "location": "Kalimantan"
  },
  {
    "project_code": "PRJ-2023-03",
    "bowheer": "PT Infrastructure Ltd",
    "location": "Surabaya"
  }
]
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `project_code` | string | Unique project code |
| `bowheer` | string | Project owner/client name |
| `location` | string | Project location |

#### Notes

- Only returns active projects (`isActive = 1`)
- Excludes system default project (code '111')
- Returns array directly (no wrapper object)

---

### 4. Authenticated User

Get currently authenticated user information.

#### Request

```http
GET /api/user
```

#### Authentication

**Required**: Yes (Laravel Sanctum token)

```bash
curl -H "Authorization: Bearer {token}" \
  http://your-domain.com/api/user
```

#### Response

**Status Code**: `200 OK`

```json
{
  "id": 1,
  "name": "Admin User",
  "email": "admin@arkfleet.com",
  "email_verified_at": "2023-01-01T00:00:00.000000Z",
  "is_active": 1,
  "created_at": "2023-01-01T00:00:00.000000Z",
  "updated_at": "2023-10-28T10:15:30.000000Z"
}
```

#### Error Response (Unauthenticated)

**Status Code**: `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

---

## Reference Data

### Unit Status Values

Use these values with the `status` parameter (case-insensitive):

| Status Name | ID | Description |
|-------------|----|----|
| **ACTIVE** | 1 | Equipment operational and in use |
| **IN-ACTIVE** | 2 | Equipment not currently in use |
| **SCRAP** | 3 | Equipment scrapped/end of life |
| **SOLD** | 4 | Equipment sold to external party |

**Example Usage:**
```bash
# Get all active equipment (RECOMMENDED)
GET /api/equipments?status=ACTIVE

# Get all scrapped equipment
GET /api/equipments?status=SCRAP

# Legacy using ID (still works)
GET /api/equipments?unitstatus_id=1
```

### Plant Groups

Common `plant_group_id` values (examples):

| ID | Name | Description |
|----|------|-------------|
| 1 | Lighting Tower | Mobile lighting equipment |
| 2 | Welding Machine | Welding and fabrication tools |
| 3 | Excavator | Excavation machinery |
| 4 | Bulldozer | Earth moving equipment |
| 5 | Wheel Loader | Loading equipment |
| 6 | Motor Grader | Grading machinery |
| 7 | Dump Truck | Hauling vehicles |

> **Note**: To get the complete list of plant groups, query the database or check the web interface at `/plantgroups`.

### Asset Categories

| ID | Name | Description |
|----|------|-------------|
| 1 | Mayor | Major assets (high value equipment) |
| 2 | Minor | Minor assets (tools and small equipment) |

### Plant Types

| ID | Name | Description |
|----|------|-------------|
| 1 | Hauler | Material transport equipment |
| 2 | Digger | Excavation equipment |
| 3 | Support | Support and auxiliary equipment |

---

## Error Responses

### Standard Error Format

```json
{
  "message": "Error description",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 404 | Not Found | Resource not found |
| 401 | Unauthorized | Authentication required |
| 422 | Unprocessable Entity | Validation error |
| 500 | Internal Server Error | Server error |

---

## Usage Examples

### JavaScript (Fetch API)

```javascript
// Get all ACTIVE equipment (RECOMMENDED - human-readable)
fetch('http://your-domain.com/api/equipments?status=ACTIVE')
  .then(response => response.json())
  .then(data => {
    console.log(`Found ${data.count} active equipment`);
    data.data.forEach(equipment => {
      console.log(`${equipment.unit_no}: ${equipment.description}`);
    });
  });

// Get equipment by project code
fetch('http://your-domain.com/api/equipments?project_code=000H')
  .then(response => response.json())
  .then(data => {
    console.log(`Project 000H has ${data.count} equipment`);
  });

// Get specific equipment by unit number
fetch('http://your-domain.com/api/equipments/by-unit/EX-001')
  .then(response => {
    if (!response.ok) {
      throw new Error('Equipment not found');
    }
    return response.json();
  })
  .then(equipment => {
    console.log(`Equipment: ${equipment.description}`);
    console.log(`Project: ${equipment.project.project_code}`);
    console.log(`Status: ${equipment.unitstatus.name}`);
  })
  .catch(error => {
    console.error('Error:', error);
  });

// Get all projects
fetch('http://your-domain.com/api/projects')
  .then(response => response.json())
  .then(projects => {
    projects.forEach(project => {
      console.log(`${project.project_code}: ${project.bowheer}`);
    });
  });
```

### PHP (cURL)

```php
<?php
// Get equipment by project code (RECOMMENDED)
$projectCode = '000H';
$url = "http://your-domain.com/api/equipments?project_code=" . urlencode($projectCode);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Found {$data['count']} equipment in project {$projectCode}\n";
    
    foreach ($data['data'] as $equipment) {
        echo "- {$equipment['unit_no']}: {$equipment['description']}\n";
    }
} else {
    echo "Error: HTTP {$httpCode}\n";
}

// Get ACTIVE equipment
$url = "http://your-domain.com/api/equipments?status=ACTIVE";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "\nActive equipment: {$data['count']}\n";

// Get specific equipment
$unitNo = 'EX-001';
$url = "http://your-domain.com/api/equipments/by-unit/" . urlencode($unitNo);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $equipment = json_decode($response, true);
    echo "Equipment: {$equipment['description']}\n";
    echo "Status: {$equipment['unitstatus']['name']}\n";
    echo "Project: {$equipment['project']['project_code']}\n";
} else if ($httpCode === 404) {
    echo "Equipment '{$unitNo}' not found\n";
}
?>
```

### Python (requests)

```python
import requests

# Get all equipment
response = requests.get('http://your-domain.com/api/equipments')
data = response.json()

print(f"Found {data['count']} equipment")
for equipment in data['data']:
    print(f"- {equipment['unit_no']}: {equipment['description']}")

# Get equipment with human-readable filters (RECOMMENDED)
params = {
    'project_code': '000H',
    'status': 'ACTIVE'
}
response = requests.get('http://your-domain.com/api/equipments', params=params)
data = response.json()

print(f"\nACTIVE equipment in project 000H: {data['count']}")

# Get SCRAP equipment
response = requests.get('http://your-domain.com/api/equipments', params={'status': 'SCRAP'})
scrap_data = response.json()
print(f"Scrapped equipment: {scrap_data['count']}")

# Get specific equipment
unit_no = 'EX-001'
response = requests.get(f'http://your-domain.com/api/equipments/by-unit/{unit_no}')

if response.status_code == 200:
    equipment = response.json()
    print(f"\nEquipment: {equipment['description']}")
    print(f"Status: {equipment['unitstatus']['name']}")
    print(f"Project: {equipment['project']['project_code']}")
elif response.status_code == 404:
    print(f"Equipment '{unit_no}' not found")

# Get all projects
response = requests.get('http://your-domain.com/api/projects')
projects = response.json()

print("\nActive Projects:")
for project in projects:
    print(f"- {project['project_code']}: {project['bowheer']} ({project['location']})")
```

---

## Rate Limiting

Currently, there is **no rate limiting** implemented on the API endpoints. For production deployment, consider implementing rate limiting to prevent abuse.

---

## CORS Configuration

By default, Laravel API routes may not allow cross-origin requests. If you need to access the API from a different domain (e.g., a frontend application), configure CORS in `config/cors.php`:

```php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://your-frontend-domain.com'],
'allowed_headers' => ['*'],
```

---

## Future Enhancements

Potential API improvements for future versions:

### Planned Features

1. **Pagination**: Add pagination support for large datasets
   ```
   GET /api/equipments?page=1&per_page=50
   ```

2. **Sorting**: Add sort parameter
   ```
   GET /api/equipments?sort=unit_no&order=asc
   ```

3. **Search**: Add search capability
   ```
   GET /api/equipments?search=excavator
   ```

4. **Expanded Relationships**: Include related data
   ```
   GET /api/equipments/by-unit/EX-001?include=documents,movements,photos
   ```

5. **Equipment by ID**: Standard RESTful endpoint
   ```
   GET /api/equipments/{id}
   ```

6. **Write Operations**: POST/PUT/DELETE endpoints with authentication
   ```
   POST /api/equipments (create)
   PUT /api/equipments/{id} (update)
   DELETE /api/equipments/{id} (delete)
   ```

7. **Document Endpoints**: Access equipment documents
   ```
   GET /api/equipments/{id}/documents
   GET /api/documents/expiring (documents expiring soon)
   ```

8. **Movement/Transfer Endpoints**: IPA data access
   ```
   GET /api/movements
   GET /api/equipments/{id}/movements
   ```

9. **Dashboard/Analytics**: Statistics endpoints
   ```
   GET /api/dashboard/stats
   GET /api/reports/equipment-utilization
   ```

10. **Webhooks**: Event notifications for external systems

### API Versioning

When breaking changes are introduced, implement versioning:
```
/api/v1/equipments
/api/v2/equipments
```

---

## Support & Troubleshooting

### Common Issues

**Issue**: CORS errors when accessing from browser
- **Solution**: Configure CORS in `config/cors.php` and ensure API routes are included

**Issue**: 404 errors on all API routes
- **Solution**: Check that `.htaccess` or nginx configuration correctly routes `/api/*` requests

**Issue**: Empty relationships (null values)
- **Solution**: Equipment may not have all relationships assigned. Check database for missing foreign keys

**Issue**: Slow response times
- **Solution**: Ensure database indexes exist on foreign keys and frequently filtered columns

### Debug Mode

For development, you can enable debug mode in `.env`:
```
APP_DEBUG=true
```

This will provide detailed error messages in API responses.

> ⚠️ **Warning**: Never enable `APP_DEBUG=true` in production!

---

## Changelog

### Version 1.1 (2025-10-30)

**Added**:
- Human-readable query parameters for better API usability
  - `project_code` - Filter by project code (e.g., 000H, 017C)
  - `status` - Filter by status name: ACTIVE, IN-ACTIVE, SCRAP, SOLD
  - `plant_group` - Filter by plant group name

**Changed**:
- API now accepts both human-readable codes and legacy ID-based parameters
- Status parameter is case-insensitive for better developer experience
- Updated all documentation examples to use recommended human-readable parameters

**Backward Compatibility**:
- All legacy ID-based parameters still work (`current_project_id`, `unitstatus_id`, `plant_group_id`)

### Version 1.0 (2025-10-30)

**Added**:
- Equipment list endpoint with filtering (`GET /api/equipments`)
- Equipment detail by unit number (`GET /api/equipments/by-unit/{unit_no}`)
- Enhanced equipment resource with 24 fields
- Detailed equipment resource with nested relationships

**Changed**:
- Equipment list endpoint now supports optional filtering (previously only returned RFU status)
- Expanded response fields from 6 to 24 fields

**Removed**:
- Unused test methods in EquipmentApiController

### Version 0.1 (Initial)

**Added**:
- Basic equipment list (RFU only)
- Projects list endpoint
- Authenticated user endpoint (Sanctum)

---

## License & Usage Terms

This API is part of the ARKFleet Equipment Fleet Management System. Usage terms follow the application license.

---

**Document Version**: 1.0  
**Last Updated**: 2025-10-30  
**Maintained By**: ARKFleet Development Team

