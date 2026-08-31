# Spec — Upload Bukti Transfer + Verifikasi AI (Outgoing Attachments)

## 1. Goal

Kasir bisa **upload bukti transfer** untuk pembayaran payreq tipe **transfer**, dan **AI memverifikasi** kesesuaian bukti dengan tujuan transfer (bank, no. rekening, atas nama) + nominal — sesuai permintaan requestor. Requestor bisa **melihat bukti transfer** payreq-nya. Upload hanya untuk pembayaran **transfer** (bukan cash).

## 2. Scope

### In
- Tabel `outgoing_attachments` (lampiran per outgoing) + disk storage `outgoing_attachments`
- Upload/download/delete (delete = creator only), multiple file per outgoing (jpg/png/pdf max 5MB)
- **Verifikasi AI otomatis** via queue job: AI baca bukti → ekstrak bank/no.rek/atas nama/amount → PHP bandingkan deterministik dengan tujuan transfer + nominal outgoing → verdict
- Verdict badge: **✓ Sesuai / ✗ Tidak Sesuai / ⏳ Memverifikasi / ⚠️ Gagal Verifikasi** + catatan
- Tampil di: halaman split/pay kasir (per outgoing), list outgoings kasir, detail payreq (requestor/accounting, read-only)
- Mismatch → **peringatan visual saja, TIDAK blokir** (AI tidak final, manusia review)

### Out
- Upload untuk pembayaran cash (tidak ada)
- Blokir alur jika mismatch
- Halaman CRUD khusus / filter lanjutan

## 3. Tech Decisions

- Pattern meniru `realization_attachments` (upload/download/delete + creator-only delete) tapi lebih ringan (tanpa access service terpisah)
- AI: `OpenRouterService` (sudah ada) + method baru vision `verifyTransferProofFromImageBase64` — pola sama dengan `extractReceiptFromImageBase64`
- Verdict = AI ekstraksi (field) + **PHP deterministic comparison** (bukan AI yang menilai cocok/tidak)
- Queue DB (`QUEUE_CONNECTION=database`), job `VerifyTransferProofJob` — PHP 8.5: jangan `public $queue`, pakai `$this->onQueue()` di constructor
- Config baru `services.openrouter.transfer_proof_model` (default: bank_statement_model / gemini-3-flash-preview)

## 4. DB Changes

### Migration: `create_outgoing_attachments_table`
```php
Schema::create('outgoing_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('outgoing_id')->constrained()->cascadeOnDelete();
    $table->string('original_name');
    $table->string('stored_path');
    $table->string('mime')->nullable();
    $table->unsignedBigInteger('size')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('verification_status')->default('pending'); // pending|verified|mismatch|failed
    $table->json('verification_result')->nullable();            // hasil ekstraksi + perbandingan
    $table->timestamps();
});
```

### config/filesystems.php — disk baru
```php
'outgoing_attachments' => [
    'driver' => 'local',
    'root' => storage_path('app/private/outgoing_attachments'),
],
```
(sesuaikan dengan pola disk `realization_attachments` yang sudah ada)

### config/services.php — openrouter
```php
'transfer_proof_model' => env('OPENROUTER_TRANSFER_PROOF_MODEL', env('OPENROUTER_BANK_STATEMENT_MODEL', 'google/gemini-3-flash-preview')),
```

## 5. Komponen

### Model `app/Models/OutgoingAttachment.php`
- `$guarded = []`, relasi `outgoing()`, `creator()` belongsTo User
- accessor `verificationStatusBadge` (badge HTML per status: pending=secondary ⏳, verified=success ✓, mismatch=danger ✗, failed=warning ⚠️)

### Model `app/Models/Outgoing.php` (edit)
- relasi `attachments()` hasMany OutgoingAttachment

### `OpenRouterService` — method baru
```php
public function verifyTransferProofFromImageBase64(string $base64Image, string $mimeType, array $expected): array
```
- Prompt (bahasa Indonesia): "Baca bukti transfer. Return JSON: {bank_name, account_number, account_name, amount, confidence}" — amount integer rupiah tanpa pemisah
- `$expected` berisi {bank_name, account_number, account_name, amount} dari transfer_account + outgoing
- Return hasil ekstraksi (sebelum dibandingkan) — bandingkan di layer Action

### Action `app/Actions/TransferProofVerifier.php` (atau Support class)
- `public static function compare(array $extracted, array $expected): array` → `['status' => 'verified'|'mismatch', 'details' => [...]]`
- Normalisasi: bank (lowercase, strip kata "bank"/"bca"→"bca", hapus spasi), no rekening (hapus spasi/titik), atas nama (lowercase trim), amount (int)
- Cek 4 field: semua cocok → verified; ada beda → mismatch + field mana yang beda (nilai terdeteksi vs diharapkan)

