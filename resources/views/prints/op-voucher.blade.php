<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cash Bank Voucher Out</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 0;
            padding: 24px;
            background: #f5f5f5;
        }
        .no-print {
            margin-bottom: 16px;
        }
        .no-print button {
            background: #2563eb;
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 14px;
        }
        #voucher-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 18mm 16mm;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        .company-name {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0;
        }
        .company-address {
            margin: 4px 0 0;
            font-size: 11px;
        }
        .doc-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 18px 0 14px;
            letter-spacing: 0.5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
            margin-bottom: 16px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 8px;
        }
        .info-label {
            font-weight: 700;
        }
        table.voucher-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.voucher-table th,
        table.voucher-table td {
            border: 1px solid #222;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.voucher-table th {
            background: #f3f4f6;
            text-align: left;
        }
        .text-right { text-align: right; }
        .say-row,
        .remarks-row {
            margin-top: 12px;
        }
        .signatures {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 28px;
        }
        .signature-box {
            text-align: center;
            min-height: 110px;
        }
        .signature-title {
            font-weight: 700;
            margin-bottom: 8px;
        }
        .signature-image {
            height: 48px;
            object-fit: contain;
        }
        .signature-image-print {
            width: 32mm;
            height: auto;
            object-fit: contain;
        }
        .signature-name {
            margin-top: 6px;
            font-weight: 600;
        }
        .signature-date {
            margin-top: 28px;
            font-size: 11px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            #voucher-sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div id="voucher-sheet">
        <div>
            <p class="company-name">PT. ARKANANTA APTA PRATISTA</p>
            <p class="company-address">Jl. MT Haryono No.131-133</p>
        </div>

        <div class="doc-title">Cash Bank Voucher Out</div>

        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Payment For</span>
                <span>{{ $voucher['header']['payment_for'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Voucher No</span>
                <span>{{ $voucher['header']['voucher_no'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Project Code</span>
                <span>{{ $voucher['header']['project'] ?: '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Voucher Date</span>
                <span>{{ $voucher['header']['voucher_date'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method</span>
                <span>{{ $voucher['header']['payment_method'] ?: '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Currency</span>
                <span>{{ $voucher['header']['currency'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Bank Acc No</span>
                <span>{{ $voucher['header']['bank_acc_no'] ?: '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Check/BG No</span>
                <span>{{ $voucher['header']['check_bg_no'] ?: '-' }}</span>
            </div>
        </div>

        <table class="voucher-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Account</th>
                    <th>Description</th>
                    <th style="width: 15%;" class="text-right">Debit</th>
                    <th style="width: 15%;" class="text-right">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($voucher['lines'] as $line)
                    <tr>
                        <td>{{ $line['account'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td class="text-right">{{ number_format($line['debit'], 2) }}</td>
                        <td class="text-right">{{ number_format($line['credit'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <th colspan="2" class="text-right">TOTAL</th>
                    <th class="text-right">{{ number_format($voucher['totals']['debit'], 2) }}</th>
                    <th class="text-right">{{ number_format($voucher['totals']['credit'], 2) }}</th>
                </tr>
            </tbody>
        </table>

        <div class="say-row">
            <strong>Say:</strong> {{ $voucher['say'] }}
        </div>

        <div class="remarks-row">
            <strong>Remarks:</strong> {{ $voucher['header']['remarks'] ?: '-' }}
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-title">Checked By</div>
                <img src="{{ asset($voucher['signatures']['checked_by_signature']) }}" alt="Checked By" class="signature-image-print">
                <div class="signature-date">Date: _______________</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Reviewed By</div>
                <img src="{{ asset($voucher['signatures']['reviewed_by_signature']) }}" alt="Reviewed By" class="signature-image">
                <div class="signature-name">{{ $voucher['signatures']['reviewed_by_name'] }}</div>
                <div class="signature-date">Date: _______________</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Paid By</div>
                <img src="{{ asset($voucher['signatures']['paid_by_signature']) }}" alt="Paid By" class="signature-image-print">
                <div class="signature-name">{{ $voucher['signatures']['paid_by_name'] ?: '-' }}</div>
                <div class="signature-date">Date: _______________</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Received By</div>
                <div class="signature-date">Date: _______________</div>
            </div>
        </div>
    </div>
</body>
</html>
