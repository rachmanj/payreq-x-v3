# Spec — OP untuk invoice yang mengandung PPh23 (withholding tax)

Pemicu: laporan Rifka (15 Sep 2026) — invoice Telkom `4978243000061202609` dan invoice Klinik Permata Husada mengandung PPh23; saat mau OP di aplikasi, nominal tidak sama dengan total invoice sehingga app menganggapnya pembayaran sebagian. Grill Q1–Q5 disetujui Iwan; uji op nyata disetujui pada invoice Astragraphia (DocNum 267007511).

## Fakta hasil penelusuran (SAP prod)
- AP invoice Telkom Sep 2026: DocNum `267007396` (DocEntry 28787), DocTotal `16.872.000` (DPP 15.200.000 + PPN 1.672.000), status Open, PaidToDate 0.
- AP invoice itu sudah memuat PPh23: `WithholdingTaxDataCollection` → **WTCode `1019` (2%), WTAmount 304.000**, GLAccount `21701005`, `Status=bost_Open`, `AppliedWTAmount=0`.
- Pola pelunasan Telkom bulan-bulan sebelumnya (manual di SAP, bukan dari app): kas `16.568.000` + Cr PPh23 `304.000` + Dr Hutang Usaha `16.872.000` → invoice lunas penuh.
- Dari 16 OP terakhir yang dibuat aplikasi: **0** menyentuh invoice ber-WTax → belum ada preseden di app.
- Uji draft di SAP: payload dengan `WithholdingTaxDataCollection` **diterima** (201), tetapi draft tidak menghitung sisi GL (DocTotal 0) → tidak bisa dipakai membuktikan posting nyata.

## Keputusan (grill)
- **Q1**: PPh23 dibaca **otomatis** dari AP invoice di SAP (bukan input manual).
- **Q2**: Kasir mengisi **netto yang keluar dari bank**; app menambahkan PPh23 supaya invoice lunas; modal menampilkan rincian Bruto − PPh23 = Netto.
- **Q3**: Dipasang di **`SapVendorPaymentBuilder`** → berlaku semua jalur OP (invoice-payment DDS, utilities, BPJS, angsuran), dan **hanya aktif bila AP invoice punya WTax open** → invoice tanpa PPh23 tidak berubah.
- **Q4**: Uji dengan draft SAP (sudah dilakukan) + **1 OP nyata kecil lalu di-cancel** — invoice Astragraphia `267007511` (Rp 1.831.500, PPh23 Rp 33.000).
- **Q5**: Tampilkan peringatan bila invoice ber-WTax tetapi OP dibuat tanpa pemotongan.

## Aturan yang diimplementasikan
1. `SapVendorPaymentBuilder::openWithholdingTax(array $apInvoice)` → `['total' => float, 'entries' => [['WTCode' => ..., 'WTAmount' => ...]]]` dari entri ber-`Status=bost_Open`.
2. Bila ada WTax open: `paymentAmount` yang diisi kasir = **kas (netto)**; payload:
   - `PaymentInvoices[0].SumApplied` = **netto + PPh23 (= sisa invoice bruto)**
   - `WithholdingTaxDataCollection` = entri WTax (WTCode + WTAmount)
   - `CashSum`/`TransferSum` = **netto**
3. Validasi: bila ada WTax open, pembayaran **harus melunasi sisa invoice** (`netto + PPh23 = sisa`, toleransi 0.5) — kalau tidak, tolak dengan pesan jelas (mencegah invoice menggantung). Bila tidak ada WTax, perilaku lama (boleh partial) tetap.
4. Preview menampilkan **Bruto / PPh23 / Netto** + kode WTax; default nilai input = **netto**; peringatan bila ada WTax open.
5. Invoice tanpa WTax: payload & validasi **sama seperti sebelumnya** (ada test regresi).

## Cara uji (nyata, disetujui Iwan)
1. Jalankan OP untuk AP invoice Astragraphia `267007511` dengan payload baru (netto 1.798.500 + PPh23 33.000).
2. Verifikasi di SAP: invoice **PaidToDate = 1.831.500 & closed**, WTax `AppliedWTAmount = 33.000`, dan baris GL OP = kas −1.798.500, PPh23 −33.000 (21701005), Hutang Usaha +1.831.500.
3. **Cancel OP** tersebut, verifikasi invoice kembali Open dan WTax kembali unapplied (tidak menyisakan jejak).
4. Laporkan hasil + baru dipakai luas (dan email pemberitahuan ke Rifka/Prana/Elma dikirim setelah fitur live).

## Di luar cakupan
- Tidak mengubah AP invoice/modul pajak lain; tidak mengubah data lama (hasil sisiran: 0 invoice menggantung).
