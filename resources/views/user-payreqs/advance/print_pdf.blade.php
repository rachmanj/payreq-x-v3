<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PayReq System | Print Payreq</title>

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
        Pay To
        <address>
          <strong>Arny Ratnasari / 123456</strong><br>
          Project: <b>{{ $payreq->project }}</b><br>
          Department: <b>{{ $payreq->department->department_name }}</b> <br>
        </address>
      </div>
      <div class="col-sm-4 invoice-col">
        <b>Payment Request</b><br>
        Document No: <b>{{ $payreq->payreq_no }}</b><br>
        Date : <b>{{ $payreq->created_at->addHours(8)->format('d-M-Y H:i:s') . ' wita' }}</b><br>
        Type : <b>{{ ucfirst($payreq->type) }}</b><br>
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->

    <!-- Table row -->
    <div class="row">
      <div class="col-12 table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr class="text-white bg-secondary">
                <th>No</th>
                <th>Description</th>
                <th class="text-right">Amount (IDR)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
                <td>1</td>
                <td>{{ $payreq->remarks }}</td>
                <td class="text-right">{{ number_format($payreq->amount, 2) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
                <th colspan="2" class="text-right">TOTAL</th>
                <th class="text-right">{{ number_format($payreq->amount, 2) }}</th>
            </tr>
            <tr>
                <th class="text-right">Say</th>
                <th colspan="2">{{ ucfirst($terbilang) }}</th>
            </tr>
          </tfoot>
        </table>
        
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->

    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            <b>Requestor / Received by</b>
            <br>
            <br>
            <br>
            <br>
            Arny Ratnasari<br>
        </div>

        <div class="col-sm-4 invoice-col">
            <b>Cashier</b>
            <br>
            <br>
            <br>
            <br>
            Rifka Annisa<br>
        </div>

        <div class="col-sm-4 invoice-col">
            <b>Approved by</b>
            <br>
            <br>
            <br>
            <br>
            Rachman Y<br>
        </div>
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>
<!-- ./wrapper -->
<!-- Page specific script -->
<script>
//   window.addEventListener("load", window.print());
</script>
</body>
</html>