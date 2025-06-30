# Dokumentasi Implementasi Fitur LOT di Sistem Payment Request

## Implementasi Fitur Pencarian LOT Number di Menu Submission > Advance

### 1. Persiapan Database

1. **Buat migrasi untuk menambahkan kolom Official Travel Number:**
    - Buat file migrasi baru untuk menambahkan kolom `lot_no` pada tabel `payreqs`
    - Kolom harus bertipe `string` dengan nullable
    - Tambahkan kolom setelah kolom `rab_id`
    - Tambahkan indeks pada kolom ini untuk optimasi pencarian

### 2. Implementasi Model

1. **Update Model Payreq:**
    - Tambahkan field `lot_no` ke dalam property `$guarded` atau `$fillable`
    - Buat accessor/mutator jika diperlukan untuk formatting data LOT Number

### 3. Integrasi API LOT Number

1. **Buat Service Class untuk API LOT:**

    - Buat service class dalam namespace `App\Services` untuk menangani komunikasi dengan API LOT
    - Implementasikan metode untuk pencarian LOT Number dengan parameter:
        - travel_number (optional)
        - traveler (optional)
        - department (optional) - bisa langsung diarahkan ke auth()->user()->department
        - project (optional) - bisa langsung diarahkan ke auth()->user()->project
    - Gunakan Laravel HTTP Client untuk komunikasi API:

        ```php
        use Illuminate\Support\Facades\Http;

        // Contoh implementasi di service
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($endpoint, $params);
        ```

    - Tambahkan error handling dan logging:
        ```php
        try {
            $response = Http::timeout(30)->post($endpoint, $params);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('LOT API Error: ' . $e->getMessage());
            throw new \Exception('Failed to fetch LOT data');
        }
        ```

2. **Buat Class Config untuk API LOT:**

    - Tambahkan konfigurasi untuk API LOT di file `config/services.php`:
        ```php
        'lot' => [
            'base_url' => 'http://192.168.32.15/hcssis/api/v1',
            'search_endpoint' => '/officialtravels/search',
            'timeout' => 30,
        ]
        ```
    - Sertakan parameter seperti API URL, credentials, dan timeout

3. **Buat Response DTO:**
    - Buat Data Transfer Object (DTO) untuk memformat response dari API LOT
    - Implementasikan interface untuk memastikan konsistensi data
    - Contoh struktur DTO:
        ```php
        class LotResponseDTO
        {
            public function __construct(
                public readonly string $travel_number,
                public readonly string $traveler,
                public readonly string $department,
                public readonly string $project,
                public readonly string $status
            ) {}
        }
        ```

### 4. Implementasi Controller

1. **Update UserPayreqController:**

    - Tambahkan metode untuk pencarian LOT Number
    - Inject LOT Service ke dalam controller
    - Implementasikan endpoint untuk AJAX request pencarian LOT
    - Validasi input pencarian dari pengguna

2. **Buat Response JSON untuk hasil pencarian:**
    - Format hasil pencarian dari API ke format JSON yang sesuai
    - Sertakan informasi status dan pesan error jika diperlukan

### 5. Implementasi Frontend

1. **Update View Form Submission Advance:**

    - Tambahkan field untuk LOT Number dengan autocomplete
    - Tambahkan tombol pencarian LOT Number
    - Implementasikan JavaScript untuk AJAX request

2. **Implementasi JavaScript untuk Autocomplete:**
    - Gunakan library seperti Select2 atau jQuery UI untuk autocomplete
    - Implementasikan event handlers untuk memproses hasil pencarian
    - Tambahkan validasi client-side

### 6. Update Routes

1. **Tambahkan Routes untuk pencarian LOT:**
    - Tambahkan route baru di `routes/user_payreqs.php` untuk endpoint pencarian LOT
    - Pastikan route dilindungi dengan middleware autentikasi yang sesuai

### 7. Pengujian

1. **Unit Testing untuk API Service:**

    - Buat test case untuk API service
    - Mock response API untuk pengujian

2. **Integration Testing:**
    - Test integrasi form submission dengan LOT Number
    - Verifikasi data tersimpan dengan benar

## Implementasi CRUD untuk LOT Claim (Realization)

### 1. Persiapan Database

