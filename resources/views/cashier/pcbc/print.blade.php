<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PCBC Document Print</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        /* Use system fonts instead of Google Fonts */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        }

        /* General Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Ensure backgrounds and colors print */
            .bg-light {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Ensure table borders print */
            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000 !important;
            }

            /* Fix table layout for printing */
            .table {
                width: 100% !important;
                margin-bottom: 1rem !important;
                border-collapse: collapse !important;
            }

            /* Ensure text colors print */
            .text-right {
                text-align: right !important;
            }

            .text-center {
                text-align: center !important;
            }

            /* Remove any page margins - maximize space */
            @page {
                margin: 0.3cm;
                size: A4;
            }

            /* Compact spacing for single page */
            .row {
                margin-bottom: 0.3rem !important;
            }

            .mt-4,
            .mt-5 {
                margin-top: 0.5rem !important;
            }

            .table {
                font-size: 0.75rem !important;
            }

            .table th,
            .table td {
                padding: 0.25rem 0.3rem !important;
            }

            h2,
            h3,
            h4,
            h5,
            h6 {
                margin-bottom: 0.2rem !important;
                margin-top: 0.2rem !important;
            }

            .card-body {
                padding: 0.5rem !important;
            }

            .card-header {
                padding: 0.4rem 0.75rem !important;
            }

            /* Hide unnecessary elements when printing */
            .no-print {
                display: none !important;
            }
        }

        /* Styles for both screen and print */
        .table th,
        .table td {
            padding: 0.5rem !important;
        }

        .money-cell {
            font-family: monospace;
        }

        /* Floating Button Styles */
        .floating-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .floating-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .floating-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            color: white;
        }

        .floating-btn:active {
            transform: translateY(-1px);
        }

        .floating-btn.print-btn {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .floating-btn.back-btn {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        /* Design Selector Styles */
        .design-selector {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .design-selector label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #333;
        }

        .design-selector select {
            width: 100%;
            padding: 8px;
            border: 2px solid #667eea;
            border-radius: 4px;
            font-size: 14px;
        }

        @media print {

            .floating-buttons,
            .design-selector {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Design Selector -->
    <div class="design-selector no-print">
        <label for="design-select"><i class="fas fa-palette"></i> Print Design:</label>
        <select id="design-select" onchange="changeDesign(this.value)">
            <option value="1" {{ request('design', '1') == '1' ? 'selected' : '' }}>Design 1 (Classic)</option>
            <option value="2" {{ request('design') == '2' ? 'selected' : '' }}>Design 2 (Modern)</option>
        </select>
    </div>

    <!-- Floating Buttons -->
    <div class="floating-buttons no-print">
        <button class="floating-btn" onclick="scrollToTop()" title="Scroll to Top">
            <i class="fas fa-arrow-up"></i>
        </button>
        <button class="floating-btn print-btn" onclick="window.print()" title="Print">
            <i class="fas fa-print"></i>
        </button>
        <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}" class="floating-btn back-btn"
            title="Back to List">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="wrapper">
        <!-- Main content -->
        <section class="invoice">
            <!-- title row -->
            <div class="row" style="margin-bottom: 0.2rem;">
                <div class="col-12">
                    <table class="table" style="border: none; margin-bottom: 0;">
                        <tr>
                            <td width="50%" style="padding: 0.2rem;">
                                <h5 class="mb-0" style="font-size: 0.9rem; margin: 0;"><strong>PT Arkananta Apta
                                        Pratista</strong></h5>
                                <small style="font-size: 0.65rem;"><strong>Project:</strong>
                                    {{ $pcbc->project }}</small>
                            </td>
                            <td width="50%" class="text-right" style="padding: 0.2rem;">
                                <h5 class="mb-0" style="font-size: 0.9rem; margin: 0;"><strong>Petty Cash Balance
                                        Control</strong></h5>
                                <small style="font-size: 0.65rem;"><strong>No. {{ $pcbc->nomor }}</strong> |
                                    <strong>Date:</strong>
                                    {{ \Carbon\Carbon::parse($pcbc->pcbc_date)->format('d-M-Y') }}</small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row" style="margin-top: 0.2rem; margin-bottom: 0.2rem;">
                <div class="col-md-6">
                    <!-- Paper Money Section -->
                    <table class="table table-bordered" style="border-width: 2px; font-size: 0.7rem;">
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center bg-light"
                                    style="padding: 0.2rem; font-size: 0.75rem;">Uang Kertas</th>
                            </tr>
                            <tr>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.7rem;">
                                    Denomination</th>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.7rem;">
                                    Quantity</th>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.7rem;">
                                    Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">100,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_100rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_100rb ?? 0) * 100000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">50,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_50rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_50rb ?? 0) * 50000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">20,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_20rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_20rb ?? 0) * 20000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">10,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_10rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_10rb ?? 0) * 10000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">5,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_5rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_5rb ?? 0) * 5000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">2,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_2rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_2rb ?? 0) * 2000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">1,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_1rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_1rb ?? 0) * 1000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">500</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_500 ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_500 ?? 0) * 500) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">100</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->kertas_100 ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->kertas_100 ?? 0) * 100) }}</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">TOTAL</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    <strong>{{ number_format(($pcbc->kertas_100rb ?? 0) + ($pcbc->kertas_50rb ?? 0) + ($pcbc->kertas_20rb ?? 0) + ($pcbc->kertas_10rb ?? 0) + ($pcbc->kertas_5rb ?? 0) + ($pcbc->kertas_2rb ?? 0) + ($pcbc->kertas_1rb ?? 0) + ($pcbc->kertas_500 ?? 0) + ($pcbc->kertas_100 ?? 0)) }}</strong>
                                </td>
                                <td class="text-right" style="padding: 0.15rem; font-size: 0.7rem;">
                                    <strong>{{ number_format(($pcbc->kertas_100rb ?? 0) * 100000 + ($pcbc->kertas_50rb ?? 0) * 50000 + ($pcbc->kertas_20rb ?? 0) * 20000 + ($pcbc->kertas_10rb ?? 0) * 10000 + ($pcbc->kertas_5rb ?? 0) * 5000 + ($pcbc->kertas_2rb ?? 0) * 2000 + ($pcbc->kertas_1rb ?? 0) * 1000 + ($pcbc->kertas_500 ?? 0) * 500 + ($pcbc->kertas_100 ?? 0) * 100) }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <!-- Coin Money Section -->
                    <table class="table table-bordered" style="border-width: 2px; font-size: 0.7rem;">
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center bg-light"
                                    style="padding: 0.2rem; font-size: 0.75rem;">Uang Logam</th>
                            </tr>
                            <tr>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.7rem;">
                                    Denomination</th>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.7rem;">
                                    Quantity</th>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.7rem;">
                                    Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">1,000</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->logam_1rb ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->logam_1rb ?? 0) * 1000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">500</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->logam_500 ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->logam_500 ?? 0) * 500) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">200</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->logam_200 ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->logam_200 ?? 0) * 200) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">100</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->logam_100 ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->logam_100 ?? 0) * 100) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">50</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->logam_50 ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->logam_50 ?? 0) * 50) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">25</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format($pcbc->logam_25 ?? 0) }}</td>
                                <td class="text-right money-cell" style="padding: 0.15rem; font-size: 0.7rem;">
                                    {{ number_format(($pcbc->logam_25 ?? 0) * 25) }}</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="text-center" style="padding: 0.15rem;"><strong
                                        style="font-size: 0.7rem;">TOTAL</strong></td>
                                <td class="text-center" style="padding: 0.15rem; font-size: 0.7rem;">
                                    <strong>{{ number_format(($pcbc->logam_1rb ?? 0) + ($pcbc->logam_500 ?? 0) + ($pcbc->logam_200 ?? 0) + ($pcbc->logam_100 ?? 0) + ($pcbc->logam_50 ?? 0) + ($pcbc->logam_25 ?? 0)) }}</strong>
                                </td>
                                <td class="text-right" style="padding: 0.15rem; font-size: 0.7rem;">
                                    <strong>{{ number_format(($pcbc->logam_1rb ?? 0) * 1000 + ($pcbc->logam_500 ?? 0) * 500 + ($pcbc->logam_200 ?? 0) * 200 + ($pcbc->logam_100 ?? 0) * 100 + ($pcbc->logam_50 ?? 0) * 50 + ($pcbc->logam_25 ?? 0) * 25) }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row" style="margin-top: 0.2rem; margin-bottom: 0.2rem;">
                <div class="col-12">
                    <table class="table table-bordered mb-0" style="border-width: 2px; font-size: 0.65rem;">
                        <tr>
                            <th class="bg-light text-right" width="12%" style="padding: 0.2rem;">System:</th>
                            <td class="text-right money-cell font-weight-bold" width="18%"
                                style="padding: 0.2rem;">
                                Rp {{ number_format($pcbc->system_amount ?? 0, 2) }}
                            </td>
                            <th class="bg-light text-right" width="12%" style="padding: 0.2rem;">Physical:</th>
                            <td class="text-right money-cell font-weight-bold text-success" width="18%"
                                style="padding: 0.2rem;">
                                Rp {{ number_format($pcbc->fisik_amount ?? 0, 2) }}
                            </td>
                            <th class="bg-light text-right" width="12%" style="padding: 0.2rem;">SAP:</th>
                            <td class="text-right money-cell font-weight-bold" width="18%"
                                style="padding: 0.2rem;">
                                Rp {{ number_format($pcbc->sap_amount ?? 0, 2) }}
                            </td>
                            <td class="text-center" width="10%" style="padding: 0.2rem;">
                                <small style="font-size: 0.6rem;"><em>({{ $terbilang }})</em></small>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-right" style="padding: 0.2rem;">Sys Var:</th>
                            <td class="text-right money-cell font-weight-bold 
                                @if (abs(($pcbc->system_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) < 0.01) text-success 
                                @elseif(abs(($pcbc->system_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) <= 1000) text-warning 
                                @else text-danger @endif"
                                style="padding: 0.2rem;">
                                Rp {{ number_format(($pcbc->system_amount ?? 0) - ($pcbc->fisik_amount ?? 0), 2) }}
                            </td>
                            <th class="bg-light text-right" style="padding: 0.2rem;">SAP Var:</th>
                            <td class="text-right money-cell font-weight-bold 
                                @if (abs(($pcbc->sap_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) < 0.01) text-success 
                                @elseif(abs(($pcbc->sap_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) <= 1000) text-warning 
                                @else text-danger @endif"
                                style="padding: 0.2rem;">
                                Rp {{ number_format(($pcbc->sap_amount ?? 0) - ($pcbc->fisik_amount ?? 0), 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="row" style="margin-top: 0.2rem; margin-bottom: 0.2rem;">
                <div class="col-12">
                    <table class="table" style="border: none; margin-bottom: 0; font-size: 0.65rem;">
                        <thead>
                            <tr>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.65rem;">
                                    Prepared by</th>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.65rem;">
                                    Checked by</th>
                                <th class="text-center" width="33.33%" style="padding: 0.2rem; font-size: 0.65rem;">
                                    Approved by</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center" height="35"
                                    style="border-bottom: 1px solid #000; padding: 0.2rem;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 3px; height: 25px;">
                                    </div>
                                </td>
                                <td class="text-center" style="border-bottom: 1px solid #000; padding: 0.2rem;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 3px; height: 25px;">
                                    </div>
                                </td>
                                <td class="text-center" style="border-bottom: 1px solid #000; padding: 0.2rem;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 3px; height: 25px;">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center" style="padding: 0.2rem;">
                                    <strong
                                        style="font-size: 0.65rem;">({{ $pcbc->createdBy->name ?? 'N/A' }})</strong><br>
                                    <small style="font-size: 0.6rem;">Cashier</small>
                                </td>
                                <td class="text-center" style="padding: 0.2rem;">
                                    <strong
                                        style="font-size: 0.65rem;">({{ $pcbc->pemeriksa1 ?? 'N/A' }})</strong><br>
                                    <small style="font-size: 0.6rem;">Checker</small>
                                </td>
                                <td class="text-center" style="padding: 0.2rem;">
                                    <strong
                                        style="font-size: 0.65rem;">({{ $pcbc->approved_by ?? 'N/A' }})</strong><br>
                                    <small style="font-size: 0.6rem;">Approver</small>
                                </td>
                            </tr>
                            @if ($pcbc->pemeriksa2)
                                <tr>
                                    <td colspan="3" class="text-center" style="padding: 0.2rem;">
                                        <small style="font-size: 0.6rem;"><strong>Second Checker:</strong>
                                            {{ $pcbc->pemeriksa2 }}</small>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Print Footer -->
            <div class="row" style="margin-top: 0.2rem; page-break-inside: avoid;">
                <div class="col-12">
                    <hr style="border-top: 1px solid #000; margin: 0.2rem 0;">
                    <div class="text-center" style="font-size: 0.6rem;">
                        <small>
                            <strong>Printed:</strong> {{ \Carbon\Carbon::now()->format('d-M-Y H:i') }} |
                            <strong>By:</strong> {{ auth()->user()->name ?? 'System' }} |
                            <strong>ID:</strong> {{ $pcbc->id }} |
                            <em>Computer-generated</em>
                        </small>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="no-print">
        <script>
            // Auto-print can be disabled by commenting out the line below
            // window.addEventListener("load", window.print());

            // Scroll to top function
            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // Change design function
            function changeDesign(design) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('design', design);
                window.location.href = currentUrl.toString();
            }

            // Show/hide floating button based on scroll position
            window.addEventListener('scroll', function() {
                const floatingButtons = document.querySelector('.floating-buttons');
                if (window.pageYOffset > 300) {
                    floatingButtons.style.opacity = '1';
                    floatingButtons.style.visibility = 'visible';
                } else {
                    floatingButtons.style.opacity = '0.7';
                }
            });

            // Initialize button visibility
            document.addEventListener('DOMContentLoaded', function() {
                const floatingButtons = document.querySelector('.floating-buttons');
                if (window.pageYOffset > 300) {
                    floatingButtons.style.opacity = '1';
                    floatingButtons.style.visibility = 'visible';
                } else {
                    floatingButtons.style.opacity = '0.7';
                }
            });
        </script>
    </div>
</body>

</html>
