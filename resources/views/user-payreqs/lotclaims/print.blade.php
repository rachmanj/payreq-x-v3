<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>LOT Claim - {{ $lotclaim->lot_no }}</title>
        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
        <!-- Theme style -->
        <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
        <style>
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                font-family: Arial, sans-serif;
                line-height: 1.3;
            }

            .logo {
                width: 200px;
            }

            .amount {
                text-align: right;
                white-space: nowrap;
            }

            .signature-section {
                display: flex;
                justify-content: space-between;
                page-break-inside: avoid;
            }

            .signature-box {
                flex: 1;
                text-align: center;
                margin: 0 10px;
            }

            .signature-line {
                margin: 70px 0 5px 0;
                border-bottom: 0px solid black;
            }

            table th,
            table td {
                padding: 0.5rem !important;
            }

            .table-bordered td,
            .table-bordered th {
                border: 1px solid #dee2e6 !important;
            }

            .footer {
                background-color: lightgray;
            }
        </style>
    </head>

    <body>
        <div class="wrapper">
            <div class="content p-3">
                <!-- Header with Logo -->
                <div class="mb-2">
                    <img src="{{ asset('ark_logo.jpeg') }}" alt="Company Logo" class="logo mb-2">
                    <h5 class="font-weight-bold text-center">LETTER OF TRAVEL CLAIM</h5>
                </div>

                <!-- Document Info -->
                <div class="row mb-2">
                    <table style="width: 100%;">
                        <tr>
                            <td class="text-center" style="width: 33%;">
                                <label class="mb-1 text-muted">LOT Number</label>
                                <div class="h6"><strong>{{ $lotclaim->lot_no }}</strong></div>
                            </td>
                            <td class="text-center" style="width: 33%;">
                                <label class="mb-1 text-muted">Date</label>
                                <div class="h6">
                                    <strong>{{ date('d F Y', strtotime($lotclaim->claim_date)) }}</strong>
                                </div>
                            </td>
                            <td class="text-center" style="width: 33%;">
                                <label class="mb-1 text-muted">Project</label>
                                <div class="h6"><strong>{{ $lotclaim->project }}</strong></div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="p-2">
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table" style="width: 100%;">
                                <thead class="bg-light">
                                    <tr>
                                        <td><strong>A. ADVANCE</strong></td>
                                        <td class="text-right">
                                            <div class="h6">Rp
                                                {{ number_format($lotclaim->advance_amount, 2, ',', '.') }}
                                            </div>
                                        </td>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="p-2">
                    <!-- Expense Details Table -->
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <td colspan="4"><strong>B. REALIZATION</strong></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Accommodations -->
                                    <tr class="bg-light">
                                        <td colspan="4" class="text-center font-weight-bold">B.1. ACCOMMODATION</td>
                                    </tr>
                                    @forelse($lotclaim->accommodations as $acc)
                                        <tr>
                                            <td class="text-right" style="width: 5%;">{{ $loop->iteration }}</td>
                                            <td style="width: 30%;">{{ $acc->description }}</td>
                                            <td style="width: 30%;">{{ $acc->notes }}</td>
                                            <td style="width: 17%;" class="amount">
                                                {{ number_format($acc->accommodation_amount, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No accommodation expenses</td>
                                        </tr>
                                    @endforelse
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-right"><strong>Total Accommodation:</strong></td>
                                        <td class="amount">
                                            <strong>{{ number_format($lotclaim->accommodation_total, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>

                                    <!-- Travels -->
                                    <tr class="bg-light">
                                        <td colspan="4" class="text-center font-weight-bold">B.2. TRAVEL</td>
                                    </tr>
                                    @forelse($lotclaim->travels as $travel)
                                        <tr>
                                            <td class="text-right" style="width: 5%;">{{ $loop->iteration }}</td>
                                            <td>{{ $travel->description }}</td>
                                            <td>{{ $travel->notes }}</td>
                                            <td class="amount">{{ number_format($travel->travel_amount, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No travel expenses</td>
                                        </tr>
                                    @endforelse
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-right"><strong>Total Travel:</strong></td>
                                        <td class="amount">
                                            <strong>{{ number_format($lotclaim->travel_total, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>

                                    <!-- Meals -->
                                    <tr class="bg-light">
                                        <td colspan="4" class="text-center font-weight-bold">B.3. MEALS</td>
                                    </tr>
                                    @forelse($lotclaim->meals as $meal)
                                        <tr>
                                            <td class="text-right" style="width: 5%;">{{ $loop->iteration }}</td>
                                            <td>{{ ucfirst($meal->meal_type) }}</td>
                                            <td>{{ $meal->notes }}</td>
                                            <td class="amount">{{ number_format($meal->meal_amount, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No meal expenses</td>
                                        </tr>
                                    @endforelse
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-right"><strong>Total Meal:</strong></td>
                                        <td class="amount">
                                            <strong>{{ number_format($lotclaim->meal_total, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="p-2">
                    <!-- Summary Table -->
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-sm">
                                <tr>
                                    <td style="border: none;" class="w-50"><strong>Remarks:</strong><br>
                                        {{ $lotclaim->claim_remarks ?? '-' }}
                                    </td>
                                    <td style="border: none;" class="text-right"><strong>Total
                                            Claim:</strong></td>
                                    <td style="border: none;" class="amount">
                                        {{ number_format($lotclaim->total_claim, 2, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border: none;" rowspan="2"></td>
                                    <td style="border: none;" class="text-right"><strong>Advance Amount:</strong></td>
                                    <td style="border: none;" class="amount">
                                        {{ number_format($lotclaim->advance_amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border: none;" class="text-right"><strong>Difference:</strong></td>
                                    <td style="border: none;" class="amount">
                                        {{ number_format($lotclaim->difference, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="row">
                <div class="col-4 text-center">
                    <p><strong>Traveller</strong></p>
                    <div class="signature-line"></div>
                    <p>{{ $lotclaim->user->name }}</p>
                </div>
                {{-- <div class="col-3 text-center">
                    <p><strong>Received by</strong></p>
                    <div class="signature-line"></div>
                    <p>Date: _____________</p>
                </div> --}}
                <div class="col-4 text-center">
                    <p><strong>Approved by</strong></p>
                    <div class="signature-line"></div>
                    <p>___________________</p>
                </div>
                <div class="col-4 text-center">
                    <p><strong>Paid By</strong></p>
                    <div class="signature-line"></div>
                    <p>___________________</p>
                </div>
            </div>
            <div class="footer">
                <div class="p-2">
                    <div class="row">
                        <div class="col-6">
                            <p><strong>DISTRIBUTION ON COMPLETION:</strong> <i>Original for Cashier</i></p>
                        </div>
                        <div class="col-6">
                            <p><i>Copy for Division/Department Concerned and Customer</i></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <script>
            window.addEventListener("load", window.print());
        </script>
    </body>

</html>