1. **Buat tabel lot_claims (tabel utama):**

    - Buat migrasi untuk tabel `lot_claims` dengan struktur:
        - id (primary key, bigInteger, auto increment)
        - lot_no (string, 50, index)
        - claim_date (date)
        - project (string)
        - advance_amount (decimal 15,2)
        - claim_remarks (text, nullable)
        - claim_status (enum: 'pending', 'approved', 'rejected')
        - user_id (foreign key ke users)

2. **Buat tabel lot_claim_accommodations:**

    - id (primary key, bigInteger, auto increment)
    - lot_claim_id (foreign key ke lot_claims)
    - accommodation_description (string) - contoh: hotel, laundry, etc.
    - accommodation_amount (decimal 15,2)
    - notes (text, nullable)

3. **Buat tabel lot_claim_travels:**

    - id (primary key, bigInteger, auto increment)
    - lot_claim_id (foreign key ke lot_claims)
    - travel_description (string) - contoh: airline ticket, airport tax, taxi, etc.
    - travel_amount (decimal 15,2)
    - notes (text, nullable)

4. **Buat tabel lot_claim_meals:**

    - id (primary key, bigInteger, auto increment)
    - lot_claim_id (foreign key ke lot_claims)
    - meal_type (enum: 'airport-meal', 'breakfast', 'lunch', 'dinner', 'other')
    - people_count (integer)
    - per_person_limit (decimal 15,2)
    - frequency (integer)
    - meal_amount (decimal 15,2) - dihitung dari people_count x per_person_limit x frequency
    - notes (text, nullable) e.g.: staff, driver, etc.

5. **Tambahkan kolom-kolom total pada tabel lot_claims:**

    ```php
    // Di migration
    $table->decimal('accommodation_total', 15, 2)->nullable();
    $table->decimal('travel_total', 15, 2)->nullable();
    $table->decimal('meal_total', 15, 2)->nullable();
    $table->decimal('total_claim', 15, 2)->nullable();
    $table->decimal('difference', 15, 2)->nullable();

    // Tambahkan indeks untuk optimasi query
    $table->index('accommodation_total');
    $table->index('travel_total');
    $table->index('meal_total');
    $table->index('total_claim');
    $table->index('difference');
    ```

    Kolom-kolom ini akan menyimpan total dari setiap kategori pengeluaran. Keuntungan menggunakan kolom biasa:

    - Lebih fleksibel dalam pengelolaan data
    - Bisa diupdate secara manual atau melalui event/observer
    - Tidak ada ketergantungan pada fitur database tertentu
    - Bisa diindeks untuk pencarian yang lebih cepat
    - Bisa digunakan dalam WHERE clause dan ORDER BY

6. **Relasi Database:**
    - Buat foreign key constraint antara `lot_claims` dan `realizations`
    - Buat foreign key constraint antara `lot_claims` dan `users`
    - Buat foreign key constraint antara `lot_claim_accommodations` dan `lot_claims`
    - Buat foreign key constraint antara `lot_claim_travels` dan `lot_claims`
    - Buat foreign key constraint antara `lot_claim_meals` dan `lot_claims`
    - Tambahkan indeks untuk kolom yang sering digunakan dalam pencarian

### 2. Implementasi Model

1. **Buat Model LotClaim:**

    ```php
    class LotClaim extends Model
    {
        protected $fillable = [
            'lot_no',
            'claim_date',
            'project',
            'advance_amount',
            'description',
            'status',
            'user_id',
            'accommodation_total',
            'travel_total',
            'meal_total',
            'total_claim',
            'difference'
        ];

        protected $casts = [
            'claim_date' => 'date',
            'advance_amount' => 'decimal:2',
            'accommodation_total' => 'decimal:2',
            'travel_total' => 'decimal:2',
            'meal_total' => 'decimal:2',
            'total_claim' => 'decimal:2',
            'difference' => 'decimal:2'
        ];

        // Event untuk update totals
        protected static function booted()
        {
            static::saving(function ($lotClaim) {
                // Update accommodation total
                $lotClaim->accommodation_total = $lotClaim->accommodations()->sum('accommodation_amount');

                // Update travel total
                $lotClaim->travel_total = $lotClaim->travels()->sum('travel_amount');

                // Update meal total
                $lotClaim->meal_total = $lotClaim->meals()->sum('meal_amount');

                // Update total claim
                $lotClaim->total_claim = $lotClaim->accommodation_total +
                                       $lotClaim->travel_total +
                                       $lotClaim->meal_total;

                // Update difference
                $lotClaim->difference = $lotClaim->advance_amount - $lotClaim->total_claim;
            });
        }

        public function user()
        {
            return $this->belongsTo(User::class);
        }

        public function accommodations()
        {
            return $this->hasMany(LotClaimAccommodation::class);
        }

        public function travels()
        {
            return $this->hasMany(LotClaimTravel::class);
        }

        public function meals()
        {
            return $this->hasMany(LotClaimMeal::class);
        }
    }
    ```

