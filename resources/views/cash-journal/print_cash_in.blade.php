<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PayReq System | Cash Out Journal</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
  
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
  
</head>
<body>
<div class="wrapper mx-4">
  <!-- Main content -->
  <section class="invoice">
    <!-- title row -->
    <div class="row">
      <div class="col-6">
        <h4 class="page-header"><strong>PT Arkananta Apta Pratista</strong></h4>
        <h5>Project: {{ auth()->user()->project }}</h5>
      </div>
      <div class="col-6" style="text-align: right">
        <h4 class="page-header"><strong>{{ $journal->type }} Journal</strong></h4>
       
        <h5>Document No: <b>{{ $journal->journal_no }}</b> | Date: {{ date('d-M-Y', strtotime($journal->date)) }}</h5>
        <h5>SAP Document No: <b>{{ $journal->sap_journal_no }}</b> | Date: {{ date('d-M-Y', strtotime($journal->sap_posting_date)) }}</h5>
      </div>
      <!-- /.col -->
    </div>
    <!-- info row -->

    <!-- Table row -->
    <div class="row">
      <div class="col-12 table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr class="bg-black disabled color-palette">
                <th>Account & Description</th>
                <th class="text-right">Debet (IDR)</th>
                <th class="text-right">Credit (IDR)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
                <th>{{ $debet_account->account_number }} - {{ $debet_account->account_name }}</th>
                <th class="text-right">{{ number_format($incomings->sum('amount'), 2) }}</th>
                <th class="text-right">{{ number_format(0, 2) }}</th>
            </tr>
                <tr>
                    <td>
                        <ol>
                            @foreach ($incomings as $item)
                                <li>{{ $item->description }}, {{ $item->realization->requestor->name }}, Rp. {{ number_format($item->amount, 2) }}</li>
                            @endforeach
                        </ol>
                    </td>
                </tr>
                <tr>
                    <th>{{ $credit_account->account_number }} - {{ $credit_account->account_name }}</th>
                    <th class="text-right ">{{ number_format(0, 2) }}</th>
                    <th class="text-right">{{ number_format($incomings->sum('amount'), 2) }}</th>
                </tr>
                <tr>
                  <th class="text-right">TOTAL</th>
                  <th class="text-right ">{{number_format($incomings->sum('amount'), 2) }}</th>
                  <th class="text-right">{{ number_format($incomings->sum('amount'), 2) }}</th>
              </tr>
        </tbody>
          <tfoot>
            {{--  --}}
          </tfoot>
        </table>
        
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->

    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            <b>Prepared by / Cashier</b>
            <br>
            <br>
            <br>
            <br>
            {{ $journal->createdBy->name }}<br>
        </div>

        <div class="col-sm-4 invoice-col">
            <b>Approved by</b>
            <br>
            <br>
            <br>
            <br>
            ( .................. )<br>
        </div>

        <div class="col-sm-4 invoice-col">
            <b>Verify by</b>
            <br>
            <br>
            <br>
            <br>
            ( .................. )<br>
        </div>
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>
<!-- ./wrapper -->
<!-- Page specific script -->
<script>
  // window.addEventListener("load", window.print());
</script>
</body>
</html>

