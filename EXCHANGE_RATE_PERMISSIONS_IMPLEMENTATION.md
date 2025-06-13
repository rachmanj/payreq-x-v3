# Exchange Rate Granular Permissions Implementation

## Overview

Implementasi sistem permission granular untuk fitur Exchange Rates menggunakan Spatie Laravel Permission dengan SweetAlert2 untuk handling unauthorized access menggunakan exception handling resmi dari Spatie dengan session flash messages.

## ✅ Permissions yang Dibuat

### 1. Basic Permission

-   `akses_exchange_rates` - Akses dasar ke modul exchange rates (sudah ada)

### 2. Granular Permissions

-   `create_exchange_rates` - Membuat exchange rate baru
-   `edit_exchange_rates` - Edit exchange rate dan bulk update
-   `delete_exchange_rates` - Hapus exchange rate dan bulk delete
-   `import_exchange_rates` - Import dari Excel dan download template
-   `export_exchange_rates` - Export ke Excel

## 🔧 Komponen yang Diimplementasi

### 1. Exception Handler (UPDATED)

**File:** `app/Exceptions/Handler.php`
Menggunakan dokumentasi resmi Spatie Permission dengan session flash messages:

```php
$this->renderable(function (UnauthorizedException $e, $request) {
    if ($request->expectsJson()) {
        // For API requests, return JSON response
        return response()->json([
            'responseMessage' => 'You do not have the required authorization.',
            'responseStatus'  => 403,
        ], 403);
    }

    // For web requests, redirect with flash message for SweetAlert2
    return redirect()
        ->back()
        ->with('alert_type', 'error')
        ->with('alert_title', 'Access Denied')
        ->with('alert_message', 'You do not have the required permissions to perform this action.');
});
```

### 2. Template Updates (NEW)

**File:** `resources/views/templates/main.blade.php`

-   ✅ Added SweetAlert2 modal untuk permission errors
-   ✅ Integrated dengan existing Toastr implementation untuk notifications lain

**File:** `resources/views/templates/partials/head.blade.php`

-   ✅ Added SweetAlert2 CSS (bootstrap-4 theme)

**File:** `resources/views/templates/partials/script.blade.php`

-   ✅ Added SweetAlert2 JavaScript

### 3. Middleware Setup

**File:** `app/Http/Kernel.php`

-   ✅ Registered Spatie Permission built-in middleware:
-   `permission` → `\Spatie\Permission\Middlewares\PermissionMiddleware::class`
-   `role` → `\Spatie\Permission\Middlewares\RoleMiddleware::class`
-   `role_or_permission` → `\Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class`

### 4. Controller Protection

**File:** `app/Http/Controllers/ExchangeRateController.php`

```php
public function __construct()
{
    // Basic access permission for all methods
    $this->middleware('permission:akses_exchange_rates');

    // Granular permissions for specific actions
    $this->middleware('permission:create_exchange_rates')->only(['create', 'store']);
    $this->middleware('permission:edit_exchange_rates')->only(['edit', 'update', 'bulkUpdate']);
    $this->middleware('permission:delete_exchange_rates')->only(['destroy', 'bulkDelete']);
    $this->middleware('permission:import_exchange_rates')->only(['import', 'downloadTemplate']);
    $this->middleware('permission:export_exchange_rates')->only(['export']);
}
```

### 5. View Protection

Semua view files dilindungi dengan `@can` directive:

#### Index View (`resources/views/exchange-rates/index.blade.php`)

-   ✅ Add New Button: `@can('create_exchange_rates')`
-   ✅ Bulk Update Button: `@can('edit_exchange_rates')`
-   ✅ Bulk Delete Button: `@can('delete_exchange_rates')`
-   ✅ Export Button: `@can('export_exchange_rates')`
-   ✅ Import Button: `@can('import_exchange_rates')`
-   ✅ Edit Action per row: `@can('edit_exchange_rates')`
-   ✅ Delete Action per row: `@can('delete_exchange_rates')`
-   ✅ Template Download: `@can('import_exchange_rates')`

#### Show View (`resources/views/exchange-rates/show.blade.php`)

-   ✅ Edit Button: `@can('edit_exchange_rates')`
-   ✅ Add New Rate: `@can('create_exchange_rates')`
-   ✅ Export This Pair: `@can('export_exchange_rates')`

#### Create & Edit Views

-   ✅ Dilindungi oleh middleware di controller
-   ✅ Tidak perlu permission check tambahan

### 6. Menu Protection

**File:** `resources/views/templates/partials/menu/accounting.blade.php`

```php
@can('akses_exchange_rates')
    <li><a href="{{ route('accounting.exchange-rates.index') }}" class="dropdown-item">Exchange Rates</a></li>
@endcan
```

### 7. Database Seeder

**File:** `database/seeders/ExchangeRatePermissionsSeeder.php`

-   Membuat semua permissions yang diperlukan
-   Menggunakan `firstOrCreate` untuk avoid duplicate

## 🚨 Unauthorized Handling

### Global Exception Handling with Session Flash Messages (UPDATED)

Menggunakan session flash messages untuk SweetAlert2 modal:

-   **Modal Alert**: `alert_type`, `alert_title`, `alert_message` sessions untuk SweetAlert2
-   **JSON Response**: Untuk AJAX requests dengan struktur `responseMessage` dan `responseStatus`

### Session Flash Messages

```php
// Modal alert (SweetAlert2)
->with('alert_type', 'error')
->with('alert_title', 'Access Denied')
->with('alert_message', 'You do not have the required permissions to perform this action.')
```

### AJAX Error Handling

```php
if ($request->expectsJson()) {
    return response()->json([
        'responseMessage' => 'You do not have the required authorization.',
        'responseStatus'  => 403,
    ], 403);
}
```