2. **Buat Model LotClaimAccommodation:**

    ```php
    class LotClaimAccommodation extends Model
    {
        protected $fillable = [
            'lot_claim_id',
            'description',
            'amount',
            'receipt_date',
            'receipt_number',
            'notes'
        ];

        protected $casts = [
            'receipt_date' => 'date',
            'amount' => 'decimal:2'
        ];

        public function lotClaim()
        {
            return $this->belongsTo(LotClaim::class);
        }
    }
    ```

3. **Buat Model LotClaimTravel:**

    ```php
    class LotClaimTravel extends Model
    {
        protected $fillable = [
            'lot_claim_id',
            'description',
            'amount',
            'travel_date',
            'receipt_number',
            'notes'
        ];

        protected $casts = [
            'travel_date' => 'date',
            'amount' => 'decimal:2'
        ];

        public function lotClaim()
        {
            return $this->belongsTo(LotClaim::class);
        }
    }
    ```

4. **Buat Model LotClaimMeal:**

    ```php
    class LotClaimMeal extends Model
    {
        protected $fillable = [
            'lot_claim_id',
            'meal_type',
            'people_count',
            'per_person_limit',
            'frequency',
            'total_amount',
            'meal_date',
            'notes'
        ];

        protected $casts = [
            'meal_date' => 'date',
            'per_person_limit' => 'decimal:2',
            'total_amount' => 'decimal:2'
        ];

        public function lotClaim()
        {
            return $this->belongsTo(LotClaim::class);
        }

        // Otomatis menghitung total_amount
        protected static function booted()
        {
            static::creating(function ($meal) {
                $meal->total_amount = $meal->people_count * $meal->per_person_limit * $meal->frequency;
            });

            static::updating(function ($meal) {
                $meal->total_amount = $meal->people_count * $meal->per_person_limit * $meal->frequency;
            });
        }
    }
    ```

### 3. Implementasi Controller

1. **Buat Controller LotClaimController:**

    ```php
    class LotClaimController extends Controller
    {
        public function index()
        {
            $lotClaims = LotClaim::with(['user'])
                ->when(request('search'), function($q) {
                    return $q->where('lot_no', 'like', '%' . request('search') . '%')
                        ->orWhere('description', 'like', '%' . request('search') . '%');
                })
                ->when(request('status'), function($q) {
                    return $q->where('status', request('status'));
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return view('lot-claims.index', compact('lotClaims'));
        }

        public function create()
        {
            return view('lot-claims.create');
        }

        public function store(Request $request)
        {
            DB::beginTransaction();
            try {
                $lotClaim = LotClaim::create($request->validated());

                // Store accommodations
                if ($request->has('accommodations')) {
                    foreach ($request->accommodations as $accommodation) {
                        $lotClaim->accommodations()->create($accommodation);
                    }
                }

                // Store travels
                if ($request->has('travels')) {
                    foreach ($request->travels as $travel) {
                        $lotClaim->travels()->create($travel);
                    }
                }

                // Store meals
                if ($request->has('meals')) {
                    foreach ($request->meals as $meal) {
                        $lotClaim->meals()->create($meal);
                    }
                }

                DB::commit();
                return redirect()->route('lot-claims.show', $lotClaim)
                    ->with('success', 'LOT Claim created successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Failed to create LOT Claim: ' . $e->getMessage());
            }
        }

        public function show(LotClaim $lotClaim)
        {
            $lotClaim->load(['accommodations', 'travels', 'meals']);
            return view('lot-claims.show', compact('lotClaim'));
        }

        public function edit(LotClaim $lotClaim)
        {
            $lotClaim->load(['accommodations', 'travels', 'meals']);
            return view('lot-claims.edit', compact('lotClaim'));
        }

        public function update(Request $request, LotClaim $lotClaim)
        {
            DB::beginTransaction();
            try {
                $lotClaim->update($request->validated());

                // Handle accommodations
                if ($request->has('accommodations')) {
                    // Delete removed accommodations
                    $keepIds = collect($request->accommodations)
                        ->pluck('id')
                        ->filter()
                        ->toArray();

                    $lotClaim->accommodations()
                        ->whereNotIn('id', $keepIds)
                        ->delete();

                    // Update or create accommodations
                    foreach ($request->accommodations as $accommodation) {
                        if (!empty($accommodation['id'])) {
                            $lotClaim->accommodations()
                                ->find($accommodation['id'])
                                ->update($accommodation);
                        } else {
                            $lotClaim->accommodations()->create($accommodation);
                        }
                    }
                }

                // Similar logic for travels and meals
                // ...

                DB::commit();
                return redirect()->route('lot-claims.show', $lotClaim)
                    ->with('success', 'LOT Claim updated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Failed to update LOT Claim: ' . $e->getMessage());
            }
        }

        public function destroy(LotClaim $lotClaim)
        {
            try {
                $lotClaim->delete();
                return redirect()->route('lot-claims.index')
                    ->with('success', 'LOT Claim deleted successfully');
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to delete LOT Claim: ' . $e->getMessage());
            }
        }
    }
    ```

