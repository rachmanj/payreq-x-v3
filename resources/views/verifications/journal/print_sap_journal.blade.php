<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JOURNAL VOUCHER | {{ $vj->sap_journal_no }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 16px 20px;
            color: #111;
        }

        .header {
            width: 100%;
            margin-bottom: 16px;
        }

        .header-row {
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-start;
            gap: 16px;
            width: 100%;
        }

        .logo-cell {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex: 0 0 auto;
            min-width: 0;
            max-width: 280px;
        }

        .logo-meta {
            margin-top: 8px;
            text-align: left;
            width: 100%;
        }

        .logo-meta .meta-line {
            margin: 0 0 3px 0;
            font-size: 13px;
        }

        .company-cell {
            flex: 1 1 auto;
            min-width: 0;
            padding-left: 8px;
        }

        .meta-cell {
            flex: 0 0 260px;
            max-width: 260px;
            text-align: right;
            min-width: 0;
            align-self: center;
        }

        .logo-cell img {
            max-height: 50px;
            max-width: 200px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
            object-position: left top;
        }

        .company-name {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .company-addr {
            margin: 0;
            line-height: 1.35;
        }

        .jv-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .meta-line {
            margin: 0 0 3px 0;
        }

        .meta-label {
            font-weight: bold;
        }

        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.lines th,
        table.lines td {
            border: 1px solid #333;
            padding: 6px 5px;
            vertical-align: top;
        }

        table.lines thead th {
            background: #eee;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
        }

        table.lines thead tr.jv-page-header td {
            border: none;
            padding: 0 0 12px 0;
            vertical-align: top;
        }

        table.lines thead {
            display: table-header-group;
        }

        table.lines td.num,
        table.lines th.num {
            text-align: right;
            white-space: nowrap;
        }

        .totals-row td {
            border: none;
            padding-top: 8px;
        }

        .totals-inner {
            margin-left: auto;
            width: 280px;
        }

        .totals-inner td {
            border: none;
            padding: 2px 4px;
            text-align: right;
        }

        .descr {
            margin: 14px 0 10px 0;
        }

        .signatures {
            margin-top: 36px;
            width: 100%;
        }

        .signatures-row {
            display: table;
            width: 100%;
        }

        .signature-col {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 0 8px;
        }

        .signature-title {
            border-top: 1px solid #333;
            padding-top: 4px;
            font-weight: bold;
        }

        @media print {
            body {
                padding: 8px 12px;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    @php
        $totalDebit = $vjDetails->where('debit_credit', 'debit')->sum('amount');
        $totalCredit = $vjDetails->where('debit_credit', 'credit')->sum('amount');
        $sapDate = $vj->sap_posting_date ? \Carbon\Carbon::parse($vj->sap_posting_date)->format('d-M-Y') : '—';
    @endphp

    <table class="lines">
        <thead>
            <tr class="jv-page-header">
                <td colspan="6">
                    <div class="header">
                        <div class="header-row">
                            <div class="header-cell logo-cell">
                                <img src="{{ asset('ark_logo.jpeg') }}" alt="Logo">
                                <div class="logo-meta">
                                    <p class="meta-line"><span class="meta-label">Voucher No</span>: {{ $vj->sap_journal_no }}</p>
                                    <p class="meta-line"><span class="meta-label">Date</span>: {{ $sapDate }}</p>
                                    <p class="meta-line"><span class="meta-label">Doc Curr</span>: IDR</p>
                                </div>
                            </div>
                            <div class="header-cell company-cell">
                                <p class="company-name">PT. ARKANANTA APTA PRATISTA</p>
                                <p class="company-addr">Jl. MT Haryono No.131-133</p>
                            </div>
                            <div class="header-cell meta-cell">
                                <p class="jv-title">JOURNAL VOUCHER</p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th style="width: 88px;">Account</th>
                <th>Account Name</th>
                <th style="width: 72px;">Project</th>
                <th class="num" style="width: 100px;">Debit</th>
                <th class="num" style="width: 100px;">Credit</th>
                <th>Line Memo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vjDetails as $item)
                <tr>
                    <td>{{ $item->account_code }}</td>
                    <td>{{ $item->account_name }}</td>
                    <td>{{ $item->project }}</td>
                    @if ($item->debit_credit === 'debit')
                        <td class="num">{{ number_format((float) $item->amount, 2, '.', ',') }}</td>
                        <td class="num">0.00</td>
                    @else
                        <td class="num">0.00</td>
                        <td class="num">{{ number_format((float) $item->amount, 2, '.', ',') }}</td>
                    @endif
                    <td>
                        {{ $item->description }}
                        @if ($item->realization_no)
                            <div style="margin-top:2px;font-size:10px;color:#444;">{{ $item->realization_no }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="6">
                    <table class="totals-inner">
                        <tr>
                            <td><strong>Debits</strong></td>
                            <td style="font-weight:bold;">{{ number_format((float) $totalDebit, 2, '.', ',') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Credits</strong></td>
                            <td style="font-weight:bold;">{{ number_format((float) $totalCredit, 2, '.', ',') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="descr"><strong>Description:</strong> Verification Journal: {{ $vj->nomor }}</p>

    <p><strong>Says:</strong> {{ $amountInWords }}</p>

    <div class="signatures">
        <div class="signatures-row">
            <div class="signature-col">
                <div style="height:52px;"></div>
                <div class="signature-title">Prepared By</div>
                <div style="margin-top:10px;"><strong>Date:</strong></div>
            </div>
            <div class="signature-col">
                <div style="height:52px;"></div>
                <div class="signature-title">Reviewed By</div>
                <div style="margin-top:10px;"><strong>Date:</strong></div>
            </div>
            <div class="signature-col">
                <div style="height:52px;">
                    @if (in_array($vj->project, ['000H', 'APS'], true))
                        <img src="{{ asset('sign_rj2.png') }}" alt="Approved signature"
                            style="max-height:52px; max-width:100%; object-fit:contain; display:block; margin:0 auto;">
                    @endif
                </div>
                <div class="signature-title">Approved By</div>
                <div style="margin-top:10px;"><strong>Date:</strong></div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>

</html>