### Template Integration

```javascript
// SweetAlert2 for modal alerts
@if (Session::get('alert_type') == 'error')
    Swal.fire({
        icon: 'error',
        title: "{{ Session::get('alert_title', 'Error') }}",
        text: "{{ Session::get('alert_message', 'An error occurred') }}",
        confirmButtonText: 'OK',
        confirmButtonColor: '#d33'
    });
@endif
```

## 📊 Permission Matrix

| Action            | Required Permission     | Additional Check       |
| ----------------- | ----------------------- | ---------------------- |
| View List         | `akses_exchange_rates`  | -                      |
| View Details      | `akses_exchange_rates`  | -                      |
| Create New        | `create_exchange_rates` | `akses_exchange_rates` |
| Edit/Update       | `edit_exchange_rates`   | `akses_exchange_rates` |
| Delete            | `delete_exchange_rates` | `akses_exchange_rates` |
| Bulk Update       | `edit_exchange_rates`   | `akses_exchange_rates` |
| Bulk Delete       | `delete_exchange_rates` | `akses_exchange_rates` |
| Import Excel      | `import_exchange_rates` | `akses_exchange_rates` |
| Export Excel      | `export_exchange_rates` | `akses_exchange_rates` |
| Download Template | `import_exchange_rates` | `akses_exchange_rates` |

## 🛠️ Cara Setup

### 1. Register Exception Handler (UPDATED)

Exception handling sudah ditambahkan di `app/Exceptions/Handler.php` dengan session flash messages

### 2. Update Templates (NEW)

-   ✅ SweetAlert2 CSS added to head partial
-   ✅ SweetAlert2 JS added to script partial
-   ✅ Session flash message handling added to main template

### 3. Run Seeder

```bash
php artisan db:seed --class=ExchangeRatePermissionsSeeder
```

### 4. Assign Permissions ke User/Role

```php
// Contoh assign permission ke user
$user = User::find(1);
$user->givePermissionTo('akses_exchange_rates');
$user->givePermissionTo('create_exchange_rates');
$user->givePermissionTo('edit_exchange_rates');
// dll...

// Atau assign ke role
$role = Role::create(['name' => 'exchange_rate_manager']);
$role->givePermissionTo([
    'akses_exchange_rates',
    'create_exchange_rates',
    'edit_exchange_rates',
    'delete_exchange_rates',
    'import_exchange_rates',
    'export_exchange_rates'
]);
```

## 🔍 Testing Permissions

### Test Cases

1. **User tanpa `akses_exchange_rates`**

    - ❌ Menu tidak muncul
    - ❌ Direct URL → SweetAlert2 modal + redirect back

2. **User dengan `akses_exchange_rates` saja**

    - ✅ Bisa akses list dan detail
    - ❌ Tidak ada tombol Create/Edit/Delete/Import/Export

3. **User dengan permission spesifik**
    - ✅ Hanya tombol yang sesuai permission yang muncul
    - ❌ Action lain akan menunjukkan SweetAlert2 modal

### Verification Commands

```bash
# Check permissions
php artisan permission:show

# Check user permissions
php artisan tinker
>>> User::find(1)->getAllPermissions()

# Check role permissions
>>> Role::find(1)->getAllPermissions()
```

## 🚀 Benefits

1. **Security**: Akses terkontrol di level controller dan view
2. **Granular Control**: Admin bisa memberikan permission spesifik
3. **User Experience**: UI dinamis berdasarkan permission + clear modal feedback
4. **Consistency**: Menggunakan Spatie Permission best practices
5. **Global Exception Handling**: Satu tempat untuk handle semua UnauthorizedException
6. **Official Implementation**: Mengikuti dokumentasi resmi Spatie
7. **Session-based**: Menggunakan Laravel session flash messages
8. **Clean Architecture**: Menggunakan built-in Spatie middleware tanpa custom redundancy

## 📝 Notes

-   Exception handling mengikuti dokumentasi resmi Spatie Permission
-   Menggunakan SweetAlert2 modal untuk error notification (bukan dual notification)
-   Session flash messages untuk better UX
-   Permission names konsisten dengan naming convention aplikasi
-   Implementasi mengikuti Laravel best practices
-   Compatible dengan Laravel 10.48.29
-   SweetAlert2 menggunakan bootstrap-4 theme untuk consistency
-   Built-in Spatie middleware digunakan untuk optimal performance dan maintainability

## ⚠️ Important

Setelah setup selesai, pastikan untuk:

1. Assign permissions ke user/role yang appropriate
2. Test setiap permission level
3. Verify SweetAlert2 modal berfungsi dengan baik untuk unauthorized access
4. Test AJAX requests untuk memastikan JSON response
5. Verify redirect logic sesuai ekspektasi (redirect back)
6. Check bahwa SweetAlert2 assets loaded properly

## 🎨 UI/UX Features

### Modal Alerts (SweetAlert2)

-   ✅ Clear error message untuk unauthorized access
-   ✅ Bootstrap 4 theme untuk consistency dengan AdminLTE
-   ✅ Requires user confirmation untuk close
-   ✅ Proper z-index dan positioning
-   ✅ Icon error untuk visual feedback
-   ✅ Customizable title dan message

## 🔗 References

-   [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission/v6/introduction)
-   [Spatie Laravel Permission Exceptions Documentation](https://spatie.be/docs/laravel-permission/v6/advanced-usage/exceptions)
-   [Laravel Exception Handling](https://laravel.com/docs/10.x/errors)
-   [SweetAlert2 Documentation](https://sweetalert2.github.io/)
-   [AdminLTE SweetAlert2 Integration](https://adminlte.io/themes/v3/plugins/sweetalert2/)