2. **Buat Controllers untuk Detail:**
    - LotClaimAccommodationController
    - LotClaimTravelController
    - LotClaimMealController

### 4. Implementasi View

1. **Buat Views untuk LotClaim:**

    - `index.blade.php`:

        - Tabel dengan kolom: LOT Number, Claim Date, Project, Advance Amount, Total Claim, Difference, Status, Actions
        - Filter berdasarkan status, tanggal, dan LOT number
        - Pagination
        - Tombol untuk create, edit, delete

    - `create.blade.php` dan `edit.blade.php`:

        - Form utama untuk LOT Claim
        - Tabs untuk berbagai kategori pengeluaran
        - Dynamic forms untuk accommodations, travels, dan meals dengan tombol add/remove
        - Summary section yang menampilkan perhitungan total dan selisih
        - JavaScript untuk perhitungan on-the-fly

    - `show.blade.php`:
        - Detail informasi LOT Claim
        - Tabel untuk setiap kategori pengeluaran
        - Summary section dengan total untuk tiap kategori
        - Perhitungan final (total claim dan selisih)
        - Status history
        - Tombol untuk print dan export ke PDF

2. **Implementasi JavaScript untuk Perhitungan Dinamis:**

    ```javascript
    // Update totals when any amount changes
    function updateTotals() {
        // Calculate accommodation total
        let accommodationTotal = 0;
        document
            .querySelectorAll(".accommodation-amount")
            .forEach((element) => {
                accommodationTotal += parseFloat(element.value || 0);
            });

        // Calculate travel total
        let travelTotal = 0;
        document.querySelectorAll(".travel-amount").forEach((element) => {
            travelTotal += parseFloat(element.value || 0);
        });

        // Calculate meal total
        let mealTotal = 0;
        document.querySelectorAll(".meal-row").forEach((row) => {
            const peopleCount = parseFloat(
                row.querySelector(".people-count").value || 0
            );
            const perPersonLimit = parseFloat(
                row.querySelector(".per-person-limit").value || 0
            );
            const frequency = parseFloat(
                row.querySelector(".frequency").value || 0
            );
            const rowTotal = peopleCount * perPersonLimit * frequency;

            row.querySelector(".meal-total").textContent = rowTotal.toFixed(2);
            mealTotal += rowTotal;
        });

        // Update displayed totals
        document.getElementById("accommodation-total").textContent =
            accommodationTotal.toFixed(2);
        document.getElementById("travel-total").textContent =
            travelTotal.toFixed(2);
        document.getElementById("meal-total").textContent =
            mealTotal.toFixed(2);

        // Calculate and display grand total
        const totalClaim = accommodationTotal + travelTotal + mealTotal;
        document.getElementById("total-claim").textContent =
            totalClaim.toFixed(2);

        // Calculate and display difference
        const advanceAmount = parseFloat(
            document.getElementById("advance-amount").value || 0
        );
        const difference = advanceAmount - totalClaim;
        document.getElementById("difference").textContent =
            difference.toFixed(2);

        // Highlight difference based on value
        const differenceElement = document.getElementById("difference");
        if (difference < 0) {
            differenceElement.classList.add("text-danger");
            differenceElement.classList.remove("text-success");
        } else {
            differenceElement.classList.add("text-success");
            differenceElement.classList.remove("text-danger");
        }
    }

    // Add event listeners to all input fields
    document.querySelectorAll("input[type=number]").forEach((input) => {
        input.addEventListener("change", updateTotals);
        input.addEventListener("keyup", updateTotals);
    });

    // Add row functions for each expense type
    function addAccommodationRow() {
        // Clone template row and append to table
        // Add event listeners to new inputs
        // Update totals
    }

    function addTravelRow() {
        // Similar logic
    }

    function addMealRow() {
        // Similar logic
    }

    // Remove row functions
    function removeRow(button) {
        button.closest("tr").remove();
        updateTotals();
    }

    // Initialize totals on page load
    document.addEventListener("DOMContentLoaded", updateTotals);
    ```

