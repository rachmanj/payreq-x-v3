# Reference for Bilyet Management System

## 1. Overview

Bilyet Management System adalah modul dalam aplikasi **payreq-x-v3** yang digunakan untuk mengelola bilyet (cek, bilyet giro, dan letter of authority) dalam operasional cashier. Sistem ini memiliki dua komponen utama:

-   **Bilyet (bilyets)**: Tabel utama untuk menyimpan data bilyet yang sudah valid
-   **BilyetTemp (bilyet_temps)**: Tabel temporary untuk validasi data sebelum diimport ke tabel utama

## 2. Database Structure

### 2.1 Tabel `bilyets`

**Migration**: `2024_08_07_073201_create_bilyets_table.php`

```sql
CREATE TABLE bilyets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    giro_id BIGINT UNSIGNED,                    -- Foreign key ke tabel giros
    prefix VARCHAR(10) NULLABLE,                -- Prefix nomor bilyet (2 karakter)
    nomor VARCHAR(30) NOT NULL,                 -- Nomor bilyet
    type VARCHAR(20) NULLABLE,                  -- Jenis: 'cek', 'bg', 'loa'
    receive_date DATE NULLABLE,                 -- Tanggal terima bilyet
    bilyet_date DATE NULLABLE,                  -- Tanggal bilyet diterbitkan
    cair_date DATE NULLABLE,                    -- Tanggal pencairan
    amount DECIMAL(20,2) NULLABLE,              -- Nominal bilyet
    remarks TEXT NULLABLE,                      -- Catatan tambahan
    filename VARCHAR(255) NULLABLE,             -- File attachment
    loan_id BIGINT UNSIGNED NULLABLE,           -- Foreign key ke loans
    created_by BIGINT UNSIGNED NULLABLE,        -- User yang membuat
    project VARCHAR(10) NULLABLE,               -- Kode project
    status VARCHAR(30) DEFAULT 'onhand',        -- Status bilyet
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Status Values:**

-   `onhand`: Bilyet tersedia di tangan (belum digunakan)
-   `release`: Bilyet sudah dikeluarkan/digunakan
-   `cair`: Bilyet sudah dicairkan
-   `void`: Bilyet dibatalkan

### 2.2 Tabel `bilyet_temps`

**Migration**: `2024_09_08_010538_create_bilyet_temps_table.php`

```sql
CREATE TABLE bilyet_temps (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    giro_id BIGINT UNSIGNED NULLABLE,           -- Foreign key ke giros (null saat upload)
    acc_no VARCHAR(50) NULLABLE,                -- Nomor rekening untuk mapping giro_id
    prefix VARCHAR(10) NULLABLE,                -- Prefix nomor bilyet
    nomor VARCHAR(30) NOT NULL,                 -- Nomor bilyet
    type VARCHAR(20) NULLABLE,                  -- Jenis: 'cek', 'bg', 'loa'
    bilyet_date DATE NULLABLE,                  -- Tanggal bilyet
    cair_date DATE NULLABLE,                    -- Tanggal pencairan
    amount DECIMAL(20,2) NULLABLE,              -- Nominal bilyet
    remarks TEXT NULLABLE,                      -- Catatan
    loan_id BIGINT UNSIGNED NULLABLE,           -- Foreign key ke loans
    created_by BIGINT UNSIGNED NULLABLE,        -- User yang upload
    project VARCHAR(10) NULLABLE,               -- Kode project
    status VARCHAR(30) DEFAULT 'onhand',        -- Status default
    batch INTEGER NULLABLE,                     -- Batch upload
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## 3. Routes Structure

**File**: `routes/cashier.php`

### 3.1 Routes Bilyet Utama

```php
Route::prefix('bilyets')->name('bilyets.')->group(function () {
    Route::get('data', [BilyetController::class, 'data'])->name('data');
    Route::get('/', [BilyetController::class, 'index'])->name('index');
    Route::post('/', [BilyetController::class, 'store'])->name('store');
    Route::put('{id}', [BilyetController::class, 'update'])->name('update');
    Route::delete('{id}', [BilyetController::class, 'destroy'])->name('destroy');
    Route::get('export', [BilyetController::class, 'export'])->name('export');
    Route::post('import', [BilyetController::class, 'import'])->name('import');
    Route::post('update-many', [BilyetController::class, 'update_many'])->name('update_many');
});
```

### 3.2 Routes Bilyet Temporary

```php
Route::prefix('bilyet-temps')->name('bilyet-temps.')->group(function () {
    Route::get('data', [BilyetTempController::class, 'data'])->name('data');
    Route::get('/', [BilyetTempController::class, 'index'])->name('index');
    Route::post('upload', [BilyetTempController::class, 'upload'])->name('upload');
    Route::get('truncate', [BilyetTempController::class, 'truncate'])->name('truncate');
    Route::get('/{id}/destroy', [BilyetTempController::class, 'destroy'])->name('destroy');
    Route::put('{id}', [BilyetTempController::class, 'update'])->name('update');
});
```

## 4. Controller Analysis

### 4.1 BilyetController

**File**: `app/Http/Controllers/Cashier/BilyetController.php`

#### 4.1.1 Method `index(Request $request)`

**Purpose**: Menampilkan dashboard dengan berbagai halaman sesuai parameter `page`

**Pages Available**:

-   `dashboard`: Menampilkan data dashboard bilyet
-   `onhand`: Bilyet yang masih di tangan
-   `release`: Bilyet yang sudah dikeluarkan
-   `cair`: Bilyet yang sudah dicairkan
-   `void`: Bilyet yang dibatalkan
-   `upload`: Halaman upload data bilyet

**Key Logic**:

```php
// Role-based data filtering
if (array_intersect(['admin', 'superadmin'], $userRoles)) {
    $giros = Giro::all();
} else {
    $giros = Giro::where('project', auth()->user()->project)->get();
}

// Upload page validations
if ($page === 'upload') {
    $giro_id_null = BilyetTemp::where('giro_id', null)->where('created_by', auth()->user()->id)->count();
    $exist = BilyetTemp::where('created_by', auth()->user()->id)->exists();
    $duplikasi = app(BilyetTempController::class)->cekDuplikasi();
    $duplikasi_bilyet = app(BilyetTempController::class)->cekDuplikasiTabelTujuan();

    // Import button disabled if validation fails
    $import_button = !$exist || $giro_id_null > 0 || !empty($duplikasi) || !empty($duplikasi_bilyet) ? 'disabled' : null;
}
```

#### 4.1.2 Method `store(Request $request)`

**Purpose**: Menyimpan bilyet baru secara manual

**Validation Rules**:

```php
$request->validate([
    'prefix' => 'required',
    'nomor' => 'required',
    'giro_id' => 'required',
]);
```

**Status Logic**:

```php
$status = $request->amount || $request->bilyet_date || $request->cair_date ? 'release' : 'onhand'
```

#### 4.1.3 Method `update(Request $request, $id)`

**Purpose**: Update data bilyet individual

**Status Logic**:

```php
if ($request->is_void) {
    $status = 'void';
} else {
    if ($request->amount && $request->bilyet_date && $request->cair_date) {
        $status = 'cair';
    } elseif ($request->amount || $request->bilyet_date) {
        $status = 'release';
    } else {
        $status = 'onhand';
    }
}
```

#### 4.1.4 Method `import(Request $request)`

**Purpose**: Import data dari bilyet_temps ke bilyets

**Process Flow**:

1. Ambil semua data dari `bilyet_temps` untuk user yang login
2. Tentukan status berdasarkan kelengkapan data:
    - `cair`: jika `amount`, `bilyet_date`, dan `cair_date` ada
    - `release`: jika `amount` atau `bilyet_date` ada
    - `onhand`: jika tidak ada data transaksi
3. Insert ke tabel `bilyets`
4. Hapus data dari `bilyet_temps`

#### 4.1.5 Method `update_many(Request $request)`

**Purpose**: Update multiple bilyet sekaligus untuk mass release

**Logic**:

-   Set status menjadi `release`
-   Update `bilyet_date`, `amount`, dan `remarks`

#### 4.1.6 Method `data()`

**Purpose**: Menyediakan data untuk DataTables dengan filtering

**Status-based Filtering**:

```php
switch ($status) {
    case 'release':
        $bilyet_bystatus = Bilyet::where('status', 'release')->orderBy('bilyet_date', 'asc');
        break;
    case 'cair':
        $bilyet_bystatus = Bilyet::where('status', 'cair')->orderBy('cair_date', 'desc');
        break;
    case 'trash':
        $bilyet_bystatus = Bilyet::where('status', 'void')->orderBy('updated_at', 'desc');
        break;
    default:
        $bilyet_bystatus = Bilyet::where('status', 'onhand');
        break;
}
```

**Project-based Access Control**:

```php
if (array_intersect(['superadmin', 'admin'], $userRoles)) {
    $bilyets = $bilyet_bystatus->orderBy('project', 'asc')->get();
} else {
    $bilyets = $bilyet_bystatus->where('project', auth()->user()->project)->get();
}
```

### 4.2 BilyetTempController

**File**: `app/Http/Controllers/Cashier/BilyetTempController.php`

#### 4.2.1 Method `upload(Request $request)`

**Purpose**: Upload file Excel dan import ke bilyet_temps

**Process Flow**:

1. Validasi file (harus Excel: xls/xlsx)
2. Rename file untuk mencegah duplikasi
3. Move file ke `public/file_upload`
4. Import menggunakan `BilyetTempImport` class
5. Delete file setelah import

#### 4.2.2 Method `cekDuplikasi()`

**Purpose**: Mengecek duplikasi dalam data bilyet_temps

**Logic**:

```php
$bilyet_temps = $this->buatArrayNomor();
$duplikasi = array_unique(array_diff_assoc($bilyet_temps, array_unique($bilyet_temps)));
```

#### 4.2.3 Method `cekDuplikasiTabelTujuan()`

**Purpose**: Mengecek apakah nomor bilyet sudah ada di tabel bilyets

**Logic**:

```php
foreach ($bilyet_temps as $bilyet_temp) {
    $bilyet = Bilyet::where('prefix', substr($bilyet_temp, 0, 2))
        ->where('nomor', substr($bilyet_temp, 2))
        ->first();

    if ($bilyet) {
        $duplikasi_bilyet[] = $bilyet->prefix . $bilyet->nomor;
    }
}
```

## 5. Logika Bisnis & Alur Kerja

### 5.1 Siklus Status

```
onhand → release → cair
   ↓
  void (dapat dari status manapun)
```

**Deskripsi Status**:

-   **onhand**: Bilyet baru diterima, belum digunakan
-   **release**: Bilyet sudah dikeluarkan, ada `bilyet_date` dan/atau `amount`
-   **cair**: Bilyet sudah dicairkan, lengkap dengan `cair_date`
-   **void**: Bilyet dibatalkan/tidak valid

### 5.2 Alur Kerja Import

```
1. Upload File Excel → bilyet_temps
2. Validasi Data:
   - Cek mapping giro_id (berdasarkan acc_no)
   - Cek duplikasi dalam data temp
   - Cek duplikasi dengan data bilyet yang sudah ada
   - Cek validitas loan_id
3. Koreksi manual jika diperlukan
4. Import ke tabel bilyets
5. Hapus data bilyet_temps
```

### 5.3 Aturan Validasi Data

#### 5.3.1 Validasi Upload

-   File harus berformat Excel (.xls, .xlsx)
-   Field wajib: `nomor` (nomor bilyet)
-   `giro_id` harus dapat dipetakan dari `acc_no`
-   Tidak boleh ada duplikasi dalam data yang diupload
-   Tidak boleh ada duplikasi dengan data bilyet yang sudah ada

#### 5.3.2 Validasi Import

-   Semua `giro_id` harus terselesaikan (tidak null)
-   Tidak ada error validasi dari pengecekan duplikasi
-   `loan_id` harus ada dalam tabel loans (jika diisi)

## 6. Models & Relasi

### 6.1 Model Bilyet

**File**: `app/Models/Bilyet.php`

**Relasi**:

```php
public function giro()
{
    return $this->belongsTo(Giro::class);
}
```

**Atribut**: Semua field dapat diisi massal (`protected $guarded = []`)

### 6.2 Model BilyetTemp

**File**: `app/Models/BilyetTemp.php`

**Atribut**: Semua field dapat diisi massal (`protected $guarded = []`)

## 7. Fitur Utama

### 7.1 Dashboard Multi-Halaman

-   **Dashboard**: Ringkasan dengan integrasi laporan
-   **On Hand**: Bilyet yang tersedia
-   **Release**: Bilyet yang sudah dikeluarkan
-   **Cair**: Bilyet yang sudah dicairkan
-   **Void**: Bilyet yang dibatalkan
-   **Upload**: Antarmuka import massal

### 7.2 Operasi Massal

-   **Mass Release**: Update multiple bilyet ke status `release`
-   **Bulk Import**: Import dari Excel melalui tabel temporary
-   **Export Template**: Download template Excel

### 7.3 Integritas Data

-   **Deteksi Duplikasi**: Baik dalam upload maupun terhadap data yang sudah ada
-   **Mapping Giro**: Pemetaan otomatis berdasarkan nomor rekening
-   **Validasi Loan**: Pengecekan keberadaan loan_id
-   **Akses Berbasis Project**: User hanya melihat data project mereka

### 7.4 Kontrol Akses Berbasis Role

-   **Admin/Superadmin**: Akses ke semua project
-   **User Biasa**: Akses hanya ke data project mereka

## 8. Pertimbangan Keamanan

### 8.1 Isolasi Data

-   User hanya mengakses bilyet dari project yang ditugaskan
-   Data temporary diisolasi berdasarkan ID user `created_by`

### 8.2 Penanganan File

-   File yang diupload disimpan sementara dan dihapus setelah diproses
-   Validasi file hanya untuk format Excel
-   Generasi nama file unik untuk mencegah konflik

### 8.3 Validasi Data

-   Validasi server-side untuk semua input
-   Mekanisme pencegahan duplikasi
-   Penegakan foreign key constraints

## 9. Titik Integrasi

### 9.1 Tabel Terkait

-   **giros**: Informasi rekening bank
-   **loans**: Informasi pinjaman untuk tracking bilyet
-   **users**: Manajemen user dan penugasan project

### 9.2 Dependensi Eksternal

-   **Maatwebsite\Excel**: Untuk fungsi import/export Excel
-   **DataTables**: Untuk presentasi dan filtering data
-   **Laravel Excel**: Untuk export template

## 10. Pertimbangan Performa

### 10.1 Optimasi Database

-   Indexing yang tepat pada field yang sering diquery (`status`, `project`, `created_by`)
-   Filtering yang efisien untuk akses data berbasis project

### 10.2 Pemrosesan File

-   Penghapusan file segera setelah import
-   Tabel temporary untuk pemrosesan data bertahap
-   Batch processing untuk import data besar

## 11. Rencana Perubahan Sistem

### 11.1 Perubahan Navigation Structure

#### 11.1.1 Navigation Lama (Saat Ini)

```blade
<!-- x-bilyet-links component -->
<a href="{{ route('cashier.bilyets.index', ['page' => 'dashboard']) }}">Dashboard</a>
<a href="{{ route('cashier.bilyets.index', ['page' => 'onhand']) }}">Onhand</a>
<a href="{{ route('cashier.bilyets.index', ['page' => 'release']) }}">Release</a>
<a href="{{ route('cashier.bilyets.index', ['page' => 'cair']) }}">Cair</a>
<a href="{{ route('cashier.bilyets.index', ['page' => 'void']) }}">Void</a>
<a href="{{ route('cashier.bilyets.index', ['page' => 'upload']) }}">Upload</a>
```

#### 11.1.2 Navigation Baru (Proposed)

```blade
<!-- x-bilyet-links component - Updated -->
<a href="{{ route('cashier.bilyets.index', ['page' => 'dashboard']) }}">Dashboard</a>
<a href="{{ route('cashier.bilyets.index', ['page' => 'list']) }}">List</a>
<a href="{{ route('cashier.bilyets.index', ['page' => 'upload']) }}">Upload</a>
```

### 11.2 Perubahan Controller Logic

#### 11.2.1 Update Method `index()` di BilyetController

**Array Views Baru**:

```php
$views = [
    'dashboard' => 'cashier.bilyets.dashboard',
    'list' => 'cashier.bilyets.list',           // New unified list page
    'upload' => 'cashier.bilyets.upload',
];
```

**Logic untuk List Page**:

```php
elseif ($page === 'list') {
    // Get all bilyets for filtering
    if (array_intersect(['admin', 'superadmin'], $userRoles)) {
        $bilyets = Bilyet::orderBy('created_at', 'desc')->get();
    } else {
        $bilyets = Bilyet::where('project', auth()->user()->project)
                         ->orderBy('created_at', 'desc')
                         ->get();
    }

    return view($views[$page], compact('giros', 'bilyets'));
}
```

#### 11.2.2 Update Method `data()` untuk Unified Filtering

**Enhanced Filtering Logic**:

```php
public function data(Request $request)
{
    $status = $request->query('status');
    $nomor = $request->query('nomor');
    $date_from = $request->query('date_from');
    $date_to = $request->query('date_to');

    $userRoles = app(UserController::class)->getUserRoles();

    // Base query
    $query = Bilyet::query();

    // Status filtering
    if ($status && $status !== 'all') {
        $query->where('status', $status);
    }

    // Nomor filtering
    if ($nomor) {
        $query->where(function($q) use ($nomor) {
            $q->where('nomor', 'LIKE', "%{$nomor}%")
              ->orWhereRaw("CONCAT(prefix, nomor) LIKE ?", ["%{$nomor}%"]);
        });
    }

    // Date range filtering
    if ($date_from && $date_to) {
        $query->whereBetween('created_at', [$date_from, $date_to]);
    } elseif ($date_from) {
        $query->where('created_at', '>=', $date_from);
    } elseif ($date_to) {
        $query->where('created_at', '<=', $date_to);
    }

    // Role-based filtering
    if (!array_intersect(['superadmin', 'admin'], $userRoles)) {
        $query->where('project', auth()->user()->project);
    }

    $bilyets = $query->orderBy('created_at', 'desc')->get();

    return datatables()->of($bilyets)
        ->editColumn('nomor', function ($bilyet) {
            return $bilyet->prefix . $bilyet->nomor;
        })
        ->editColumn('status', function ($bilyet) {
            $statusBadges = [
                'onhand' => '<span class="badge badge-primary">Onhand</span>',
                'release' => '<span class="badge badge-warning">Release</span>',
                'cair' => '<span class="badge badge-success">Cair</span>',
                'void' => '<span class="badge badge-danger">Void</span>',
            ];
            return $statusBadges[$bilyet->status] ?? $bilyet->status;
        })
        ->addColumn('account', function ($bilyet) {
            $remarks = $bilyet->remarks ? $bilyet->remarks : '';
            return '<small>' . $bilyet->giro->bank->name . ' ' . strtoupper($bilyet->giro->curr) . ' | ' . $bilyet->giro->acc_no . '<br>' . $remarks . '</small>';
        })
        ->editColumn('bilyet_date', function ($bilyet) {
            return $bilyet->bilyet_date ? date('d-M-Y', strtotime($bilyet->bilyet_date)) : '-';
        })
        ->editColumn('cair_date', function ($bilyet) {
            return $bilyet->cair_date ? date('d-M-Y', strtotime($bilyet->cair_date)) : '-';
        })
        ->editColumn('amount', function ($bilyet) {
            return $bilyet->amount ? number_format($bilyet->amount, 0, ',', '.') . ',-' : '-';
        })
        ->editColumn('type', function ($bilyet) {
            return strtoupper($bilyet->type);
        })
        ->addIndexColumn()
        ->addColumn('action', 'cashier.bilyets.list_action') // New action template
        ->rawColumns(['action', 'account', 'nomor', 'status'])
        ->toJson();
}
```

### 11.3 Frontend Implementation

#### 11.3.1 List Page Template (resources/views/cashier/bilyets/list.blade.php)

**Search Form Structure**:

```blade
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filter Data Bilyet</h3>
    </div>
    <div class="card-body">
        <form id="filter-form">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="status-filter">
                            <option value="">Semua Status</option>
                            <option value="onhand">Onhand</option>
                            <option value="release">Release</option>
                            <option value="cair">Cair</option>
                            <option value="void">Void</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Nomor Bilyet</label>
                        <input type="text" class="form-control" id="nomor-filter"
                               placeholder="Cari nomor bilyet...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Tanggal Dari</label>
                        <input type="date" class="form-control" id="date-from">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Tanggal Sampai</label>
                        <input type="date" class="form-control" id="date-to">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label><br>
                        <button type="button" class="btn btn-primary" id="apply-filter">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <button type="button" class="btn btn-secondary" id="reset-filter">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
```

**DataTable Structure**:

```blade
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data Bilyet</h3>
    </div>
    <div class="card-body">
        <table id="bilyets-table" class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nomor</th>
                    <th>Status</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Tanggal Bilyet</th>
                    <th>Tanggal Cair</th>
                    <th>Amount</th>
                    <th>Project</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
```

#### 11.3.2 JavaScript Implementation

```javascript
$(document).ready(function () {
    var table = $("#bilyets-table").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('cashier.bilyets.data') }}",
            data: function (d) {
                d.status = $("#status-filter").val();
                d.nomor = $("#nomor-filter").val();
                d.date_from = $("#date-from").val();
                d.date_to = $("#date-to").val();
            },
        },
        columns: [
            {
                data: "DT_RowIndex",
                name: "DT_RowIndex",
                orderable: false,
                searchable: false,
            },
            { data: "nomor", name: "nomor" },
            { data: "status", name: "status" },
            { data: "account", name: "account", orderable: false },
            { data: "type", name: "type" },
            { data: "bilyet_date", name: "bilyet_date" },
            { data: "cair_date", name: "cair_date" },
            { data: "amount", name: "amount" },
            { data: "project", name: "project" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
        order: [[1, "desc"]],
        pageLength: 25,
        responsive: true,
    });

    // Apply filter
    $("#apply-filter").click(function () {
        table.ajax.reload();
    });

    // Reset filter
    $("#reset-filter").click(function () {
        $("#filter-form")[0].reset();
        table.ajax.reload();
    });

    // Real-time filter on enter
    $("#nomor-filter").keypress(function (e) {
        if (e.which == 13) {
            table.ajax.reload();
        }
    });
});
```

### 11.4 Action Button Template

#### 11.4.1 List Action Template (resources/views/cashier/bilyets/list_action.blade.php)

```blade
<div class="btn-group" role="group">
    @if($status == 'onhand')
        <button class="btn btn-sm btn-warning" onclick="editBilyet({{ $id }})"
                title="Edit/Release">
            <i class="fas fa-edit"></i>
        </button>
    @elseif($status == 'release')
        <button class="btn btn-sm btn-success" onclick="cairBilyet({{ $id }})"
                title="Cairkan">
            <i class="fas fa-money-bill"></i>
        </button>
    @endif

    @if($status != 'void')
        <button class="btn btn-sm btn-danger" onclick="voidBilyet({{ $id }})"
                title="Void">
            <i class="fas fa-ban"></i>
        </button>
    @endif

    <button class="btn btn-sm btn-info" onclick="viewBilyet({{ $id }})"
            title="View Details">
        <i class="fas fa-eye"></i>
    </button>
</div>
```

### 11.5 Benefits dari Perubahan

#### 11.5.1 User Experience

-   **Simplified Navigation**: Hanya 3 tab utama (Dashboard, List, Upload)
-   **Unified View**: Semua data bilyet dalam satu tabel dengan filtering
-   **Advanced Search**: Multi-criteria filtering (status, nomor, tanggal)
-   **Better Performance**: Single endpoint untuk semua data

#### 11.5.2 Maintenance

-   **Reduced Complexity**: Lebih sedikit view files dan logic branches
-   **Easier Testing**: Konsolidasi logic testing
-   **Consistent UI**: Satu template untuk semua status

#### 11.5.3 Scalability

-   **Flexible Filtering**: Mudah menambah kriteria filter baru
-   **Better Pagination**: DataTables handling large datasets
-   **Mobile Responsive**: Single responsive table design

### 11.6 Migration Steps

#### 11.6.1 Phase 1: Backend Changes

1. Update `BilyetController@index()` method
2. Enhance `BilyetController@data()` method dengan filtering
3. Update routes jika diperlukan

#### 11.6.2 Phase 2: Frontend Changes

1. Update `x-bilyet-links` component
2. Create new `list.blade.php` view
3. Create `list_action.blade.php` template
4. Update JavaScript handling

#### 11.6.3 Phase 3: Testing & Cleanup

1. Test semua filtering functionality
2. Remove unused view files (onhand.blade.php, release.blade.php, dll)
3. Update dokumentasi

---

_Dokumen referensi ini menyediakan cakupan komprehensif dari Sistem Manajemen Bilyet dalam payreq-x-v3. Untuk detail implementasi, silakan merujuk pada file controller dan model yang sebenarnya._
