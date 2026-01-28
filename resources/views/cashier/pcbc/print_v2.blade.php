<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PCBC Document Print - Design 2</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        /* General Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .bg-light {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000 !important;
            }

            .table {
                width: 100% !important;
                margin-bottom: 1rem !important;
                border-collapse: collapse !important;
            }

            .text-right {
                text-align: right !important;
            }

            .text-center {
                text-align: center !important;
            }

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
                margin-top: 0.3rem !important;
            }

            .table {
                font-size: 0.7rem !important;
            }

            .table th,
            .table td {
                padding: 0.2rem 0.25rem !important;
            }

            h2,
            h3,
            h4,
            h5,
            h6 {
                margin-bottom: 0.2rem !important;
                margin-top: 0.2rem !important;
            }

            .header-section {
                padding: 10px !important;
                margin-bottom: 0.3rem !important;
            }

            .denomination-card {
                margin-bottom: 0.3rem !important;
            }

            .summary-card {
                padding: 10px !important;
                margin: 0.3rem 0 !important;
            }

            .signature-section {
                margin-top: 0.3rem !important;
                padding: 10px !important;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Modern Design Styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .header-section h2 {
            margin: 0;
            font-weight: 600;
        }

        .header-section h4 {
            margin: 5px 0;
            opacity: 0.9;
        }

        .denomination-card {
            border: 2px solid #667eea;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .denomination-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            font-weight: 600;
            text-align: center;
        }

        .summary-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 6px;
            padding: 8px;
            margin: 0.2rem 0;
            font-size: 0.7rem;
        }

        .summary-card h4 {
            margin: 0 0 5px 0;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 0.7rem;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 500;
            font-size: 0.7rem;
        }

        .summary-value {
            font-weight: 700;
            font-size: 0.75rem;
        }

        .variance-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .variance-success {
            background-color: #28a745;
            color: white;
        }

        .variance-warning {
            background-color: #ffc107;
            color: #000;
        }

        .variance-danger {
            background-color: #dc3545;
            color: white;
        }

        .signature-section {
            margin-top: 0.2rem;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }

        .signature-box {
            text-align: center;
            padding: 5px;
            font-size: 0.65rem;
        }

        .signature-line {
            border-bottom: 2px solid #000;
            height: 30px;
            margin-bottom: 5px;
        }

        .money-cell {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .footer-info {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #667eea;
            text-align: center;
            color: #6c757d;
            font-size: 0.85em;
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
            <option value="1" {{ request('design') == '1' ? 'selected' : '' }}>Design 1 (Classic)</option>
            <option value="2" {{ request('design', '2') == '2' ? 'selected' : '' }}>Design 2 (Modern)</option>
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
        <section class="invoice">
            <!-- Modern Header -->
            <div class="header-section">
                <div class="row">
                    <div class="col-md-6">
                        <h2><i class="fas fa-building"></i> PT Arkananta Apta Pratista</h2>
                        <h4><i class="fas fa-project-diagram"></i> Project: {{ $pcbc->project }}</h4>
                    </div>
                    <div class="col-md-6 text-right">
                        <h2><i class="fas fa-cash-register"></i> Petty Cash Balance Control</h2>
                        <h4><strong>No. {{ $pcbc->nomor }}</strong></h4>
                        <h4><i class="fas fa-calendar"></i>
                            {{ \Carbon\Carbon::parse($pcbc->pcbc_date)->format('d F Y') }}</h4>
                    </div>
                </div>
            </div>

            <!-- Denomination Tables -->
            <div class="row" style="margin-bottom: 0.2rem;">
                <div class="col-md-6">
                    <div class="denomination-card" style="margin-bottom: 0.2rem;">
                        <div class="denomination-header" style="padding: 6px; font-size: 0.75rem;">
                            <i class="fas fa-money-bill-wave"></i> Uang Kertas (Paper Money)
                        </div>
                        <table class="table table-bordered mb-0" style="font-size: 0.65rem;">
                            <thead>
                                <tr>
                                    <th class="text-center" width="40%"
                                        style="padding: 0.15rem; font-size: 0.65rem;">Denomination</th>
                                    <th class="text-center" width="30%"
                                        style="padding: 0.15rem; font-size: 0.65rem;">Quantity</th>
                                    <th class="text-center" width="30%"
                                        style="padding: 0.15rem; font-size: 0.65rem;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 100,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_100rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_100rb ?? 0) * 100000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 50,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_50rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_50rb ?? 0) * 50000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 20,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_20rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_20rb ?? 0) * 20000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 10,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_10rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_10rb ?? 0) * 10000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 5,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_5rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_5rb ?? 0) * 5000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 2,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_2rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_2rb ?? 0) * 2000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 1,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_1rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_1rb ?? 0) * 1000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 500</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_500 ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_500 ?? 0) * 500) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 100</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->kertas_100 ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->kertas_100 ?? 0) * 100) }}</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">TOTAL</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        <strong>{{ number_format(($pcbc->kertas_100rb ?? 0) + ($pcbc->kertas_50rb ?? 0) + ($pcbc->kertas_20rb ?? 0) + ($pcbc->kertas_10rb ?? 0) + ($pcbc->kertas_5rb ?? 0) + ($pcbc->kertas_2rb ?? 0) + ($pcbc->kertas_1rb ?? 0) + ($pcbc->kertas_500 ?? 0) + ($pcbc->kertas_100 ?? 0)) }}</strong>
                                    </td>
                                    <td class="text-right" style="padding: 0.1rem; font-size: 0.65rem;">
                                        <strong class="money-cell">Rp
                                            {{ number_format(($pcbc->kertas_100rb ?? 0) * 100000 + ($pcbc->kertas_50rb ?? 0) * 50000 + ($pcbc->kertas_20rb ?? 0) * 20000 + ($pcbc->kertas_10rb ?? 0) * 10000 + ($pcbc->kertas_5rb ?? 0) * 5000 + ($pcbc->kertas_2rb ?? 0) * 2000 + ($pcbc->kertas_1rb ?? 0) * 1000 + ($pcbc->kertas_500 ?? 0) * 500 + ($pcbc->kertas_100 ?? 0) * 100) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="denomination-card" style="margin-bottom: 0.2rem;">
                        <div class="denomination-header" style="padding: 6px; font-size: 0.75rem;">
                            <i class="fas fa-coins"></i> Uang Logam (Coin Money)
                        </div>
                        <table class="table table-bordered mb-0" style="font-size: 0.65rem;">
                            <thead>
                                <tr>
                                    <th class="text-center" width="40%"
                                        style="padding: 0.15rem; font-size: 0.65rem;">Denomination</th>
                                    <th class="text-center" width="30%"
                                        style="padding: 0.15rem; font-size: 0.65rem;">Quantity</th>
                                    <th class="text-center" width="30%"
                                        style="padding: 0.15rem; font-size: 0.65rem;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 1,000</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->logam_1rb ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->logam_1rb ?? 0) * 1000) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 500</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->logam_500 ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->logam_500 ?? 0) * 500) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 200</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->logam_200 ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->logam_200 ?? 0) * 200) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 100</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->logam_100 ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->logam_100 ?? 0) * 100) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 50</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->logam_50 ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->logam_50 ?? 0) * 50) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">Rp 25</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        {{ number_format($pcbc->logam_25 ?? 0) }}</td>
                                    <td class="text-right money-cell" style="padding: 0.1rem; font-size: 0.65rem;">Rp
                                        {{ number_format(($pcbc->logam_25 ?? 0) * 25) }}</td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-center" style="padding: 0.1rem;"><strong
                                            style="font-size: 0.65rem;">TOTAL</strong></td>
                                    <td class="text-center" style="padding: 0.1rem; font-size: 0.65rem;">
                                        <strong>{{ number_format(($pcbc->logam_1rb ?? 0) + ($pcbc->logam_500 ?? 0) + ($pcbc->logam_200 ?? 0) + ($pcbc->logam_100 ?? 0) + ($pcbc->logam_50 ?? 0) + ($pcbc->logam_25 ?? 0)) }}</strong>
                                    </td>
                                    <td class="text-right" style="padding: 0.1rem; font-size: 0.65rem;">
                                        <strong class="money-cell">Rp
                                            {{ number_format(($pcbc->logam_1rb ?? 0) * 1000 + ($pcbc->logam_500 ?? 0) * 500 + ($pcbc->logam_200 ?? 0) * 200 + ($pcbc->logam_100 ?? 0) * 100 + ($pcbc->logam_50 ?? 0) * 50 + ($pcbc->logam_25 ?? 0) * 25) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modern Summary Card -->
            <div class="summary-card" style="padding: 6px; margin: 0.2rem 0;">
                <h5 style="font-size: 0.75rem; margin: 0 0 3px 0;"><i class="fas fa-calculator"></i> Amount Summary
                </h5>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; font-size: 0.65rem;">
                    <span style="white-space: nowrap;"><strong>System:</strong> Rp
                        {{ number_format($pcbc->system_amount ?? 0, 2) }}</span>
                    <span style="white-space: nowrap;"><strong>Physical:</strong> Rp
                        {{ number_format($pcbc->fisik_amount ?? 0, 2) }}</span>
                    <span style="white-space: nowrap;"><strong>SAP:</strong> Rp
                        {{ number_format($pcbc->sap_amount ?? 0, 2) }}</span>
                    <span style="white-space: nowrap;">
                        <strong>Sys Var:</strong>
                        @php
                            $systemVariance = ($pcbc->system_amount ?? 0) - ($pcbc->fisik_amount ?? 0);
                            $varianceClass =
                                abs($systemVariance) < 0.01
                                    ? 'variance-success'
                                    : (abs($systemVariance) <= 1000
                                        ? 'variance-warning'
                                        : 'variance-danger');
                        @endphp
                        <span class="variance-badge {{ $varianceClass }}"
                            style="font-size: 0.65rem; padding: 2px 8px;">
                            Rp {{ number_format($systemVariance, 2) }}
                        </span>
                    </span>
                    @if ($pcbc->sap_amount)
                        <span style="white-space: nowrap;">
                            <strong>SAP Var:</strong>
                            @php
                                $sapVariance = ($pcbc->sap_amount ?? 0) - ($pcbc->fisik_amount ?? 0);
                                $sapVarianceClass =
                                    abs($sapVariance) < 0.01
                                        ? 'variance-success'
                                        : (abs($sapVariance) <= 1000
                                            ? 'variance-warning'
                                            : 'variance-danger');
                            @endphp
                            <span class="variance-badge {{ $sapVarianceClass }}"
                                style="font-size: 0.65rem; padding: 2px 8px;">
                                Rp {{ number_format($sapVariance, 2) }}
                            </span>
                        </span>
                    @endif
                    <span style="white-space: nowrap; flex-basis: 100%; margin-top: 2px;"><strong>Words:</strong>
                        <em>{{ $terbilang }}</em></span>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="signature-section" style="padding: 6px; margin-top: 0.2rem;">
                <div style="display: flex; justify-content: space-around; align-items: flex-start; gap: 10px;">
                    <div class="signature-box" style="padding: 3px; flex: 1; text-align: center;">
                        <div class="signature-line" style="height: 25px; margin-bottom: 3px;"></div>
                        <strong style="font-size: 0.65rem;">{{ $pcbc->createdBy->name ?? 'N/A' }}</strong><br>
                        <small style="font-size: 0.6rem;">Cashier</small>
                    </div>
                    <div class="signature-box" style="padding: 3px; flex: 1; text-align: center;">
                        <div class="signature-line" style="height: 25px; margin-bottom: 3px;"></div>
                        <strong style="font-size: 0.65rem;">{{ $pcbc->pemeriksa1 ?? 'N/A' }}</strong><br>
                        <small style="font-size: 0.6rem;">Checker</small>
                    </div>
                    <div class="signature-box" style="padding: 3px; flex: 1; text-align: center;">
                        <div class="signature-line" style="height: 25px; margin-bottom: 3px;"></div>
                        <strong style="font-size: 0.65rem;">{{ $pcbc->approved_by ?? 'N/A' }}</strong><br>
                        <small style="font-size: 0.6rem;">Approver</small>
                    </div>
                </div>
                @if ($pcbc->pemeriksa2)
                    <div style="margin-top: 0.2rem; text-align: center;">
                        <small style="font-size: 0.6rem;"><strong>Second Checker:</strong>
                            {{ $pcbc->pemeriksa2 }}</small>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer-info" style="margin-top: 0.2rem; padding-top: 5px; font-size: 0.55rem;">
                <strong>Printed:</strong> {{ \Carbon\Carbon::now()->format('d F Y H:i') }} |
                <strong>By:</strong> {{ auth()->user()->name ?? 'System' }} |
                <strong>ID:</strong> {{ $pcbc->id }} |
                <em>Computer-generated</em>
            </div>
        </section>
    </div>

    <div class="no-print">
        <script>
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