### 5. Implementasi Routes

1. **Tambahkan Routes untuk LotClaim:**
    ```php
    Route::middleware(['auth'])->group(function () {
        Route::resource('lot-claims', LotClaimController::class);
        Route::post('lot-claims/search', [LotClaimController::class, 'search'])->name('lot-claims.search');
    });
    ```

### 6. Implementasi Permission

1. **Tambahkan Permission untuk LotClaim:**

    - `lot-claims.view`
    - `lot-claims.create`
    - `lot-claims.edit`
    - `lot-claims.delete`
    - `lot-claims.approve`

2. **Update Controller dengan Authorization:**
    ```php
    public function __construct()
    {
        $this->authorizeResource(LotClaim::class, 'lotClaim');
    }
    ```

### 7. Implementasi Validasi

1. **StoreLotClaimRequest:**
    ```php
    public function rules()
    {
        return [
            'lot_number' => 'required|string|max:50',
            'claim_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'realization_id' => 'required|exists:realizations,id'
        ];
    }
    ```

### 8. Pengujian

1. **Unit Testing:**

    - Test model LotClaim
    - Test relasi dengan Realization
    - Test validasi input
    - Test authorization

2. **Feature Testing:**
    - Test CRUD operations
    - Test integrasi dengan Realization
    - Test approval workflow
    - Test search dan filter

### 9. Dokumentasi

1. **Update README:**

    - Tambahkan dokumentasi untuk fitur LOT Claim
    - Sertakan instruksi penggunaan
    - Tambahkan contoh penggunaan API

2. **Dokumentasi API:**
    - Dokumentasikan endpoint untuk LOT Claim
    - Sertakan contoh request dan response
    - Tambahkan informasi tentang authorization

## Timeline Implementasi

1. **Fase 1: Persiapan Database dan Model (2–3 hari)**

    - Migrasi database untuk kedua fitur
    - Implementasi dan update model yang terkait

2. **Fase 2: Integrasi API LOT Number (3–4 hari)**

    - Implementasi service untuk API LOT
    - Testing integrasi API

3. **Fase 3: Implementasi Frontend untuk LOT Number Search (3–4 hari)**

    - Update form submission dengan pencarian LOT
    - Implementasi JavaScript untuk AJAX dan autocomplete

4. **Fase 4: Implementasi CRUD LotClaim (5–7 hari)**

    - Implementasi controller dan views untuk LotClaim
    - Integrasi dengan Realization

5. **Fase 5: Testing dan Optimisasi (3–4 hari)**

    - Unit dan feature testing
    - Performance optimization

6. **Fase 6: Deployment dan Dokumentasi (1–2 hari)**
    - Deployment ke staging environment
    - Finalisasi dokumentasi dan user guide

## Kesimpulan

Implementasi fitur LOT Number search dan CRUD LotClaim akan meningkatkan efisiensi proses permintaan dan realisasi pembayaran dengan menghubungkan secara langsung ke sistem Letter of Official Travel. Kedua fitur saling terintegrasi namun dapat diimplementasikan secara bertahap, dengan pencarian LOT Number menjadi prasyarat untuk CRUD LotClaim.
