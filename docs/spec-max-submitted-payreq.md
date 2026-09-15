# Spec — Batas Payreq `submitted` per requestor (maksimal N menunggu approval)

Pemicu: permintaan Iwan (15 Sep 2026). Grill ronde 1 disetujui penuh ("Q1–Q5 ok").

## Aturan
Requestor **tidak boleh submit** Payreq baru (advance maupun reimburse) bila **jumlah payreq miliknya yang berstatus `submitted` sudah mencapai batas** (default **5**).

## Keputusan grill
- **Q1 — Yang dihitung:** hanya `payreqs.status = 'submitted'`. `draft` **tidak** dihitung; `revise` **tidak** dihitung (keputusan Iwan).
- **Q2 — Cakupan hitungan:** per **requestor**, **digabung** advance + reimburse (bukan per tipe).
- **Q3 — Angka batas:** disimpan di tabel **`parameters`** supaya bisa diubah tanpa deploy (`name1 = 'max_submitted_payreq'`), default **5** bila baris parameter belum ada. Ikuti pola pembacaan yang sudah dipakai: `Parameter::where('name1', ...)->first()->param_value`.
- **Q4 — Jalur yang dijaga (semua):** (a) advance `POST user-payreqs/advance/proses` dengan `button_type` `create_submit`/`edit_submit` → `PayreqAdvanceController@submit()`; (b) reimburse `POST user-payreqs/reimburse/submit` → `PayreqReimburseController@submit_payreq()`; (c) API `Api/PayreqApiController` (dua tempat yang men-set `status='submitted'`). Guard ditaruh di **satu** tempat (service/validator bersama) supaya tidak ada jalur yang lupa.
- **Q5 — Perilaku:** simpan **draft tetap boleh**; yang diblokir hanya aksi **submit**. Pesan error jelas: sebutkan jumlah saat ini dan batasnya. Form advance & reimburse menampilkan info **"N dari <batas> payreq menunggu approval"** (dan tombol submit dinonaktifkan saat sudah mencapai batas, sebagai isyarat visual — guard server-side tetap yang menentukan).

## Kondisi data saat spec dibuat (prod, 15 Sep 2026)
- Total `submitted`: 20 (semua tipe `advance`).
- Per user: **Himelda Indrita (id 33) = 14**, Theodoris (106) = 4, dua user lain = 1.
- Konsekuensi yang sudah diketahui & diterima Iwan: Himelda langsung tidak bisa submit sampai turun di bawah batas.

## Titik implementasi
- Service baru (mis. `App\Services\PayreqSubmitLimitService`) dengan `limit(): int` + `validate(User $requestor): ?string` (mengembalikan pesan error atau null).
- Dipanggil di `PayreqAdvanceController@submit()`, `PayreqReimburseController@submit_payreq()`, dan kedua jalur `PayreqApiController` **sebelum** approval plan dibuat / status diubah.
- Migrasi idempotent untuk menyemai parameter `max_submitted_payreq` = 5 (jangan menimpa nilai yang sudah ada).
- Test: blokir pada batas, boleh di bawah batas, parameter override, default 5 saat parameter hilang, `revise`/`draft` tidak dihitung, digabung advance+reimburse, API ikut diblokir, draft tetap bisa disimpan, isi pesan.

## Di luar cakupan
- Tidak ada perubahan pada alur approval/notifikasi; tidak menyentuh SAP; tidak mengubah data payreq yang sudah ada.
