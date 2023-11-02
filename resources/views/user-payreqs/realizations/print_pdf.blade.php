<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PayReq System | Print Realization Payreq</title>

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
      <div class="col-12">
        <h4 class="page-header">
          PT Arkananta Apta Pratista
        </h4>
      </div>
      <!-- /.col -->
    </div>
    <!-- info row -->
    <div class="row invoice-info">
      <div class="col-sm-4 invoice-col">
        
        <address>
          Project: <b>{{ $realization->project }}</b><br>
          Department: <b>{{ $realization->department->department_name }}</b> <br>
        </address>
      </div>
      <div class="col-sm-4"><h3>Realization Payment Request</h3></div>
      <div class="col-sm-4 invoice-col">
        Document No: <b>{{ $realization->nomor }}</b><br>
        Date : <b>{{ $approved_at->format('d-M-Y') }}</b><br>
        Payreq No : <b>{{ $realization->payreq->nomor }}</b><br>
      </div>
    </div>
    
    <div class="row">
      <div class="col-12 table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr class="text-white bg-secondary">
                <th class="text-right">No</th>
                <th>Description</th>
                <th class="text-right">Amount (IDR)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($realization_details as $item)
            <tr>
              <td class="text-right">{{ $loop->iteration }}</td>
              <td>{{ $item->description }}</td>
              <td class="text-right">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
                <th colspan="2" class="text-right">TOTAL</th>
                <th class="text-right">{{ number_format($realization_details->sum('amount'), 2) }}</th>
            </tr>
            <tr>
              <th colspan="2" class="text-right">Advance Payment</th>
                <th class="text-right">{{ number_format($realization->payreq->amount, 2) }}</th>
            </tr>
            <tr>
              <th colspan="2" class="text-right">Variance</th>
                <th class="text-right">{{ number_format($realization->payreq->amount - $realization_details->sum('amount'), 2) }}</th>
            </tr>
            <tr>
                <th class="text-right">Say</th>
                <th colspan="2">{{ ucfirst($terbilang) }}</th>
            </tr>
          </tfoot>
        </table>
        
      </div>
      
    </div> 
    

    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            <b>Realization by</b><br>
            Date: {{ $approved_at->format('d-M-Y') }}<br>
            <br>
            <br>
            <br>
            {{ $realization->payreq->requestor->name }}<br>
        </div>

        <div class="col-sm-4 invoice-col">
            <b>Verified by</b>
            <br>
            <br>
            <br>
            <br>
        </div>

        <div class="col-sm-4 invoice-col text-center">
            <b>Approved by</b>
            <br>
            <br>
            <br>
            <br>
            <br>
            <div>
              @foreach ($approvers as $approver)
              ( {{ $approver }} )
              @endforeach
            </div>
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