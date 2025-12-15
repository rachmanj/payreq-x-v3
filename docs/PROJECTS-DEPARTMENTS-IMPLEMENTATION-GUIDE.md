# Projects & Departments Feature Implementation Guide

**Purpose:** Comprehensive guide for implementing Projects and Departments features with SAP B1 integration  
**Target:** Implementation reference for other applications  
**Date:** November 26, 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Models Implementation](#models-implementation)
4. [SAP B1 Integration](#sap-b1-integration)
5. [Controllers & Routes](#controllers--routes)
6. [Admin UI Components](#admin-ui-components)
7. [Usage in Business Logic](#usage-in-business-logic)
8. [Permissions & Access Control](#permissions--access-control)
9. [Implementation Checklist](#implementation-checklist)
10. [Code Examples](#code-examples)

---

## Overview

### Purpose

**Projects** and **Departments** serve as **cost objects** (master data) for financial tracking and organizational management in an enterprise accounting system. They are:

1. **Cost Centers** - Track expenses by project and organizational unit
2. **Approval Routing** - Determine approval workflows based on project/department
3. **Access Control** - Filter data visibility by user assignment
4. **SAP Integration** - Synchronized with SAP B1 master data

### Key Features

- ✅ SAP B1 master data synchronization
- ✅ Hierarchical department structure (parent-child relationships)
- ✅ Visibility control (is_selectable) for UI filtering
- ✅ Soft deletes for data integrity
- ✅ Permission-based access control
- ✅ Admin UI with DataTables
- ✅ One-click sync from SAP B1

---

## Database Schema

### Projects Table

```sql
CREATE TABLE `projects` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(10) UNIQUE NOT NULL,
  `sap_code` VARCHAR(20) NULLABLE,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULLABLE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `is_selectable` BOOLEAN DEFAULT TRUE,
  `synced_at` TIMESTAMP NULLABLE,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  
  INDEX `idx_code` (`code`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_sap_code` (`sap_code`)
);
```

**Key Fields:**
- `code`: Unique project identifier (10 chars max, e.g., "001H", "PROJ-001")
- `sap_code`: SAP B1 project code for synchronization (nullable, indexed)
- `is_active`: Enable/disable project
- `is_selectable`: Visibility in user-facing dropdowns (can hide without deleting)
- `synced_at`: Last SAP sync timestamp

### Departments Table

```sql
CREATE TABLE `departments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `department_name` VARCHAR(255) NOT NULL,
  `akronim` VARCHAR(20) NULLABLE,
  `sap_code` VARCHAR(20) NULLABLE,
  `description` TEXT NULLABLE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `is_selectable` BOOLEAN DEFAULT TRUE,
  `parent_id` BIGINT UNSIGNED NULLABLE,
  `synced_at` TIMESTAMP NULLABLE,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  
  FOREIGN KEY (`parent_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_parent_id` (`parent_id`),
  INDEX `idx_sap_code` (`sap_code`)
);
```

**Key Fields:**
- `department_name`: Full department name
- `akronim`: Abbreviation/acronym (optional, max 20 chars)
- `sap_code`: SAP B1 Profit Center code for synchronization
- `parent_id`: Self-referencing FK for hierarchical structure
- `is_active`: Enable/disable department
- `is_selectable`: Visibility in user-facing dropdowns
- `synced_at`: Last SAP sync timestamp

### Migration Files Structure

**Laravel Migrations:**

1. **Base Tables:**
   - `2025_11_23_144318_create_projects_table.php`
   - `2025_11_23_120204_create_departments_table.php`

2. **SAP Integration Fields:**
   - `2025_11_26_063431_add_sap_code_and_synced_at_to_projects_table.php`
   - `2025_11_26_063437_add_synced_at_to_departments_table.php`

3. **Visibility Control:**
   - `2025_11_26_070539_add_is_selectable_to_projects_table.php`
   - `2025_11_26_070548_add_is_selectable_to_departments_table.php`

---

## Models Implementation

### Project Model

**File:** `app/Models/Project.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'sap_code',
        'name',
        'description',
        'is_active',
        'is_selectable',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_selectable' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Scope to get only selectable (visible) projects
     * Use this in dropdowns and user-facing forms
     */
    public function scopeSelectable($query)
    {
        return $query->where('is_selectable', true);
    }

    /**
     * Scope to get only active projects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### Department Model

**File:** `app/Models/Department.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_name',
        'akronim',
        'sap_code',
        'description',
        'is_active',
        'parent_id',
        'is_selectable',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_selectable' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Scope to get only selectable (visible) departments
     */
    public function scopeSelectable($query)
    {
        return $query->where('is_selectable', true);
    }

    /**
     * Scope to get only active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Parent department relationship (for hierarchy)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Child departments relationship (for hierarchy)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Payment requests belonging to this department
     */
    public function payreqs(): HasMany
    {
        return $this->hasMany(Payreq::class);
    }

    /**
     * Realizations belonging to this department
     */
    public function realizations(): HasMany
    {
        return $this->hasMany(Realization::class);
    }
}
```

### Key Model Features

1. **Soft Deletes:** Both models use `SoftDeletes` trait to preserve historical data
2. **Selectable Scope:** Filter visible records for UI dropdowns
3. **Active Scope:** Filter active records
4. **Casts:** Boolean and datetime casts for proper type handling
5. **Relationships:** Department has parent/children for hierarchy

---

## SAP B1 Integration

### Prerequisites

1. **SAP B1 Service Layer** must be accessible
2. **Environment Variables** configured:
   ```env
   SAP_SERVER_URL=https://your-server:50000/b1s/v1/
   SAP_DB_NAME=your_company_db
   SAP_USER=your_username
   SAP_PASSWORD=your_password
   SAP_VERIFY_SSL=false  # Set true for production with valid SSL
   ```

3. **Config File:** `config/services.php`
   ```php
   'sap_b1' => [
       'base_url' => env('SAP_SERVER_URL', 'https://arkasrv2:50000/b1s/v1/'),
       'company_db' => env('SAP_DB_NAME'),
       'username' => env('SAP_USER'),
       'password' => env('SAP_PASSWORD'),
       'verify_ssl' => env('SAP_VERIFY_SSL', false),
   ],
   ```

### SapService (Base Service)

**File:** `app/Services/Sap/SapService.php`

This is the foundation service that handles SAP B1 authentication and HTTP communication.

**Key Features:**
- Cookie-based session management (SAP B1 uses cookies, not tokens)
- Automatic re-login on 401 errors
- SSL verification toggle
- Singleton pattern recommended for session reuse

```php
<?php

namespace App\Services\Sap;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class SapService
{
    protected Client $client;
    protected CookieJar $cookieJar;
    protected array $config;

    public function __construct()
    {
        $this->config = config('services.sap_b1');
        $this->cookieJar = new CookieJar();
        
        $this->client = new Client([
            'base_uri' => $this->config['base_url'],
            'cookies' => $this->cookieJar,  // Auto-manages cookies
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'verify' => $this->config['verify_ssl'],
        ]);
    }

    public function login(): bool
    {
        try {
            $response = $this->client->post('Login', [
                'json' => [
                    'CompanyDB' => $this->config['company_db'],
                    'UserName' => $this->config['username'],
                    'Password' => $this->config['password'],
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            Log::error('SAP B1 login failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('SAP B1 login failed: ' . $e->getMessage());
        }
    }

    public function ensureSession(): void
    {
        if ($this->cookieJar->count() === 0) {
            $this->login();
        }
    }

    public function get(string $endpoint, array $options = []): array
    {
        $this->ensureSession();

        try {
            $response = $this->client->get($endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // Auto re-login on 401
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                $this->login();
                $response = $this->client->get($endpoint, $options);
                return json_decode($response->getBody()->getContents(), true);
            }
            throw new \Exception('SAP B1 request failed: ' . $e->getMessage());
        }
    }
}
```

**Register as Singleton (Recommended):**

```php
// In AppServiceProvider::register()
$this->app->singleton(SapService::class, function ($app) {
    return new SapService();
});
```

### SapProjectSyncService

**File:** `app/Services/Sap/SapProjectSyncService.php`

Synchronizes projects from SAP B1 `ProjectsService_GetProjectList` endpoint.

```php
<?php

namespace App\Services\Sap;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapProjectSyncService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function syncProjects(): array
    {
        try {
            $this->sapService->ensureSession();

            // Call SAP B1 ProjectsService_GetProjectList
            $response = $this->sapService->get('ProjectsService_GetProjectList');
            
            if (!isset($response['value']) && !is_array($response)) {
                throw new \Exception('Invalid response format from SAP ProjectsService_GetProjectList');
            }
            
            $projects = $response['value'] ?? (is_array($response) ? $response : []);
            
            $stats = [
                'total' => count($projects),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_messages' => [],
            ];

            DB::beginTransaction();

            try {
                foreach ($projects as $sapProject) {
                    try {
                        $projectCode = $sapProject['ProjectCode'] ?? $sapProject['Code'] ?? null;
                        $projectName = $sapProject['ProjectName'] ?? $sapProject['Name'] ?? null;

                        if (!$projectCode || !$projectName) {
                            $stats['errors']++;
                            $stats['error_messages'][] = 'Missing ProjectCode or ProjectName: ' . json_encode($sapProject);
                            continue;
                        }

                        // Upsert by sap_code
                        $project = Project::where('sap_code', $projectCode)->first();

                        if ($project) {
                            $project->update([
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Name'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'synced_at' => now(),
                                // Note: is_selectable is NOT updated - preserves manual overrides
                            ]);
                            $stats['updated']++;
                        } else {
                            Project::create([
                                'code' => $projectCode,  // Use SAP code as local code
                                'sap_code' => $projectCode,
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Name'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'is_selectable' => true,  // New records default to visible
                                'synced_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $stats['error_messages'][] = "Error processing project {$projectCode}: " . $e->getMessage();
                        Log::error('Error syncing SAP project', [
                            'project' => $sapProject,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('SAP Projects sync completed', $stats);

                return [
                    'success' => true,
                    'message' => "Sync completed: {$stats['created']} created, {$stats['updated']} updated, {$stats['errors']} errors",
                    'stats' => $stats,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('SAP Projects sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'stats' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => 1,
                    'error_messages' => [$e->getMessage()],
                ],
            ];
        }
    }
}
```

**Key Points:**
- Uses `sap_code` for matching (upsert logic)
- Preserves `is_selectable` on updates (manual overrides persist)
- New records default to `is_selectable = true`
- Wraps in transaction for data integrity
- Returns detailed stats for UI feedback

### SapDepartmentSyncService

**File:** `app/Services/Sap/SapDepartmentSyncService.php`

Synchronizes departments from SAP B1 `ProfitCenters` service.

```php
<?php

namespace App\Services\Sap;

use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapDepartmentSyncService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function syncDepartments(): array
    {
        try {
            $this->sapService->ensureSession();

            // Call SAP B1 ProfitCenters with $select filter
            $response = $this->sapService->get('ProfitCenters', [
                'query' => [
                    '$select' => 'CenterCode,CenterName',
                ],
            ]);
            
            if (!isset($response['value']) && !is_array($response)) {
                throw new \Exception('Invalid response format from SAP ProfitCenters');
            }
            
            $departments = $response['value'] ?? (is_array($response) ? $response : []);
            
            $stats = [
                'total' => count($departments),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_messages' => [],
            ];

            DB::beginTransaction();

            try {
                foreach ($departments as $sapDepartment) {
                    try {
                        $centerCode = $sapDepartment['CenterCode'] ?? null;
                        $centerName = $sapDepartment['CenterName'] ?? null;

                        if (!$centerCode || !$centerName) {
                            $stats['errors']++;
                            $stats['error_messages'][] = 'Missing CenterCode or CenterName: ' . json_encode($sapDepartment);
                            continue;
                        }

                        // Upsert by sap_code
                        $department = Department::where('sap_code', $centerCode)->first();

                        if ($department) {
                            $department->update([
                                'department_name' => $centerName,
                                'is_active' => true,
                                'synced_at' => now(),
                                // Note: is_selectable is NOT updated - preserves manual overrides
                            ]);
                            $stats['updated']++;
                        } else {
                            Department::create([
                                'department_name' => $centerName,
                                'sap_code' => $centerCode,
                                'is_active' => true,
                                'is_selectable' => true,  // New records default to visible
                                'synced_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $stats['error_messages'][] = "Error processing department {$centerCode}: " . $e->getMessage();
                        Log::error('Error syncing SAP department', [
                            'department' => $sapDepartment,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('SAP Departments sync completed', $stats);

                return [
                    'success' => true,
                    'message' => "Sync completed: {$stats['created']} created, {$stats['updated']} updated, {$stats['errors']} errors",
                    'stats' => $stats,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('SAP Departments sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'stats' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => 1,
                    'error_messages' => [$e->getMessage()],
                ],
            ];
        }
    }
}
```

**Key Points:**
- Uses `$select` query parameter to limit fields returned
- Maps `CenterCode` → `sap_code`, `CenterName` → `department_name`
- Preserves `is_selectable` on updates
- New records default to `is_selectable = true`

### SAP B1 API Endpoints Used

1. **Projects:** `ProjectsService_GetProjectList`
   - Returns all projects with codes, names, descriptions
   - Response format: `{ "value": [{ "ProjectCode": "...", "ProjectName": "...", ... }] }`

2. **Departments:** `ProfitCenters?$select=CenterCode,CenterName`
   - Returns profit centers (departments)
   - Uses `$select` OData filter to limit fields
   - Response format: `{ "value": [{ "CenterCode": "...", "CenterName": "..." }] }`

---

## Controllers & Routes

### ProjectController

**File:** `app/Http/Controllers/Admin/ProjectController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Sap\SapProjectSyncService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function __construct(
        protected SapProjectSyncService $syncService
    ) {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->dataTable();
        }

        return view('admin.projects.index');
    }

    protected function dataTable()
    {
        $projects = Project::query();

        return DataTables::of($projects)
            ->addColumn('actions', function ($project) {
                return view('admin.projects.partials.actions', compact('project'))->render();
            })
            ->editColumn('is_active', function ($project) {
                return $project->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->editColumn('is_selectable', function ($project) {
                return $project->is_selectable
                    ? '<span class="badge badge-primary">Visible</span>'
                    : '<span class="badge badge-dark">Hidden</span>';
            })
            ->editColumn('synced_at', function ($project) {
                return $project->synced_at
                    ? $project->synced_at->format('Y-m-d H:i:s')
                    : '<span class="text-muted">Never</span>';
            })
            ->rawColumns(['is_active', 'is_selectable', 'synced_at', 'actions'])
            ->make(true);
    }

    public function syncFromSap(Request $request)
    {
        try {
            $result = $this->syncService->syncProjects();

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('admin.projects.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->route('admin.projects.index')
                    ->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.projects.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function toggleVisibility(Project $project)
    {
        try {
            $project->update([
                'is_selectable' => !$project->is_selectable,
            ]);

            $message = $project->is_selectable
                ? 'Project is now visible in selections.'
                : 'Project has been hidden from selections.';

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $project->only(['id', 'is_selectable']),
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
```

### DepartmentController

**File:** `app/Http/Controllers/Admin/DepartmentController.php`

Similar structure to ProjectController, with additional `parent` relationship in DataTable:

```php
protected function dataTable()
{
    $departments = Department::query()->with('parent');

    return DataTables::of($departments)
        ->addColumn('actions', function ($department) {
            return view('admin.departments.partials.actions', compact('department'))->render();
        })
        ->editColumn('is_active', function ($department) {
            return $department->is_active
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Inactive</span>';
        })
        ->editColumn('is_selectable', function ($department) {
            return $department->is_selectable
                ? '<span class="badge badge-primary">Visible</span>'
                : '<span class="badge badge-dark">Hidden</span>';
        })
        ->editColumn('parent', function ($department) {
            return $department->parent
                ? $department->parent->department_name
                : '<span class="text-muted">-</span>';
        })
        ->editColumn('synced_at', function ($department) {
            return $department->synced_at
                ? $department->synced_at->format('Y-m-d H:i:s')
                : '<span class="text-muted">Never</span>';
        })
        ->rawColumns(['is_active', 'is_selectable', 'parent', 'synced_at', 'actions'])
        ->make(true);
}
```

### Routes

**File:** `routes/admin.php`

```php
// Projects Management
Route::middleware('permission:projects.view')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
});

Route::middleware('permission:sap-sync-projects')->group(function () {
    Route::post('projects/sync', [ProjectController::class, 'syncFromSap'])->name('projects.sync');
});

Route::middleware('permission:projects.manage-visibility')->group(function () {
    Route::patch('projects/{project}/visibility', [ProjectController::class, 'toggleVisibility'])->name('projects.toggle-visibility');
});

// Departments Management
Route::middleware('permission:departments.view')->group(function () {
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
});

Route::middleware('permission:sap-sync-departments')->group(function () {
    Route::post('departments/sync', [DepartmentController::class, 'syncFromSap'])->name('departments.sync');
});

Route::middleware('permission:departments.manage-visibility')->group(function () {
    Route::patch('departments/{department}/visibility', [DepartmentController::class, 'toggleVisibility'])->name('departments.toggle-visibility');
});
```

---

## Admin UI Components

### View Structure

**Projects Index:** `resources/views/admin/projects/index.blade.php`

Key components:
- DataTables integration
- "Sync from SAP" button (AJAX)
- Visibility toggle buttons
- Status badges (Active/Inactive, Visible/Hidden)
- Last sync timestamp display
- Toastr notifications for sync/toggle feedback (ensure toastr assets are loaded in the base layout)
- Safe timestamp rendering: `synced_at` parsed with `Carbon::parse()` to handle legacy string timestamps

**Departments Index:** `resources/views/admin/departments/index.blade.php`

Similar structure, with additional:
- Parent department column
- Hierarchical display support
- Toastr notifications for sync/toggle feedback
- Safe timestamp rendering: `synced_at` parsed with `Carbon::parse()` to handle legacy string timestamps

### JavaScript Integration

**Sync Button Example (AJAX):**

```javascript
// In your Blade view
$('#sync-from-sap').on('click', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
    
    $.ajax({
        url: '{{ route("admin.projects.sync") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#projects-table').DataTable().ajax.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error('Sync failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sync from SAP');
        }
    });
});
```

---

## Usage in Business Logic

### Payment Requests

**Projects** are referenced as **string codes** (not FK) in payment requests:

```php
// In Payreq model
protected $fillable = [
    'project',  // String field, matches Project::code
    'department_id',  // FK to departments table
    // ...
];

// Usage
$payreq = Payreq::create([
    'project' => '001H',  // Project code
    'department_id' => 5,  // Department ID
    // ...
]);

// Query
$payreqs = Payreq::where('project', '001H')->get();
```

### Realizations

Similar structure - both project (string) and department_id (FK):

```php
// In Realization model
protected $fillable = [
    'project',
    'department_id',
    // ...
];

// RealizationDetail can also have project/department for line-item tracking
protected $fillable = [
    'project',
    'department_id',
    // ...
];
```

### Approval Workflows

**Approval Stages** use project + department to determine routing:

```php
// In ApprovalStage model
protected $fillable = [
    'document_type',  // payreq, realization, rab
    'project',  // nullable - matches Project::code
    'department_id',  // nullable - FK to departments
    'approver_id',
    'sequence',
    // ...
];

// Matching logic
$stages = ApprovalStage::where('document_type', 'payreq')
    ->where(function($query) use ($document) {
        $query->where('project', $document->project)
              ->orWhereNull('project');  // Default rules
    })
    ->where(function($query) use ($document) {
        $query->where('department_id', $document->department_id)
              ->orWhereNull('department_id');  // Default rules
    })
    ->orderBy('sequence')
    ->get();
```

### Dropdown Filtering in Forms

Always use `selectable()` scope + `active()` scope for user-facing dropdowns:

```php
// In controllers (e.g., PaymentRequestController)
public function create()
{
    $projects = Project::selectable()
        ->active()
        ->orderBy('code')
        ->get();
    
    $departments = Department::selectable()
        ->active()
        ->orderBy('department_name')
        ->get();
    
    return view('payment-requests.create', compact('projects', 'departments'));
}
```

This ensures users only see:
- Active projects/departments
- Visible (is_selectable = true) projects/departments

---

## Permissions & Access Control

### Required Permissions

Create these permissions in your permission seeder:

```php
// Projects Management
'projects.view',              // View projects list
'sap-sync-projects',          // Sync projects from SAP
'projects.manage-visibility', // Toggle is_selectable

// Departments Management
'departments.view',              // View departments list
'sap-sync-departments',          // Sync departments from SAP
'departments.manage-visibility', // Toggle is_selectable
```

### Permission Assignment

```php
// Assign to admin role (all permissions)
$adminRole->givePermissionTo([
    'projects.view',
    'sap-sync-projects',
    'projects.manage-visibility',
    'departments.view',
    'sap-sync-departments',
    'departments.manage-visibility',
]);

// Assign to accountant role (view + sync only)
$accountantRole->givePermissionTo([
    'projects.view',
    'sap-sync-projects',
    'departments.view',
    'sap-sync-departments',
]);
```

### Sidebar Menu Integration

```blade
{{-- In sidebar.blade.php --}}
@can('projects.view')
    <li class="nav-item">
        <a href="{{ route('admin.projects.index') }}" class="nav-link">
            <i class="nav-icon fas fa-project-diagram"></i>
            <p>Projects</p>
        </a>
    </li>
@endcan

@can('departments.view')
    <li class="nav-item">
        <a href="{{ route('admin.departments.index') }}" class="nav-link">
            <i class="nav-icon fas fa-building"></i>
            <p>Departments</p>
        </a>
    </li>
@endcan
```

---

## Implementation Checklist

### Phase 1: Database Setup

- [ ] Create `projects` table migration
- [ ] Create `departments` table migration
- [ ] Add `sap_code` and `synced_at` fields (migrations)
- [ ] Add `is_selectable` fields (migrations)
- [ ] Run migrations
- [ ] Add indexes for performance

### Phase 2: Models

- [ ] Create `Project` model with SoftDeletes
- [ ] Create `Department` model with SoftDeletes
- [ ] Add `scopeSelectable()` to both models
- [ ] Add `scopeActive()` to both models
- [ ] Add relationships (Department parent/children, etc.)
- [ ] Configure fillable fields and casts

### Phase 3: SAP Integration

- [ ] Configure `config/services.php` with SAP B1 credentials
- [ ] Create `SapService` base class
- [ ] Register `SapService` as singleton (recommended)
- [ ] Create `SapProjectSyncService`
- [ ] Create `SapDepartmentSyncService`
- [ ] Test SAP connection and authentication
- [ ] Test sync services

### Phase 4: Controllers & Routes

- [ ] Create `ProjectController`
- [ ] Create `DepartmentController`
- [ ] Add routes with permission middleware
- [ ] Implement DataTables methods
- [ ] Implement sync methods
- [ ] Implement visibility toggle methods

### Phase 5: Permissions

- [ ] Create permission seeder entries
- [ ] Assign permissions to roles
- [ ] Test permission checks

### Phase 6: Admin UI

- [ ] Create projects index view (DataTables)
- [ ] Create departments index view (DataTables)
- [ ] Add sync buttons with AJAX
- [ ] Add visibility toggle buttons
- [ ] Add status badges
- [ ] Add sidebar menu items
- [ ] Test UI interactions

### Phase 7: Business Logic Integration

- [ ] Update PaymentRequest model to use projects/departments
- [ ] Update Realization model to use projects/departments
- [ ] Update ApprovalStage model for project/department matching
- [ ] Update form controllers to use `selectable()` scope
- [ ] Update queries to filter by project/department
- [ ] Test end-to-end workflows

### Phase 8: Testing & Documentation

- [ ] Test SAP sync (create, update scenarios)
- [ ] Test visibility toggles
- [ ] Test permission checks
- [ ] Test data integrity (soft deletes)
- [ ] Test hierarchy (department parent-child)
- [ ] Document API endpoints
- [ ] Document configuration requirements

---

## Code Examples

### Example 1: Using Projects/Departments in Payment Request

```php
// In PaymentRequestController::store()
public function store(StorePaymentRequestRequest $request)
{
    $validated = $request->validated();
    
    // Validate project exists and is selectable
    $project = Project::selectable()
        ->active()
        ->where('code', $validated['project'])
        ->firstOrFail();
    
    // Validate department exists and is selectable
    $department = Department::selectable()
        ->active()
        ->findOrFail($validated['department_id']);
    
    $payreq = Payreq::create([
        'nomor' => $this->generatePayreqNumber(),
        'project' => $project->code,
        'department_id' => $department->id,
        'amount' => $validated['amount'],
        'user_id' => auth()->id(),
        // ...
    ]);
    
    return redirect()->route('payreqs.show', $payreq)
        ->with('success', 'Payment request created successfully.');
}
```

### Example 2: Filtering by User's Project

```php
// In PaymentRequestController::index()
public function index(Request $request)
{
    $user = auth()->user();
    
    $query = Payreq::query();
    
    // Filter by user's project if user has project assignment
    if ($user->project) {
        $query->where('project', $user->project);
    }
    
    // Filter by user's department if user has department assignment
    if ($user->department_id) {
        $query->where('department_id', $user->department_id);
    }
    
    $payreqs = $query->paginate(15);
    
    return view('payreqs.index', compact('payreqs'));
}
```

### Example 3: Approval Stage Matching

```php
// In SequentialApprovalWorkflowService
protected function findMatchingStages($document, string $documentType)
{
    return ApprovalStage::where('document_type', $documentType)
        ->where('is_active', true)
        ->where(function($query) use ($document) {
            // Match project or NULL (default)
            $query->where('project', $document->project)
                  ->orWhereNull('project');
        })
        ->where(function($query) use ($document) {
            // Match department or NULL (default)
            $query->where('department_id', $document->department_id)
                  ->orWhereNull('department_id');
        })
        ->orderBy('sequence')
        ->get();
}
```

### Example 4: Department Hierarchy Query

```php
// Get department with all ancestors
$department = Department::with('parent.parent.parent')->find($id);

// Get department with all children
$department = Department::with('children.children.children')->find($id);

// Get root departments (no parent)
$rootDepartments = Department::whereNull('parent_id')->get();

// Get all departments in a tree structure
function getDepartmentTree($parentId = null)
{
    return Department::where('parent_id', $parentId)
        ->with(['children' => function($query) {
            $query->selectable()->active();
        }])
        ->selectable()
        ->active()
        ->get()
        ->map(function($dept) {
            $dept->children = getDepartmentTree($dept->id);
            return $dept;
        });
}
```

---

## Best Practices

### 1. Always Use Scopes for User-Facing Queries

✅ **DO:**
```php
$projects = Project::selectable()->active()->get();
```

❌ **DON'T:**
```php
$projects = Project::all();  // Includes hidden/inactive
```

### 2. Preserve Manual Overrides During Sync

✅ **DO:**
```php
// Sync updates name, active status, but NOT is_selectable
$project->update([
    'name' => $sapData['name'],
    'is_active' => $sapData['active'],
    'synced_at' => now(),
    // is_selectable is NOT updated - preserves manual changes
]);
```

### 3. Use Transactions for Sync Operations

✅ **DO:**
```php
DB::beginTransaction();
try {
    foreach ($items as $item) {
        // Process items
    }
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 4. Handle SAP Response Variations

✅ **DO:**
```php
$projectCode = $sapProject['ProjectCode'] ?? $sapProject['Code'] ?? null;
$projectName = $sapProject['ProjectName'] ?? $sapProject['Name'] ?? null;
```

### 5. Log Sync Operations

✅ **DO:**
```php
Log::info('SAP Projects sync completed', [
    'stats' => $stats,
    'duration' => $duration,
]);
```

### 6. Return Detailed Sync Results

✅ **DO:**
```php
return [
    'success' => true,
    'message' => "Sync completed: {$created} created, {$updated} updated",
    'stats' => [
        'total' => $total,
        'created' => $created,
        'updated' => $updated,
        'errors' => $errors,
    ],
];
```

---

## Troubleshooting

### SAP Sync Fails with 401 Unauthorized

**Cause:** Session expired or invalid credentials

**Solution:**
- Check SAP credentials in `.env`
- Verify `SapService::login()` is called
- Check cookie jar has cookies after login
- Enable auto re-login on 401 in `SapService::get()`

### Sync Creates Duplicates

**Cause:** Not matching by `sap_code` correctly

**Solution:**
- Ensure `sap_code` is indexed
- Use `where('sap_code', $code)->first()` for matching
- Check for NULL `sap_code` values

### Projects/Departments Not Showing in Dropdowns

**Cause:** `is_selectable = false` or `is_active = false`

**Solution:**
- Check `is_selectable` field value
- Use `selectable()->active()` scope in queries
- Verify visibility toggle is working

### Performance Issues with Large Syncs

**Cause:** Processing too many records in one transaction

**Solution:**
- Process in batches (chunk)
- Consider queue jobs for large syncs
- Add progress indicators in UI

---

## Related Documentation

- [SAP B1 Session Management](./SAP-B1-SESSION-MANAGEMENT.md)
- [Architecture Documentation](./architecture.md)
- [Approval Concept Explanation](./APPROVAL-CONCEPT-EXPLANATION.md)

---

## Summary

This implementation guide provides a complete reference for implementing Projects and Departments features with SAP B1 integration. Key takeaways:

1. **Master Data Pattern:** Projects and Departments are master data synchronized from SAP B1
2. **Visibility Control:** `is_selectable` allows hiding without deleting
3. **Hierarchical Structure:** Departments support parent-child relationships
4. **Cost Object Tracking:** Used throughout financial workflows (payreqs, realizations, approvals)
5. **Permission-Based Access:** Granular permissions control who can view/sync/manage
6. **Sync Preservation:** Manual overrides (`is_selectable`) persist across SAP syncs

Follow the implementation checklist step-by-step for a successful implementation in any Laravel application.

