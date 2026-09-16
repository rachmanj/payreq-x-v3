# Spec — OP Umum: Pinbuk Bank → Cash (dengan Bilyet)

Pemicu: Iwan (16 Sep 2026) + contoh transaksi: bilyet CEK Mandiri **JM 130552** (cabang Balikpapan Sudirman 14903, 12/8/2026, Rp 100.934.000) yang di SAP menjadi **OP 268811748** (DocEntry 10271): debit **11101001 Petty Cash 99.950.500** + **11101020 Intransit 983.500**, kredit **11201001 Bank Mandiri 100.934.000** via `PaymentChecks`, Project 000H, remarks "Operational PC by Payreq & PMT BPJS TK".

## Keputusan grill (disetujui Iwan)
- **Q1**: dokumen SAP = **Outgoing Payment "to account"** (`DocType = rAccount`, CardCode = akun GL tujuan seperti contoh), **arah Bank → Cash saja**, dan **boleh multi-akun tujuan** dalam satu OP.
- **Q2**: akun dipilih dari **master akun aplikasi** (bank dari daftar giro yang punya `sap_account`; akun tujuan dari akun `cash` yang punya `sap_account`) — tidak mengetik kode GL bebas.
- **Q3**: **integrasi bilyet** — pilih bilyet status `onhand` milik giro/bank terpilih; setelah OP sukses → bilyet otomatis: `status=cair`, `bilyet_date`, `cair_date`, `amount`, `remarks`, dan nomor OP tersimpan; bilyet `cair`/`void` tidak boleh dipakai; tetap tercatat di audit/riwayat bilyet.
- **Q4**: **langsung posting** (tanpa approval berjenjang) dengan **preview + konfirmasi**; permission baru (mis. `create_general_op`) untuk cashier/accounting/superadmin; giro yang bisa dipakai hanya milik project user.
- **Q5**: **dictat lokal** (tabel baru `general_outgoing_payments` + baris akun tujuan) + `sap_submission_logs` (`document_type` baru `general_outgoing_payment`) + **masuk cakupan Print OP** (label metode **CHEQUE** + nomor bilyet).
- **Saldo (pertanyaan Iwan, disetujui)**: memakai **pola incoming** — `cash app_balance` **naik** per akun tujuan, `advance app_balance` **turun** (counterpart), plus baris `transaksis` (`document_type='incoming'`) sebagai buku kas. Sisi bank tidak diubah (app_balance bank = 0 / bank dilacak di SAP). Variasi: butuh helper baru yang meng-kredit **akun cash spesifik** (fungsi `AccountController::incoming()` sekarang selalu memakai akun cash pertama project).

## Alur
1. User (cashier/accounting) buka form **OP Umum** → pilih **giro bank** (rekening bank, punya `sap_account`), **bilyet onhand** milik giro itu, tanggal, remarks, dan **satu atau lebih akun tujuan `cash`** + nominal per akun (total harus = nominal bilyet/OP).
2. Preview (payload SAP + dampak lokal) → konfirmasi.
3. Submit: POST OP ke SAP (DocType `rAccount`, kredit akun bank giro via `PaymentChecks` berisi nomor bilyet, debit akun tujuan) → sukses: simpan record lokal, log, update bilyet, catat incoming per akun tujuan (cash ↑ / advance ↓ + transaksis).
4. Kalau SAP gagal: tidak ada perubahan lokal (bilyet tetap `onhand`), pesan error apa adanya dari SAP dicatat di log.

## Guard
- Bilyet wajib milik giro terpilih & status `onhand`; total nominal akun tujuan wajib = nominal OP (toleransi 0,5 seperti modul lain).
- Update saldo & bilyet hanya setelah OP sukses; **anti-dobel** (cek record pinbuk sudah pernah dicatat sebelum menambah `app_balance`).
- Multi-akun: satu akun tujuan hanya boleh muncul sekali; tiap akun harus akun `cash` dengan `sap_account`.

## Yang harus diverifikasi ke SAP nyata sebelum dipakai luas
Payload `rAccount` + `PaymentChecks` + multi debit line belum pernah dikirim aplikasi → **wajib diuji sekali dengan OP nyata kecil lalu di-cancel** (izin Iwan), karena instance SL ini menolak properti/koleksi yang tidak sesuai (pelajaran PPh23).

## Di luar cakupan
- Arah cash → bank (setoran) belum; approval berjenjang belum; perubahan master bilyet/giro tidak termasuk.