### Queue Job `app/Jobs/VerifyTransferProofJob.php`
- `ShouldQueue`, `Queueable` via constructor `$this->onQueue('default')`
- Handle: ambil attachment + outgoing + payreq + transferAccount + amount → base64 file (jpg/png; PDF → konversi? **batasi upload pdf TANPA verifikasi AI** atau pakai model vision yg terima pdf — rekomendasi: verifikasi AI hanya untuk jpg/png; pdf status 'pending' + catatan "PDF tidak diverifikasi otomatis") → panggil OpenRouterService → compare → update verification_status + verification_result
- Gagal/exception/timeout → status 'failed' + result berisi error (jangan throw — biarkan job selesai)

### Controller `app/Http/Controllers/Cashier/OutgoingAttachmentController.php`
- `store(Request, Outgoing $outgoing)`: validasi file (mimes jpg/jpeg/png/pdf, max 5120), **abort 403 kalau `$outgoing->payment_method !== 'transfer'`** (upload hanya transfer), simpan ke disk (uuid), create attachment, dispatch VerifyTransferProofJob (kalau jpg/png), redirect balik dengan success
- `download(OutgoingAttachment $attachment)`: download dari disk (404 kalau file hilang)
- `destroy(OutgoingAttachment $attachment)`: **hanya creator** (403 kalau bukan pembuat) + delete file + delete record
- `reverify(OutgoingAttachment $attachment)`: re-dispatch job (untuk file yang failed/pending)

### Routes (routes/cashier.php — dalam group cashier, pakai prefix outgoing-attachments)
```php
Route::prefix('outgoing-attachments')->name('outgoing-attachments.')->group(function () {
    Route::post('/outgoings/{outgoing}', [OutgoingAttachmentController::class, 'store'])->name('store');
    Route::get('/{attachment}/download', [OutgoingAttachmentController::class, 'download'])->name('download');
    Route::delete('/{attachment}', [OutgoingAttachmentController::class, 'destroy'])->name('destroy');
    Route::post('/{attachment}/reverify', [OutgoingAttachmentController::class, 'reverify'])->name('reverify');
});
```
(letakkan route sesuai struktur routes/cashier.php yang ada)

## 6. UI/UX

### 1. Halaman split/pay kasir (`cashier/approved/split.blade.php`) — tabel "Outgoing Info"
Per baris outgoing yang `payment_method === 'transfer'`:
- daftar lampiran: nama file + badge verdict + tombol download + delete (creator only)
- form upload (file input + tombol, enctype multipart) — **hanya muncul untuk outgoing transfer**
- kalau ada attachment mismatch → alert kecil peringatan

### 2. List outgoings kasir (`cashier/outgoings/index.blade.php` + controller data())
- kolom "Bukti Transfer": badge jumlah + status ringkas (contoh: "1 ✓" / "1 ✗" / "2 ⏳") + link download

### 3. Detail payreq (`user-payreqs/show.blade.php`)
- Section "Bukti Transfer" (read-only): daftar file + verdict badge + download — requestor/accounting bisa lihat
- Tampil jika payreq `payment_method === 'transfer'` dan ada attachment

### 4. (opsional) List approveds kasir — kolom indikator bukti (bisa diskip bila berat; fokus 1-3 dulu)

## 7. Validasi & Keamanan

- Upload: `required|file|mimes:jpg,jpeg,png,pdf|max:5120`
- **Transfer-only**: server-side abort 403 kalau outgoing bukan transfer (jangan hanya sembunyi di UI)
- Delete: creator-only (403)
- Download: semua user login bisa (auth), 404 kalau file tidak ada
- AI verification hanya jpg/png (PDF → status pending + catatan "PDF tidak diverifikasi otomatis")

## 8. Risks

- **AI salah baca** → verdict salah → karena hanya peringatan (tidak blokir), dampak terbatas; ada tombol re-verify
- **No. rekening yang sama untuk banyak akun** (duplikat) → verifikasi tetap jalan (bandingkan bank + nama juga)
- **File besar** → max 5MB per file
- **Queue gagal** (worker mati) → status tetap pending; tombol re-verify tersedia; catat di UI "verifikasi berjalan otomatis, bisa diulang"
- **OpenRouter API down** → status failed, tidak mengganggu alur bayar

## Keputusan Grill (ringkasan)
1. Upload bukti **transfer saja** + AI verifikasi kesesuaian dgn tujuan transfer
2. Posisi: split page + list outgoings + detail payreq
3. Akses: semua user login upload; delete creator-only
4. Multiple file, jpg/png/pdf max 5MB, disk terpisah
5. Verifikasi otomatis via queue
6. Bandingkan 4 field (bank, no.rek, atas nama, amount) — PHP deterministic
7. Verdict badge 4 status
8. Mismatch → peringatan saja, tidak blokir
9. Model: config OPENROUTER_TRANSFER_PROOF_MODEL
10. Requestor bisa lihat bukti transfer payreq-nya
