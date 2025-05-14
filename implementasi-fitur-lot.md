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

1. **Buat tabel lot_claims:**

    - Buat migrasi untuk tabel `lot_claims` dengan struktur:
        - id (primary key)
        - lot_number (string, index)
        - user_id (foreign key ke users)
        - realization_id (foreign key ke realizations)
        - claim_date (date)
        - amount (decimal)
        - description (text)
        - status (string)
        - timestamps (created_at, updated_at)

2. **Relasi Database:**
    - Buat foreign key constraint antara `lot_claims` dan `realizations`
    - Buat foreign key constraint antara `lot_claims` dan `users`

### 2. Implementasi Model

1. **Buat Model LotClaim:**

    - Buat model `LotClaim` dengan relasi yang sesuai
    - Definisikan relasi dengan `User` dan `Realization`
    - Tambahkan metode scope untuk filtering

2. **Update Model Realization:**
    - Tambahkan relasi `hasMany` ke LotClaim di model Realization

### 3. Implementasi Controller

1. **Buat Controller LotClaimController:**

    - Implementasikan metode CRUD standard (index, create, store, show, edit, update, delete)
    - Tambahkan validasi request
    - Implementasikan authorization menggunakan policy

2. **Integrasi dengan UserRealizationController:**
    - Update UserRealizationController untuk mendukung pemilihan LotClaim
    - Tambahkan logika untuk menghubungkan LotClaim dengan Realization

### 4. Implementasi View

1. **Buat Views untuk LotClaim:**

    - Form create/edit untuk LotClaim
    - List view dengan datatable
    - Detail view untuk single LotClaim
    - Tambahkan modal untuk quick-add LotClaim

2. **Update View Form Realization:**
    - Tambahkan section untuk memilih/menambahkan LotClaim
    - Implementasikan dynamic form untuk multiple LotClaim

### 5. Implementasi Routes

1. **Tambahkan Routes untuk LotClaim:**
    - Buat resource routes untuk LotClaim di `routes/user_payreqs.php`
    - Implementasikan nested routes jika diperlukan

### 6. Implementasi Permission

1. **Tambahkan Permission untuk LotClaim:**

    - Buat permission baru untuk manajemen LotClaim
    - Assign ke roles yang sesuai

2. **Update Controller dengan Authorization:**
    - Implementasikan Gates/Policies untuk akses LotClaim
    - Tambahkan middleware permission pada routes

### 7. Implementasi Validasi

1. **Buat Request Classes:**
    - StoreLotClaimRequest
    - UpdateLotClaimRequest
    - Implementasikan rules validasi yang sesuai

### 8. Pengujian

1. **Unit Testing:**

    - Test model LotClaim
    - Test relasi dengan Realization

2. **Feature Testing:**
    - Test CRUD operations
    - Test integrasi dengan Realization

### 9. Dokumentasi

1. **Update README:**

    - Tambahkan dokumentasi untuk fitur baru
    - Sertakan instruksi penggunaan

2. **Dokumentasi API:**
    - Jika ada endpoint API baru, dokumentasikan dengan format yang sesuai

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
