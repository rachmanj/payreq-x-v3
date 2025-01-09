<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PCBC Document Print</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
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

            /* Remove any page margins */
            @page {
                margin: 0.5cm;
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
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Main content -->
        <section class="invoice">
            <!-- title row -->
            <div class="row">
                <div class="col-12">
                    <table class="table">
                        <tr>
                            <td>
                                <h4>PT Arkananta Apta Pratista</h4>
                                <h6>Project: {{ $pcbc->project }}</h6>
                            </td>
                            <td rowspan="2">
                                <h3><b>Petty Cash Balance Control</b></h3>
                                <h4>No. {{ $pcbc->nomor }}</h4>
                                <h6>Date: {{ date('d-M-Y', strtotime($pcbc->pcbc_date)) }}</h6>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <!-- Paper Money Section -->
                    <table class="table table-bordered" style="border-width: 2px;">
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center bg-light">Uang Kertas</th>
                            </tr>
                            <tr>
                                <th class="text-center" width="33.33%">Denomination</th>
                                <th class="text-center" width="33.33%">Quantity</th>
                                <th class="text-center" width="33.33%">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">100,000</td>
                                <td class="text-center">{{ number_format($pcbc->kertas_100rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->kertas_100rb * 100000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">50,000</td>
                                <td class="text-center">{{ number_format($pcbc->kertas_50rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->kertas_50rb * 50000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">20,000</td>
                                <td class="text-center">{{ number_format($pcbc->kertas_20rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->kertas_20rb * 20000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">10,000</td>
                                <td class="text-center">{{ number_format($pcbc->kertas_10rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->kertas_10rb * 10000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">5,000</td>
                                <td class="text-center">{{ number_format($pcbc->kertas_5rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->kertas_5rb * 5000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">2,000</td>
                                <td class="text-center">{{ number_format($pcbc->kertas_2rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->kertas_2rb * 2000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">1,000</td>
                                <td class="text-center">{{ number_format($pcbc->kertas_1rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->kertas_1rb * 1000) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <!-- Coin Money Section -->
                    <table class="table table-bordered" style="border-width: 2px;">
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center bg-light">Uang Logam</th>
                            </tr>
                            <tr>
                                <th class="text-center" width="33.33%">Denomination</th>
                                <th class="text-center" width="33.33%">Quantity</th>
                                <th class="text-center" width="33.33%">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1,000</td>
                                <td class="text-center">{{ number_format($pcbc->logam_1rb) }}</td>
                                <td class="text-right">{{ number_format($pcbc->logam_1rb * 1000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">500</td>
                                <td class="text-center">{{ number_format($pcbc->logam_500) }}</td>
                                <td class="text-right">{{ number_format($pcbc->logam_500 * 500) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">200</td>
                                <td class="text-center">{{ number_format($pcbc->logam_200) }}</td>
                                <td class="text-right">{{ number_format($pcbc->logam_200 * 200) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">100</td>
                                <td class="text-center">{{ number_format($pcbc->logam_100) }}</td>
                                <td class="text-right">{{ number_format($pcbc->logam_100 * 100) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">50</td>
                                <td class="text-center">{{ number_format($pcbc->logam_50) }}</td>
                                <td class="text-right">{{ number_format($pcbc->logam_50 * 50) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center">25</td>
                                <td class="text-center">{{ number_format($pcbc->logam_25) }}</td>
                                <td class="text-right">{{ number_format($pcbc->logam_25 * 25) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <table class="table table-bordered" style="border-width: 2px;">
                        <tr>
                            <th class="bg-light text-right">System Amount:</th>
                            <td class="text-right">{{ number_format($pcbc->system_amount, 2) }}</td>
                            <td>{{ number_format($pcbc->fisik_amount, 2) }} <br>
                                <small>({{ $terbilang }})</small>
                            </td>
                            <th class="bg-light">Physical Amount</th>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <table class="table">
                        <tr>
                            <th class="text-center">Prepared by</th>
                            <th class="text-center">Checked by</th>
                            <th class="text-center">Approved by</th>
                        </tr>
                        <tr>
                            <td class="text-center" height="100"></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                        </tr>
                        <tr>
                            <td class="text-center">({{ $pcbc->createdBy->name }})</td>
                            <td class="text-center">({{ $pcbc->pemeriksa1 }})</td>
                            <td class="text-center">({{ $pcbc->approved_by }})</td>
                        </tr>
                        <tr>
                            <td class="text-center">Date: ________________</td>
                            <td class="text-center">Date: ________________</td>
                            <td class="text-center">Date: ________________</td>
                        </tr>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <script>
        window.addEventListener("load", window.print());
    </script>
</body>

</html>
