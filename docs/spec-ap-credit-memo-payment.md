# Spec: Pembayaran invoice vendor dengan AP Credit Memo (AccountingOne)

**Status:** siap implementasi · **Asal:** permintaan Iwan 29 Sep 2026 (email bu Ria: realisasi payment invoice Trakindo memakai Credit Note Fuel Guarantee Rp 740.954.358)

## Masalah

Halaman `/cashier/invoice-payment` (sumber: API DDS) sudah bisa mengirim **pembayaran vendor ke SAP** (`InvoicePaymentController::submitSapPayment` → `SapVendorPaymentBuilder`), tetapi builder **hanya** mengenal `MEANS_CASH` dan `MEANS_TRANSFER` dan **menolak** jenis lain. Akibatnya pembayaran dengan **AP Credit Memo** (credit note vendor, mis. Fuel Guarantee Trakindo) harus dikerjakan manual di SAP.

## Bukti dari SAP produksi (29 Sep 2026, read-only)

- Invoice Trakindo masuk SAP **lewat DDS**: `Comments = "Imported from DDS - Invoice #7439"`.
- Credit note vendor tercatat sebagai **`Purchase CreditNotes`** dengan pola `NumAtCard = "<no invoice> (CM 54100xxxxx)"`, dan invoice terkait jadi terbayar sebagian (`PaidToDate` naik).
- Objek pembayaran SAP (`VendorPayments`) punya array **`PaymentInvoices`**; tiap baris memuat `DocEntry`, `DocNum`, **`InvoiceType`**, `SumApplied`, `InstallmentId`. Pada pembayaran normal terlihat `InvoiceType = it_PurchaseInvoice`.
- `SapService` sudah memuat peta PaymentMeans `19 => AP Credit Memo` (hanya label).
- **Belum ada** purchase credit note Trakindo sejak Jan 2026 → credit note `7000029869` belum dibuat di SAP (di luar aplikasi).

## Cara memastikan bentuk payload (WAJIB dikerjakan lebih dulu)

Cari pembayaran yang **menerapkan** sebuah credit memo, lalu tiru strukturnya:

1. Ambil credit memo yang sudah applied: `GET {URL}PurchaseCreditNotes?$filter=contains(CardName,'ORIX')` → catat `DocEntry`/`DocNum` (mis. `257100099`, status Close).
2. Tarik pembayaran vendor pada periode pembuatan CM: `GET {URL}VendorPayments?$filter=CardCode eq 'VORIFIDR01' and DocDate ge '2025-01-01' and DocDate le '2025-12-31'` (jangan pakai `$select`; baca `PaymentInvoices[]`).
3. Perhatikan nilai **`InvoiceType`** pada baris credit memo (kandidat: `it_PurchaseCreditNote`) dan **tanda `SumApplied`**. Kalau tidak ditemukan, ambil enum dari `GET {URL}$metadata` (cari `InvoiceTypeEnum`) atau dari dokumen SAP B1 DI API. **Jangan menebak** — kalau tetap tidak jelas, hentikan dan laporkan.

**Cara akses SAP dari .149:** host `arkasrv2` hanya resolve di dalam container (`/etc/hosts` container = `192.168.32.26`). Dari host .149 pakai `https://192.168.32.26:50000/b1s/v1/`, login `POST {URL}Login` dengan CompanyDB/UserName/Password **dibaca runtime dari `.env` aplikasi** (`SAP_DB_NAME`, `SAP_USER`, `SAP_PASSWORD`). Jangan pernah mencetak kredensial.

## Yang dibangun (v1)

1. **Builder** (`SapVendorPaymentBuilder`): tambah `MEANS_CREDIT_MEMO = 'credit_memo'`.
   - Saat mode ini, `PaymentInvoices` memuat **dua jenis baris**: baris credit memo (tipe CM) dan baris invoice yang dibayar.
   - Validasi: hanya boleh untuk **vendor yang sama** (`CardCode` CM = `CardCode` invoice); jumlah yang dialokasikan tidak melebihi `DocTotal - PaidToDate` masing-masing dokumen.
2. **Endpoint daftar credit memo terbuka**: `GET /cashier/invoice-payment/credit-memos?invoice_id=...` → baca SAP `PurchaseCreditNotes` dengan `CardCode` = vendor invoice itu, `DocumentStatus = bost_Open`, dan `PaidToDate < DocTotal`; kirim `DocEntry, DocNum, DocDate, DocTotal, sisa, NumAtCard` (pola on-demand seperti `cashier.bank-transactions.bilyet-options`, jangan memuat dari server).
3. **UI halaman Invoice Payment**: pada form pembayaran tambah pilihan **Sumber Pembayaran: Transfer | Credit Memo**.
   - Bila **Credit Memo** dipilih: dropdown CM dari endpoint di atas + ringkasan alokasi **preview** sebelum submit (invoice terlama lebih dulu bila CM lebih besar dari satu invoice).
   - Alokasi otomatis: urutkan invoice vendor yang open berdasarkan tanggal **paling lama**, isi sampai CM habis; invoice yang tidak terbayar penuh tetap open untuk periode berikutnya.
4. **Permission baru** `pay_invoice_with_credit_memo` (migrasi + seeder, diberikan ke role yang sekarang memegang pembayaran DDS) — tanpa izin ini pilihan CM tidak muncul dan endpoint menolak.
5. **Audit**: tetap memakai `sap_submission_logs` (`document_type = invoice_payment`), simpan nomor CM + daftar invoice yang dialokasikan pada kolom respons.
6. **Batas tegas (jangan dilanggar):** aplikasi **tidak membuat** credit note di SAP. CM harus sudah dibuat di SAP lebih dulu (mis. oleh BO); kalau belum ada, dropdown kosong + pesan jelas: *"Belum ada credit note terbuka untuk vendor ini. Pastikan credit note sudah dibuat di SAP."*

## Uji & verifikasi

- Unit/feature test: builder menghasilkan `PaymentInvoices` berisi baris CM + invoice dengan tipe & tanda yang benar; validasi vendor berbeda ditolak; alokasi urut aging; permission dijaga.
- Suite: baseline gagal hanya 3 (`BankReconciliationValidatorDashboardTest`, `ExampleTest`, `VjRejectionAlertTest`).
- **Jangan** uji ke SAP produksi tanpa izin Iwan. Preview sudah cukup untuk verifikasi lokal.

## Di luar cakupan (catat, jangan kerjakan)

- Membuat/mengubah `Purchase Credit Note` dari aplikasi.
- Perubahan pada alur DDS (rate limit, import invoice) dan pada modul utilities/installment.
