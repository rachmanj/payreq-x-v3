<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Document Print</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
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
                            <td rowspan="2">
                                <h4>PT Arkananta Apta Pratista</h4>
                            </td>
                            <td rowspan="2">
                                <h3><b>Delivery Document</b></h3>
                                <h4>No. {{ $delivery->delivery_number }}</h4>
                                <h6>Document Date: {{ date('d F Y', strtotime($delivery->document_date)) }}</h4>
                            </td>
                            {{-- <td class="text-right">ARKA/ACC/IV/01.01</td> --}}
                        </tr>
                        <tr>
                            {{-- <td>{{ date('d-M-Y') }}</td> --}}
                        </tr>
                    </table>
                </div>
            </div>

            <!-- info row -->
            <div class="row">
                <div class="col-6">
                    <p><strong>From:</strong></p>
                    <address>
                        <strong>PT Arkananta Apta Pratista</strong><br>
                        Accounting Department<br>
                        Project: {{ $delivery->origin }}<br>
                    </address>
                </div>
                <div class="col-6">
                    <p><strong>To:</strong></p>
                    <address>
                        <strong>PT Arkananta Apta Pratista</strong><br>
                        Accounting Department<br>
                        Project: {{ $delivery->destination }}<br>
                        Recipient: {{ $delivery->recipient_name }}
                    </address>
                </div>
            </div>

            <!-- Delivery Info -->
            <div class="row">
                <div class="col-12">
                    <p>
                        {{-- <strong>Delivery Date:</strong> {{ date('d F Y', strtotime($delivery->delivery_date)) }}<br> --}}
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <p>Dengan hormat, bersama ini kami kirimkan dokumen-dokumen sebagai berikut:</p>
                </div>
            </div>

            <!-- Table row -->
            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="padding: 5px 0;">No</th>
                                <th style="padding: 5px 0;">Verification Journal No.</th>
                                <th style="padding: 5px 0;">Date</th>
                                <th style="padding: 5px 0;">SAP Journal No</th>
                                <th class="text-right" style="padding: 5px 0;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($delivery->verificationJournals as $journal)
                                <tr style="background-color: #f2f2f2; padding: 2px;"> <!-- Darkened background -->
                                    <td style="padding: 2px;">{{ $loop->iteration }}</td>
                                    <td style="padding: 2px;"><i class="far fa-square"></i> {{ $journal->nomor }}</td>
                                    <td style="padding: 2px;">{{ date('d M Y', strtotime($journal->date)) }}</td>
                                    <td style="padding: 2px;">{{ $journal->sap_journal_no }}</td>
                                    <td class="text-right" style="padding: 2px;">
                                        {{ number_format($journal->amount, 2) }}</td>
                                </tr>
                                @if ($journal->realizations->count() > 0)
                                    <tr>
                                        <td colspan="5">
                                            <strong>Document Realizations:</strong>
                                            <div class="row">
                                                @foreach ($journal->realizations as $index => $realization)
                                                    @if ($index % 4 == 0 && $index != 0)
                                            </div>
                                            <div class="row">
                                @endif
                                <div class="col-3">
                                    <li>
                                        <input type="checkbox" disabled> No.{{ $realization->nomor }}
                                        ({{ $realization->created_at->format('d M Y') }})
                                    </li>
                                </div>
                            @endforeach
                </div>
                </td>
                </tr>
                @endif
                @endforeach
                </tbody>
                </table>
            </div>
    </div>

    <!-- Signature row -->
    <div class="row mt-4">
        <div class="col-12">
            <table class="table">
                <tr>
                    <th>Prepared by</th>
                    <th>Delivered by</th>
                    <th>Received by</th>
                </tr>
                <tr>
                    <td height="100"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>({{ $journal->createdBy->name }})</td>
                    <td>(____________________)</td>
                    <td>(____________________)</td>
                </tr>
                <tr>
                    <td>Date: ________________</td>
                    <td>Date: ________________</td>
                    <td>Date: ________________</td>
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
