<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PCBC Document Print - Design 3</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        /* Use system fonts */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

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

            .bg-header {
                background-color: #d3d3d3 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }


            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000 !important;
            }

            .table {
                width: 100% !important;
                margin-bottom: 0.5rem !important;
                border-collapse: collapse !important;
            }

            .no-print {
                display: none !important;
            }
        }

        @page {
            margin: 0.5cm;
            size: A4;
        }

        /* ARKA Logo Style */
        .arka-logo {
            display: inline-block;
            max-height: 60px;
            margin-right: 15px;
        }

        .arka-logo img {
            max-height: 60px;
            width: auto;
        }

        /* Section Headers */
        .section-header {
            background-color: #d3d3d3;
            padding: 8px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        /* Checkmark Icon */
        .checkmark {
            color: #28a745;
            font-size: 0.9rem;
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
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

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
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .design-selector select {
            width: 100%;
            padding: 8px;
            border: 2px solid #667eea;
            border-radius: 4px;
            font-size: 14px;
        }

        .table th,
        .table td {
            padding: 0.4rem 0.5rem;
            font-size: 0.85rem;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .money-cell {
            font-family: monospace;
        }
    </style>
</head>

<body>
    <!-- Design Selector -->
    <div class="design-selector no-print">
        <label for="design-select"><i class="fas fa-palette"></i> Print Design:</label>
        <select id="design-select" onchange="changeDesign(this.value)">
            <option value="1" {{ request('design') == '1' ? 'selected' : '' }}>Design 1 (Classic)</option>
            <option value="2" {{ request('design') == '2' ? 'selected' : '' }}>Design 2 (Modern)</option>
            <option value="3" {{ request('design', '3') == '3' ? 'selected' : '' }}>Design 3 (ARKA Format)</option>
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
            <!-- Header Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                            <div class="arka-logo">
                                <img src="{{ asset('ark_logo.jpeg') }}" alt="ARKA Logo">
                            </div>
                            <div style="margin-left: 15px;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: bold;">PETTY CASH BALANCE CONTROL
                                    (PCBC)</h3>
                                <div style="margin-top: 5px; font-size: 0.95rem;">
                                    <strong>Project: {{ $pcbc->project }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                        <div>
                            <strong>Date:</strong> {{ \Carbon\Carbon::parse($pcbc->pcbc_date)->format('d-M-y') }}
                        </div>
                        <div>
                            <strong>TO: ACC HO BALIKPAPAN</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section A: System Balance -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="section-header">A. SYSTEM BALANCE</div>
                    <table class="table table-bordered" style="margin-top: 0;">
                        <tr>
                            <td width="70%">System Petty Cash Balance:</td>
                            <td width="30%" class="text-right">
                                <strong>AMOUNT (dr)</strong>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="text-right money-cell">
                                {{ number_format($systemBalance ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Section B: Denominations -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="section-header">B. DENOMINATIONS</div>

                    <!-- Banknote Table -->
                    <table class="table table-bordered" style="margin-top: 0; margin-bottom: 10px;">
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center bg-light">Banknote</th>
                            </tr>
                            <tr>
                                <th class="text-center" width="40%">KOPURS</th>
                                <th class="text-center" width="30%">NUMBER OF UNITS</th>
                                <th class="text-center" width="30%">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $banknoteTotal = 0;
                                $denominations = [
                                    ['field' => 'kertas_100rb', 'value' => 100000, 'label' => 'Rp. 100.000,-'],
                                    ['field' => 'kertas_50rb', 'value' => 50000, 'label' => 'Rp. 50.000,-'],
                                    ['field' => 'kertas_20rb', 'value' => 20000, 'label' => 'Rp. 20.000,-'],
                                    ['field' => 'kertas_10rb', 'value' => 10000, 'label' => 'Rp. 10.000,-'],
                                    ['field' => 'kertas_5rb', 'value' => 5000, 'label' => 'Rp. 5.000,-'],
                                    ['field' => 'kertas_2rb', 'value' => 2000, 'label' => 'Rp. 2.000,-'],
                                    ['field' => 'kertas_1rb', 'value' => 1000, 'label' => 'Rp. 1.000,-'],
                                    ['field' => 'kertas_500', 'value' => 500, 'label' => 'Rp. 500,-'],
                                    ['field' => 'kertas_100', 'value' => 100, 'label' => 'Rp. 100,-'],
                                ];
                            @endphp
                            @foreach ($denominations as $denom)
                                @php
                                    $qty = $pcbc->{$denom['field']} ?? 0;
                                    $amount = $qty * $denom['value'];
                                    $banknoteTotal += $amount;
                                @endphp
                                <tr>
                                    <td>{{ $denom['label'] }}</td>
                                    <td class="text-center">{{ $qty > 0 ? number_format($qty, 0, ',', '.') : '' }}</td>
                                    <td class="text-right money-cell">
                                        {{ $amount > 0 ? number_format($amount, 0, ',', '.') : '' }}
                                        @if ($amount > 0)
                                            <i class="fas fa-check checkmark"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-light">
                                <td><strong>TOTAL BANKNOTE</strong></td>
                                <td></td>
                                <td class="text-right money-cell">
                                    <strong>{{ number_format($banknoteTotal, 0, ',', '.') }}</strong>
                                    <i class="fas fa-check checkmark"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Coin Table -->
                    <table class="table table-bordered" style="margin-top: 0;">
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center bg-light">Coin</th>
                            </tr>
                            <tr>
                                <th class="text-center" width="40%">KOPURS</th>
                                <th class="text-center" width="30%">NUMBER OF UNITS</th>
                                <th class="text-center" width="30%">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $coinTotal = 0;
                                $coinDenominations = [
                                    ['field' => 'logam_1rb', 'value' => 1000, 'label' => 'Rp. 1.000,-'],
                                    ['field' => 'logam_500', 'value' => 500, 'label' => 'Rp. 500,-'],
                                    ['field' => 'logam_200', 'value' => 200, 'label' => 'Rp. 200,-'],
                                    ['field' => 'logam_100', 'value' => 100, 'label' => 'Rp. 100,-'],
                                    ['field' => 'logam_50', 'value' => 50, 'label' => 'Rp. 50,-'],
                                    ['field' => 'logam_25', 'value' => 25, 'label' => 'Rp. 25,-'],
                                ];
                            @endphp
                            @foreach ($coinDenominations as $denom)
                                @php
                                    $qty = $pcbc->{$denom['field']} ?? 0;
                                    $amount = $qty * $denom['value'];
                                    $coinTotal += $amount;
                                @endphp
                                <tr>
                                    <td>{{ $denom['label'] }}</td>
                                    <td class="text-center">{{ $qty > 0 ? number_format($qty, 0, ',', '.') : '' }}</td>
                                    <td class="text-right money-cell">
                                        {{ $amount > 0 ? number_format($amount, 0, ',', '.') : '' }}
                                        @if ($amount > 0)
                                            <i class="fas fa-check checkmark"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-light">
                                <td><strong>TOTAL COIN</strong></td>
                                <td></td>
                                <td class="text-right money-cell">
                                    <strong>{{ number_format($coinTotal, 0, ',', '.') }}</strong>
                                    <i class="fas fa-check checkmark"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section C: Total Petty Cash and Difference -->
            <div class="row mb-3">
                <div class="col-12">
                    @php
                        $totalPettyCash = $banknoteTotal + $coinTotal;
                        $difference = ($systemBalance ?? 0) - $totalPettyCash;
                    @endphp
                    <table class="table table-bordered">
                        <tr>
                            <td width="70%"><strong>C. TOTAL PETTY CASH</strong></td>
                            <td width="30%" class="text-right money-cell">
                                <strong>{{ number_format($totalPettyCash, 0, ',', '.') }}</strong>
                                <i class="fas fa-check checkmark"></i>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>DIFFERENCE (A-C)</strong></td>
                            <td class="text-right money-cell">
                                <strong>{{ number_format($difference, 0, ',', '.') }}</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Amount in Words -->
            <div class="row mb-3">
                <div class="col-12">
                    <p style="margin: 0;"><strong>Amount in words:</strong> {{ $terbilang }}</p>
                </div>
            </div>

            <!-- Verification Statement -->
            <div class="row mb-3">
                <div class="col-12">
                    <p style="margin: 0; font-style: italic;">
                        Telah diperiksa kebenaran jumlah fisik uang dalam Cash Box seperti terinci diatas ini.
                    </p>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <table class="table" style="border: none;">
                        <thead>
                            <tr>
                                <th class="text-center" width="33.33%">Checked By,</th>
                                <th class="text-center" width="33.33%">Approved By,</th>
                                <th class="text-center" width="33.33%">Prepared By,</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center" height="60" style="border-bottom: 1px solid #000;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 10px; height: 40px;">
                                    </div>
                                </td>
                                <td class="text-center" style="border-bottom: 1px solid #000;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 10px; height: 40px;">
                                    </div>
                                </td>
                                <td class="text-center" style="border-bottom: 1px solid #000;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 10px; height: 40px;">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">
                                    <strong>{{ $pcbc->pemeriksa1 ?? 'N/A' }}</strong>
                                </td>
                                <td class="text-center">
                                    <strong>{{ $pcbc->approved_by ?? 'N/A' }}</strong><br>
                                    <small>Project Manager</small>
                                </td>
                                <td class="text-center">
                                    <strong>{{ $pcbc->createdBy->name ?? 'N/A' }}</strong><br>
                                    <small>Accounting Officer</small>
                                </td>
                            </tr>
                            @if ($pcbc->pemeriksa2)
                                <tr>
                                    <td colspan="3" class="text-center pt-2">
                                        <small><strong>Second Checker:</strong> {{ $pcbc->pemeriksa2 }}</small>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Distribution -->
            <div class="row">
                <div class="col-12">
                    <div style="font-size: 0.75rem; margin-top: 20px; border-top: 1px solid #000; padding-top: 10px;">
                        <strong>DISTRIBUTION on COMPLETION:</strong> Original for Site Accounting<br>
                        Copy for Division/ Department Concerned and Customer
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="no-print">
        <script>
            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            function changeDesign(design) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('design', design);
                window.location.href = currentUrl.toString();
            }

            window.addEventListener('scroll', function() {
                const floatingButtons = document.querySelector('.floating-buttons');
                if (window.pageYOffset > 300) {
                    floatingButtons.style.opacity = '1';
                    floatingButtons.style.visibility = 'visible';
                } else {
                    floatingButtons.style.opacity = '0.7';
                }
            });

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
