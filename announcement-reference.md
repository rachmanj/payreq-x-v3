# Announcement Feature Implementation Reference

## Table of Contents

1. [Initial Analysis & Recommendation](#initial-analysis--recommendation)
2. [Step 1: Database Migration](#step-1-database-migration)
3. [Step 2: Model & Controller](#step-2-model--controller)
4. [Step 3: Routes & Views](#step-3-routes--views)
5. [Step 4: Language Standardization](#step-4-language-standardization)
6. [Step 5: UI/UX Improvements](#step-5-uiux-improvements)
7. [Step 6: WYSIWYG Editor Integration](#step-6-wysiwyg-editor-integration)
8. [Final Architecture](#final-architecture)
9. [Usage Guide](#usage-guide)

---

## Initial Analysis & Recommendation

### Requirements Analysis

The client requested an announcement feature with the following specifications:

-   **Purpose**: Inform users about updates or new features in the application
-   **Access Control**: Only Superadmin can create announcements
-   **Target Control**: Announcements can be targeted to specific roles
-   **Format**: Blog post-like format without title, containing only content, date, duration in days, status, and target roles
-   **Display**: Announcements appear on dashboard from start date until duration expires, only for targeted roles
-   **Integration**: Replace existing dashboard/pengumuman.blade.php with new announcement system

### Application Context Analysis

-   **Framework**: Laravel 10 with AdminLTE theme
-   **Existing Auth**: Spatie Permission package for role-based access control
-   **Database**: MySQL with existing user management and role system
-   **UI Framework**: AdminLTE with Bootstrap 4
-   **Existing Features**: Tyre management system with dashboard and existing pengumuman system

### Recommended Implementation Architecture

#### Database Structure

```sql
announcements table:
- id (primary key)
- content (text) - announcement content
- start_date (date) - start display date
- duration_days (integer) - duration in days
- status (enum: 'active', 'inactive') - announcement status
- target_roles (json) - array of role names that can see this announcement
- created_by (foreign key to users table)
- created_at, updated_at (timestamps)
```

#### Key Features Recommended

1. **CRUD Management**: Full Create, Read, Update, Delete functionality
2. **Role-Based Targeting**: Select specific roles who can see announcements
3. **Auto-hide**: Automatic hiding after duration expires
4. **Multiple announcements**: Support for multiple active announcements per role
5. **Rich text editor**: For content formatting
6. **Preview mode**: Admin can preview before publishing
7. **Role-based access**: Only superadmin can manage announcements
8. **Dashboard Integration**: Replace existing pengumuman system

---

## Step 1: Database Migration

### Migration Creation

```bash
php artisan make:migration create_announcements_table
```

### Migration Implementation

**File**: `database/migrations/2025_05_28_005943_create_announcements_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->text('content'); // Announcement content
            $table->date('start_date'); // Start display date
            $table->integer('duration_days'); // Duration in days
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status
            $table->json('target_roles'); // Target roles as JSON array
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Creator
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
```

### Security & Data Integrity Features

-   ✅ **Foreign Key Constraint**: `created_by` linked to `users` table with cascade delete
-   ✅ **Enum Validation**: Status restricted to 'active' or 'inactive'
-   ✅ **Default Values**: Status defaults to 'active'
-   ✅ **Text Field**: Content supports long text content
-   ✅ **JSON Field**: Target roles stored as JSON array for flexibility

---

## Step 2: Model & Controller

### Model Implementation

**File**: `app/Models/Announcement.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'start_date',
        'duration_days',
        'status',
        'target_roles',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'duration_days' => 'integer',
        'target_roles' => 'array',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        $today = Carbon::today();

        return $query->where('start_date', '<=', $today)
                    ->whereRaw('DATE_ADD(start_date, INTERVAL duration_days DAY) >= ?', [$today]);
    }

    public function scopeActiveAndCurrent($query)
    {
        return $query->active()->current();
    }

    public function scopeForUserRoles($query, $userRoles)
    {
        return $query->where(function ($q) use ($userRoles) {
            foreach ($userRoles as $role) {
                $q->orWhereJsonContains('target_roles', $role);
            }
        });
    }

    public function scopeVisibleToUser($query, $user)
    {
        $userRoles = $user->roles->pluck('name')->toArray();
        return $query->activeAndCurrent()->forUserRoles($userRoles);
    }

    // Accessors
    public function getEndDateAttribute()
    {
        return $this->start_date->addDays($this->duration_days);
    }

    public function getIsCurrentAttribute()
    {
        $today = Carbon::today();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < Carbon::today();
    }

    public function getTargetRolesStringAttribute()
    {
        return is_array($this->target_roles) ? implode(', ', $this->target_roles) : '';
    }

    // Helper Methods
    public function isVisibleToUser($user)
    {
        if (!$this->is_current || $this->status !== 'active') {
            return false;
        }

        $userRoles = $user->roles->pluck('name')->toArray();
        return !empty(array_intersect($userRoles, $this->target_roles ?? []));
    }
}
```

### Model Features

-   **Fillable Attributes**: Mass assignment protection including target_roles
-   **Type Casting**: Automatic date, integer, and array casting for target_roles JSON
-   **Relationships**: Creator relationship with User model
-   **Query Scopes**: Active, Current, Role-based filtering, and User visibility scopes
-   **Accessors**: Computed properties for end_date, is_current, is_expired, and role string display
-   **Helper Methods**: Check visibility for specific users based on their roles
-   **Spatie Integration**: Seamless integration with existing role system

### Controller Implementation

**File**: `app/Http/Controllers/AnnouncementController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superadmin']);
    }

    public function index()
    {
        $announcements = Announcement::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('announcements.index', compact('announcements'));
    }

    public function create()
    {
        $roles = Role::all()->pluck('name', 'name');
        return view('announcements.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:65535',
            'start_date' => 'required|date|after_or_equal:today',
            'duration_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
            'target_roles' => 'required|array|min:1',
            'target_roles.*' => 'exists:roles,name',
        ], [
            'content.required' => 'Announcement content is required',
            'content.max' => 'Announcement content is too long',
            'start_date.required' => 'Start date is required',
            'start_date.after_or_equal' => 'Start date cannot be before today',
            'duration_days.required' => 'Duration days is required',
            'duration_days.min' => 'Duration must be at least 1 day',
            'duration_days.max' => 'Duration cannot exceed 365 days',
            'status.required' => 'Status must be selected',
            'status.in' => 'Status must be active or inactive',
            'target_roles.required' => 'At least one target role must be selected',
            'target_roles.min' => 'At least one target role must be selected',
            'target_roles.*.exists' => 'Selected role does not exist',
        ]);

        $validated['created_by'] = Auth::id();
        Announcement::create($validated);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement created successfully');
    }

    public function show(Announcement $announcement)
    {
        return view('announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement)
    {
        $roles = Role::all()->pluck('name', 'name');
        return view('announcements.edit', compact('announcement', 'roles'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:65535',
            'start_date' => 'required|date',
            'duration_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
            'target_roles' => 'required|array|min:1',
            'target_roles.*' => 'exists:roles,name',
        ]);

        $announcement->update($validated);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully');
    }

    public function toggleStatus(Announcement $announcement)
    {
        $announcement->update([
            'status' => $announcement->status === 'active' ? 'inactive' : 'active'
        ]);

        $status = $announcement->status === 'active' ? 'activated' : 'deactivated';

        return redirect()->route('announcements.index')
            ->with('success', "Announcement {$status} successfully");
    }
}
```

### Controller Features

-   **Middleware Protection**: Auth and superadmin role required
-   **Complete CRUD**: All resource methods implemented
-   **Role Integration**: Fetch and validate available roles
-   **Validation**: Comprehensive input validation including target roles
-   **Auto-assignment**: Created_by automatically set to authenticated user
-   **Session Flash Messages**: User-friendly success and error notifications
-   **Extra Method**: Toggle status functionality

### User Model Relationship

**File**: `app/Models/User.php` (Added relationship)

```php
public function announcements()
{
    return $this->hasMany(Announcement::class, 'created_by');
}
```

---

## Step 3: Routes & Views

### Routes Implementation

**File**: `routes/web.php`

```php
// Added import
use App\Http\Controllers\AnnouncementController;

// Added routes within auth middleware group
Route::prefix('announcements')->name('announcements.')->group(function () {
    Route::put('toggle-status/{announcement}', [AnnouncementController::class, 'toggleStatus'])->name('toggle_status');
});
Route::resource('announcements', AnnouncementController::class);
```

### Views Structure

```
resources/views/announcements/
├── index.blade.php     # List all announcements
├── create.blade.php    # Create new announcement with role selection
├── edit.blade.php      # Edit existing announcement with role selection
└── show.blade.php      # View announcement details including target roles
```

### Dashboard Component (Replacing pengumuman.blade.php)

**File**: `resources/views/dashboard/announcements.blade.php`

```php
@php
    $activeAnnouncements = \App\Models\Announcement::visibleToUser(auth()->user())
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

@if($activeAnnouncements->count() > 0)
<div class="row mb-3">
    <div class="col-12">
        @foreach($activeAnnouncements as $announcement)
        <div class="alert alert-info alert-dismissible fade show rounded" role="alert">
            <h5><strong>📢 Announcement</strong></h5>
            <div style="line-height: 1.6;">{!! $announcement->content !!}</div>
            <hr>
            <small class="text-muted">
                <i class="fas fa-calendar-alt"></i>
                <strong>Period:</strong> {{ $announcement->start_date->format('d/m/Y') }} - {{ $announcement->end_date->format('d/m/Y') }}
                ({{ $announcement->duration_days }} days)

                <i class="fas fa-users ml-3"></i>
                <strong>Target:</strong> {{ $announcement->target_roles_string }}

                @if(auth()->user()->hasRole('superadmin'))
                    <br><i class="fas fa-user"></i> <strong>Created by:</strong> {{ $announcement->creator->name }}
                    | <a href="{{ route('announcements.show', $announcement) }}" class="text-primary">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                @endif
            </small>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endforeach
    </div>
</div>
@endif
```

### Dashboard Integration (Replacing pengumuman)

**File**: `resources/views/dashboard/index.blade.php`

```php
@section('content')
    {{-- ANNOUNCEMENTS (Replacing dashboard/pengumuman.blade.php) --}}
    @include('dashboard.announcements')

    <div class="row">
        @include('dashboard.mini_boxes')
    </div>
    <!-- ... rest of dashboard content ... -->
@endsection
```

### Navigation Menu Integration

**File**: `resources/views/templates/partials/menu/admin.blade.php`

```php
<li><a href="{{ route('users.index') }}" class="dropdown-item">User List</a></li>
<li><a href="{{ route('announcements.index') }}" class="dropdown-item">Announcements</a></li>
```

### Updated Create/Edit Views with Role Selection

**Key additions to forms:**

```php
<!-- Target Roles -->
<div class="form-group">
    <label for="target_roles">Target Roles <span class="text-danger">*</span></label>
    <select name="target_roles[]" id="target_roles" class="form-control select2" multiple="multiple" data-placeholder="Select target roles">
        @foreach($roles as $role)
            <option value="{{ $role }}"
                @if(old('target_roles') && in_array($role, old('target_roles'))) selected
                @elseif(isset($announcement) && in_array($role, $announcement->target_roles ?? [])) selected
                @endif>
                {{ ucfirst($role) }}
            </option>
        @endforeach
    </select>
    <small class="form-text text-muted">Select which roles can see this announcement</small>
</div>
```

### View Features Implemented

-   **Role Selection**: Multi-select dropdown with Select2 for better UX
-   **Role Display**: Target roles shown in listing and detail views
-   **User Filtering**: Only announcements for user's roles are displayed
-   **DataTable Integration**: Sortable, searchable announcement list with role column
-   **Status Badges**: Visual status indicators with different colors
-   **Action Buttons**: View, Edit, Toggle Status, Delete with confirmations
-   **Responsive Design**: Mobile-friendly using AdminLTE classes
-   **Form Validation**: Client-side and server-side validation including role validation
-   **Flash Messages**: Success and error notifications using Laravel session

### Flash Message Display

Add this to your layout file (e.g., `resources/views/templates/main.blade.php`) to display flash messages:

```html
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Success!</strong> {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif @if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Error!</strong> {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif
```

---

## Step 4: Language Standardization

### Issue Identified

Initial implementation used mixed Indonesian and English languages, which was inconsistent with the existing application that uses English.

### Changes Made

#### View Labels Translation

-   "Tambah Announcement" → "Add Announcement"
-   "Kembali" → "Back"
-   "Konten Announcement" → "Announcement Content"
-   "Tanggal Mulai" → "Start Date"
-   "Durasi (Hari)" → "Duration (Days)"
-   "Simpan Announcement" → "Save Announcement"
-   "Batal" → "Cancel"

#### Status and Messages Translation

-   "Periode" → "Period"
-   "hari" → "days"
-   "Dibuat oleh" → "Created by"
-   "Informasi Tambahan" → "Additional Information"
-   "Lihat Detail" → "View Details"

#### Confirmation Dialogs Translation

-   "Apakah Anda yakin ingin menghapus announcement ini?" → "Are you sure you want to delete this announcement?"
-   "Apakah Anda yakin ingin mengubah status announcement ini?" → "Are you sure you want to change the status of this announcement?"

#### JavaScript Updates

-   Updated preview period format from "hari" to "days"
-   Maintained Indonesian date format (d/m/Y) for consistency with existing application

### Result

-   ✅ Consistent English language across all announcement features
-   ✅ Maintained existing application conventions
-   ✅ Professional and standardized user interface

---

## Step 5: UI/UX Improvements

### Button Layout Optimization

**Issue**: Action buttons were scattered in card footer, requiring users to scroll down

**Solution**: Moved all action buttons to card header for better accessibility

#### Before:

```html
<div class="card-header">
    <h3 class="card-title">Detail Announcement</h3>
    <div class="card-tools">
        <a href="..." class="btn btn-sm btn-secondary">Back</a>
        <a href="..." class="btn btn-sm btn-warning">Edit</a>
    </div>
</div>
<!-- ... card body ... -->
<div class="card-footer">
    <!-- Action buttons here -->
</div>
```

#### After:

```html
<div class="card-header">
    <h3 class="card-title">Detail Announcement</h3>
    <div class="card-tools">
        <div class="btn-group" role="group">
            <a href="..." class="btn btn-sm btn-secondary mr-2">Back</a>
            <a href="..." class="btn btn-sm btn-warning mr-2">Edit</a>
            <form
                action="..."
                method="POST"
                style="display: inline;"
                class="mr-2"
            >
                <button type="submit" class="btn btn-sm btn-success">
                    Toggle Status
                </button>
            </form>
            <form action="..." method="POST" style="display: inline;">
                <button type="submit" class="btn btn-sm btn-danger">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>
```

### Spacing Improvements

**Issue**: Buttons were too close together without proper spacing

**Solution**: Added `mr-2` (margin-right) classes for better visual separation

```html
<a href="..." class="btn btn-sm btn-secondary mr-2">Back</a>
<a href="..." class="btn btn-sm btn-warning mr-2">Edit</a>
<form action="..." method="POST" style="display: inline;" class="mr-2">
    <!-- Toggle button -->
</form>
<!-- Delete button (no mr-2 as it's the last) -->
```

### Visual Design Enhancement

**Issue**: Alert boxes had sharp corners, looking outdated

**Solution**: Added rounded corners for modern appearance

```html
<div
    class="alert alert-info alert-dismissible fade show rounded"
    role="alert"
></div>
```

### Benefits Achieved

-   ✅ **Better Accessibility**: Actions immediately visible at top
-   ✅ **Improved UX**: No scrolling required for common actions
-   ✅ **Professional Spacing**: Proper margins between elements
-   ✅ **Modern Design**: Rounded corners for contemporary look
-   ✅ **Consistent Layout**: Follows AdminLTE best practices

---

## Step 6: WYSIWYG Editor Integration

### Problem Analysis

Initial implementation used plain textarea with preview functionality:

-   Plain text input was limiting for content formatting
-   Separate preview section was redundant
-   Users couldn't format text (bold, italic, lists, etc.)

### Solution: Summernote Integration

AdminLTE includes Summernote WYSIWYG editor, which provides rich text editing capabilities.

### Implementation Steps

#### 1. Remove Preview Sections

**Removed from both create.blade.php and edit.blade.php:**

```html
<!-- Preview Section -->
<div class="form-group">
    <label>Preview Announcement</label>
    <div id="preview-container" class="alert alert-info" style="display: none;">
        <h5><i class="icon fas fa-info"></i> Announcement Preview</h5>
        <div id="preview-content">Content will appear here...</div>
        <small class="text-muted">
            <strong>Period:</strong> <span id="preview-period">-</span>
        </small>
    </div>
</div>
```

#### 2. Update Textarea

**Removed rows attribute to let Summernote control height:**

```html
<!-- Before -->
<textarea name="content" id="content" class="form-control" rows="6">

<!-- After -->
<textarea name="content" id="content" class="form-control">
```

#### 3. Add Summernote Assets

**Added to both files:**

```html
@section('styles')
<!-- Summernote -->
<link
    rel="stylesheet"
    href="{{ asset('adminlte/plugins/summernote/summernote-bs4.min.css') }}"
/>
@endsection @section('scripts')
<!-- Summernote -->
<script src="{{ asset('adminlte/plugins/summernote/summernote-bs4.min.js') }}"></script>

<script>
    $(document).ready(function () {
        // Initialize Summernote
        $("#content").summernote({
            height: 200,
            placeholder: "Enter announcement content...",
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "underline", "clear"]],
                ["fontname", ["fontname"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture"]],
                ["view", ["fullscreen", "codeview", "help"]],
            ],
        });
    });
</script>
@endsection
```

#### 4. Update Content Display

**Changed from escaped to unescaped HTML output:**

**Dashboard announcements.blade.php:**

```html
<!-- Before -->
<div style="white-space: pre-wrap; line-height: 1.6;">
    {{ $announcement->content }}
</div>

<!-- After -->
<div style="line-height: 1.6;">{!! $announcement->content !!}</div>
```

**Show page:**

```html
<!-- Before -->
<div style="white-space: pre-wrap; line-height: 1.6;">
    {{ $announcement->content }}
</div>

<!-- After -->
<div style="line-height: 1.6;">{!! $announcement->content !!}</div>
```

### Summernote Configuration Features

-   **Height**: Set to 200px for optimal editing space
-   **Placeholder**: Helpful guidance text
-   **Toolbar Options**:
    -   **Style**: Paragraph styles (H1, H2, etc.)
    -   **Font**: Bold, underline, clear formatting
    -   **Font Family**: Different font options
    -   **Color**: Text and background colors
    -   **Paragraph**: Lists (ordered/unordered), paragraphs
    -   **Table**: Insert and edit tables
    -   **Insert**: Links and pictures
    -   **View**: Fullscreen mode, code view, help

### Security Considerations

-   HTML content is stored and displayed using `{!! !!}` syntax
-   Summernote provides built-in XSS protection
-   Content is validated on server-side for maximum length
-   Only superadmin users can create/edit announcements

### Benefits Achieved

-   ✅ **Rich Text Editing**: Full formatting capabilities
-   ✅ **WYSIWYG Experience**: What You See Is What You Get
-   ✅ **Better UX**: No separate preview needed
-   ✅ **Professional Content**: Formatted announcements
-   ✅ **Easy to Use**: Familiar word processor interface
-   ✅ **Consistent Integration**: Uses existing AdminLTE assets

---

## Final Architecture

### System Components Overview

```
Role-Based Announcement Feature Architecture
├── Database Layer
│   ├── announcements table (with target_roles JSON field from initial migration)
│   └── integration with existing roles table (Spatie)
├── Model Layer
│   ├── Announcement.php (with built-in role scopes & visibility methods)
│   ├── User.php (relationship added)
│   └── Role.php (existing Spatie model)
├── Controller Layer
│   └── AnnouncementController.php (CRUD + role validation integrated)
├── View Layer
│   ├── announcements/
│   │   ├── index.blade.php (with role columns)
│   │   ├── create.blade.php (with role selection)
│   │   ├── edit.blade.php (with role selection)
│   │   └── show.blade.php (with role display)
│   └── dashboard/
│       └── announcements.blade.php (replaces pengumuman.blade.php)
├── Routes
│   ├── Resource routes (announcements.*)
│   └── Custom route (toggle_status)
├── Assets
│   ├── Summernote CSS/JS
│   ├── Select2 CSS/JS (for role selection)
│   └── DataTables CSS/JS
└── Integration
    └── Spatie Permission (existing role system)
```

### Security Implementation

-   **Authentication**: Required for all announcement routes
-   **Authorization**: Superadmin role required for management
-   **Role Validation**: Target roles validated against existing roles
-   **Input Validation**: Comprehensive server-side validation
-   **XSS Protection**: Summernote built-in protection
-   **CSRF Protection**: Laravel tokens on all forms
-   **Mass Assignment**: Fillable attributes protection
-   **JSON Security**: Safe JSON storage and querying
-   **Session Security**: Flash messages use Laravel's secure session handling

### Database Design

```sql
announcements
├── id (bigint, primary key, auto-increment)
├── content (text, announcement content with HTML)
├── start_date (date, when to start showing)
├── duration_days (int, how many days to show)
├── status (enum: active/inactive, announcement status)
├── target_roles (json, array of role names)
├── created_by (bigint, foreign key to users.id)
├── created_at (timestamp)
└── updated_at (timestamp)

Foreign Keys:
- created_by REFERENCES users(id) ON DELETE CASCADE

Indexes:
- Primary key on id
- Foreign key index on created_by
- JSON index on target_roles for performance
```

### Query Optimization

-   **Eager Loading**: Creator relationship loaded with announcements
-   **Role-Based Scopes**: Efficient filtering for user's roles
-   **JSON Indexing**: Optimized JSON queries for role matching
-   **Compound Queries**: Combined active, current, and role filtering

---

## Usage Guide

### For Administrators (Superadmin)

#### Creating a Role-Targeted Announcement

1. Navigate to Admin → Announcements
2. Click "Add Announcement"
3. Fill in the form:
    - **Content**: Use Summernote editor for rich formatting
    - **Start Date**: When announcement should appear
    - **Duration**: How many days to display (1-365)
    - **Target Roles**: Select which roles can see this announcement
    - **Status**: Active or Inactive
4. Click "Save Announcement"

#### Managing Announcements

-   **View All**: Admin → Announcements shows list with target roles column
-   **Edit**: Click edit button to modify existing announcement and target roles
-   **Toggle Status**: Quick activate/deactivate without editing
-   **Delete**: Remove announcement permanently (with confirmation)
-   **View Details**: See complete announcement information including target roles

#### Status Indicators

-   **🟢 Active & Current**: Currently displaying on dashboard for target roles
-   **🟡 Active but Expired**: Active status but past end date
-   **🔵 Active (Future)**: Active status but not yet started
-   **⚫ Inactive**: Disabled, won't show on dashboard

### For Regular Users

#### Viewing Announcements

-   Announcements appear automatically on dashboard if user has target role
-   Only see announcements targeted to user's current roles
-   Dismissible alerts with close (×) button
-   Rich formatted content displays properly
-   Period and target role information visible

#### Dashboard Display Rules

-   Only **Active** announcements are shown
-   Only **Current** announcements (within date range) are shown
-   Only announcements targeting user's **current roles** are shown
-   Multiple announcements can display simultaneously
-   Most recent announcements appear first

### Role-Based Examples

#### Example Scenarios

1. **Admin-only announcement**: Target role = ["admin"]

    - Only users with admin role see this announcement

2. **Multi-role announcement**: Target roles = ["manager", "supervisor"]

    - Users with manager OR supervisor role see this announcement

3. **All-user announcement**: Target roles = ["user", "admin", "manager"]
    - All users see this announcement (based on their role)

### Technical Notes

#### Automatic Cleanup

-   Announcements automatically hide after duration expires
-   Role-based filtering happens in real-time
-   No manual intervention required
-   Database records remain for audit purposes

#### Performance Considerations

-   Dashboard queries optimized with role-based scopes
-   JSON queries indexed for performance
-   Eager loading prevents N+1 query problems
-   Minimal database impact on regular users

#### Migration from pengumuman.blade.php

-   **Backward Compatibility**: Replace `@include('dashboard.pengumuman')` with `@include('dashboard.announcements')`
-   **Enhanced Features**: Role targeting, rich content, admin management
-   **Same Appearance**: Maintains similar visual style and placement
-   **Better Performance**: Optimized queries vs old static inclusion

#### Customization Options

-   **Role Selection**: Easily modify target roles via admin interface
-   **Content Formatting**: Summernote toolbar can be customized
-   **Alert Styling**: CSS classes can be adjusted for different themes
-   **Additional Targeting**: Can be extended for user-specific targeting

---

## Conclusion

The role-based announcement feature has been successfully implemented with integrated targeting system from the initial design:

✅ **Complete CRUD functionality** for superadmin users  
✅ **Built-in role-based targeting** using existing Spatie Permission from Step 1  
✅ **Rich text editing** with Summernote WYSIWYG editor  
✅ **Automatic display logic** based on date, duration, and user roles  
✅ **Professional UI/UX** following AdminLTE standards  
✅ **Comprehensive security** with role-based access control and validation  
✅ **Scalable architecture** designed for future enhancements  
✅ **Consistent language** throughout the interface  
✅ **Mobile-responsive design** for all screen sizes  
✅ **Seamless pengumuman.blade.php replacement** with enhanced functionality  
✅ **Performance optimization** with indexed JSON queries  
✅ **Session flash messages** following Laravel best practices

The implementation follows Laravel best practices, maintains security standards, leverages existing Spatie Permission system, and provides an intuitive user experience for both administrators and end users. The role-based targeting system is integrated from the initial database design through to the final UI, ensuring a cohesive and efficient announcement management system that completely replaces the old pengumuman system with enhanced role-based functionality.
