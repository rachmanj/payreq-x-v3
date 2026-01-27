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
                    <table class="table" style="border: none;">
                        <tr>
                            <td width="50%">
                                <h4 class="mb-1"><strong>PT Arkananta Apta Pratista</strong></h4>
                                <h6 class="mb-0"><strong>Project:</strong> {{ $pcbc->project }}</h6>
                            </td>
                            <td width="50%" class="text-right">
                                <h3 class="mb-1"><strong>Petty Cash Balance Control</strong></h3>
                                <h4 class="mb-1"><strong>No. {{ $pcbc->nomor }}</strong></h4>
                                <h6 class="mb-0"><strong>Date:</strong> {{ \Carbon\Carbon::parse($pcbc->pcbc_date)->format('d-M-Y') }}</h6>
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
                                <td class="text-center"><strong>100,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_100rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_100rb ?? 0) * 100000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>50,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_50rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_50rb ?? 0) * 50000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>20,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_20rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_20rb ?? 0) * 20000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>10,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_10rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_10rb ?? 0) * 10000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>5,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_5rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_5rb ?? 0) * 5000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>2,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_2rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_2rb ?? 0) * 2000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>1,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_1rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_1rb ?? 0) * 1000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>500</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_500 ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_500 ?? 0) * 500) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>100</strong></td>
                                <td class="text-center">{{ number_format($pcbc->kertas_100 ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->kertas_100 ?? 0) * 100) }}</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="text-center"><strong>TOTAL</strong></td>
                                <td class="text-center"><strong>{{ number_format(($pcbc->kertas_100rb ?? 0) + ($pcbc->kertas_50rb ?? 0) + ($pcbc->kertas_20rb ?? 0) + ($pcbc->kertas_10rb ?? 0) + ($pcbc->kertas_5rb ?? 0) + ($pcbc->kertas_2rb ?? 0) + ($pcbc->kertas_1rb ?? 0) + ($pcbc->kertas_500 ?? 0) + ($pcbc->kertas_100 ?? 0)) }}</strong></td>
                                <td class="text-right"><strong>{{ number_format((($pcbc->kertas_100rb ?? 0) * 100000) + (($pcbc->kertas_50rb ?? 0) * 50000) + (($pcbc->kertas_20rb ?? 0) * 20000) + (($pcbc->kertas_10rb ?? 0) * 10000) + (($pcbc->kertas_5rb ?? 0) * 5000) + (($pcbc->kertas_2rb ?? 0) * 2000) + (($pcbc->kertas_1rb ?? 0) * 1000) + (($pcbc->kertas_500 ?? 0) * 500) + (($pcbc->kertas_100 ?? 0) * 100)) }}</strong></td>
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
                                <td class="text-center"><strong>1,000</strong></td>
                                <td class="text-center">{{ number_format($pcbc->logam_1rb ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->logam_1rb ?? 0) * 1000) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>500</strong></td>
                                <td class="text-center">{{ number_format($pcbc->logam_500 ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->logam_500 ?? 0) * 500) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>200</strong></td>
                                <td class="text-center">{{ number_format($pcbc->logam_200 ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->logam_200 ?? 0) * 200) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>100</strong></td>
                                <td class="text-center">{{ number_format($pcbc->logam_100 ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->logam_100 ?? 0) * 100) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>50</strong></td>
                                <td class="text-center">{{ number_format($pcbc->logam_50 ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->logam_50 ?? 0) * 50) }}</td>
                            </tr>
                            <tr>
                                <td class="text-center"><strong>25</strong></td>
                                <td class="text-center">{{ number_format($pcbc->logam_25 ?? 0) }}</td>
                                <td class="text-right money-cell">{{ number_format(($pcbc->logam_25 ?? 0) * 25) }}</td>
                            </tr>
                            <tr class="bg-light">
                                <td class="text-center"><strong>TOTAL</strong></td>
                                <td class="text-center"><strong>{{ number_format(($pcbc->logam_1rb ?? 0) + ($pcbc->logam_500 ?? 0) + ($pcbc->logam_200 ?? 0) + ($pcbc->logam_100 ?? 0) + ($pcbc->logam_50 ?? 0) + ($pcbc->logam_25 ?? 0)) }}</strong></td>
                                <td class="text-right"><strong>{{ number_format((($pcbc->logam_1rb ?? 0) * 1000) + (($pcbc->logam_500 ?? 0) * 500) + (($pcbc->logam_200 ?? 0) * 200) + (($pcbc->logam_100 ?? 0) * 100) + (($pcbc->logam_50 ?? 0) * 50) + (($pcbc->logam_25 ?? 0) * 25)) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-calculator"></i> Amount Summary</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0" style="border-width: 2px;">
                                <tr>
                                    <th class="bg-light text-right" width="20%">System Amount:</th>
                                    <td class="text-right money-cell font-weight-bold" width="25%">
                                        Rp {{ number_format($pcbc->system_amount ?? 0, 2) }}
                                    </td>
                                    <th class="bg-light text-right" width="20%">Physical Amount:</th>
                                    <td class="text-right money-cell font-weight-bold text-success" width="35%">
                                        Rp {{ number_format($pcbc->fisik_amount ?? 0, 2) }}
                                        <br>
                                        <small class="text-muted">({{ $terbilang }})</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-right">SAP Amount:</th>
                                    <td class="text-right money-cell font-weight-bold">
                                        Rp {{ number_format($pcbc->sap_amount ?? 0, 2) }}
                                    </td>
                                    <th class="bg-light text-right">System Variance:</th>
                                    <td class="text-right money-cell font-weight-bold 
                                        @if(abs(($pcbc->system_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) < 0.01) 
                                            text-success 
                                        @elseif(abs(($pcbc->system_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) <= 1000) 
                                            text-warning 
                                        @else 
                                            text-danger 
                                        @endif">
                                        Rp {{ number_format(($pcbc->system_amount ?? 0) - ($pcbc->fisik_amount ?? 0), 2) }}
                                    </td>
                                </tr>
                                @if($pcbc->sap_amount)
                                <tr>
                                    <th class="bg-light text-right">SAP Variance:</th>
                                    <td class="text-right money-cell font-weight-bold 
                                        @if(abs(($pcbc->sap_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) < 0.01) 
                                            text-success 
                                        @elseif(abs(($pcbc->sap_amount ?? 0) - ($pcbc->fisik_amount ?? 0)) <= 1000) 
                                            text-warning 
                                        @else 
                                            text-danger 
                                        @endif">
                                        Rp {{ number_format(($pcbc->sap_amount ?? 0) - ($pcbc->fisik_amount ?? 0), 2) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <table class="table" style="border: none;">
                        <thead>
                            <tr>
                                <th class="text-center" width="33.33%">Prepared by</th>
                                <th class="text-center" width="33.33%">Checked by</th>
                                <th class="text-center" width="33.33%">Approved by</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center" height="80" style="border-bottom: 1px solid #000;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 10px; height: 60px;"></div>
                                </td>
                                <td class="text-center" style="border-bottom: 1px solid #000;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 10px; height: 60px;"></div>
                                </td>
                                <td class="text-center" style="border-bottom: 1px solid #000;">
                                    <div style="border-bottom: 1px dotted #000; margin-bottom: 10px; height: 60px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">
                                    <strong>({{ $pcbc->createdBy->name ?? 'N/A' }})</strong><br>
                                    <small>Cashier</small>
                                </td>
                                <td class="text-center">
                                    <strong>({{ $pcbc->pemeriksa1 ?? 'N/A' }})</strong><br>
                                    <small>Checker</small>
                                </td>
                                <td class="text-center">
                                    <strong>({{ $pcbc->approved_by ?? 'N/A' }})</strong><br>
                                    <small>Approver</small>
                                </td>
                            </tr>
                            @if($pcbc->pemeriksa2)
                            <tr>
                                <td colspan="3" class="text-center pt-3">
                                    <strong>Second Checker:</strong> {{ $pcbc->pemeriksa2 }}
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-center pt-3">
                                    <small>Date: ________________</small>
                                </td>
                                <td class="text-center pt-3">
                                    <small>Date: ________________</small>
                                </td>
                                <td class="text-center pt-3">
                                    <small>Date: ________________</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="row mt-4 no-print">
                <div class="col-12">
                    <div class="text-center">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Print Footer -->
            <div class="row mt-4" style="page-break-inside: avoid;">
                <div class="col-12">
                    <hr style="border-top: 1px solid #000;">
                    <div class="text-center">
                        <small>
                            <strong>Printed on:</strong> {{ \Carbon\Carbon::now()->format('d-M-Y H:i:s') }} | 
                            <strong>Printed by:</strong> {{ auth()->user()->name ?? 'System' }} |
                            <strong>Document ID:</strong> {{ $pcbc->id }}
                        </small>
                        <br>
                        <small class="text-muted">This is a computer-generated document</small>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="no-print">
        <script>
            // Auto-print can be disabled by commenting out the line below
            // window.addEventListener("load", window.print());
        </script>
    </div>
</body>

</html>
