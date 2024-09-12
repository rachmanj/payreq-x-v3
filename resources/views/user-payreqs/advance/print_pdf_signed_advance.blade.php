<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PayReq System | Print Payreq</title>

  <!-- Google Font: Source Sans Pro -->
  {{-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"> --}}
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
      <div class="col-12 mb-2">
        {{-- <h4 class="page-header">
          PT Arkananta Apta Pratista
        </h4> --}}
        <img src="{{ asset('ark_logo.jpeg') }}" alt="Arkananta Logo" class="brand-image" style="opacity: .8; width: 200px;">
      </div>
      <!-- /.col -->
    </div>
    <!-- info row -->
    <div class="row invoice-info">
      <div class="col-sm-4 invoice-col">
        Pay To
        <address>
          <strong>{{ $payreq->requestor->name }} / {{ $payreq->requestor->nik }}</strong><br>
          Project: <b>{{ $payreq->project }}</b><br>
          Department: <b>{{ $payreq->department->department_name }}</b> <br>
        </address>
      </div>
      <div class="col-sm-4"><h3>Payment Request</h3></div>
      <div class="col-sm-4 invoice-col">
        Document No: <b>{{ $payreq->nomor }}</b><br>
        @if ($payreq->approved_at !== null)
        Approved at: <b>{{ \Carbon\Carbon::parse($payreq->approved_at)->addHours(8)->format('d M Y H:i') }} wita</b><br>
        @endif
        {{-- Date : <b>{{ $payreq->created_at->format('d-M-Y') }}</b><br> --}}
        Type : <b>{{ ucfirst($payreq->type) }}</b><br>
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->

    <!-- Table row -->
    <div class="row">
      <div class="col-12 table-responsive">
        <table class="table table-bordered" style="border: 1px solid black;">
          <thead>
            <tr> {{-- <tr class="text-white bg-secondary"> --}}
          <th style="border: 1px solid black;">No</th>
          <th style="border: 1px solid black;">Description</th>
          <th class="text-right" style="border: 1px solid black;">Amount (IDR)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
          <td style="border: 1px solid black;">1</td>
          <td style="border: 1px solid black;">{{ $payreq->remarks }} <br>
            {{ $payreq->rab_id ? 'RAB No. ' . $payreq->anggaran->nomor . ' | ' . $payreq->anggaran->rab_project . ' | ' . substr($payreq->anggaran->description, 0, 100) : '' }}
          </td>
          <td class="text-right" style="border: 1px solid black;">{{ number_format($payreq->amount, 2) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
          <th colspan="2" class="text-right" style="border: 1px solid black;">TOTAL</th>
          <th class="text-right" style="border: 1px solid black;">{{ number_format($payreq->amount, 2) }}</th>
            </tr>
            <tr>
          <th class="text-right" style="border: 1px solid black;">Say</th>
          <td colspan="2" style="border: 1px solid black;">{{ ucfirst($terbilang) }}</td>
            </tr>
            <tr>
              <td colspan="3" style="border: 1px solid black;">Transfer Info (Bank / Acc No / Acc Name) :</td>
            </tr>
          </tfoot>
        </table>
        
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->

    <div class="row invoice-info">
        <div class="col-sm-3 invoice-col">
            <b>Requestor</b><br>
            <br> 
            <br>
            <br>
            <br>
            <br>
            {{ $payreq->requestor->name }}<br>
        </div>

        <div class="col-sm-3 invoice-col">
          <b>Received by</b><br>
           Date: {{--{{ $payreq->created_at->format('d-M-Y') }} --}}<br> 
          <br>
          <br>
          <br>
          <br>
          (....................................)<br>
      </div>

        <div class="col-sm-3 invoice-col text-center">
          <b>Approved by</b>
          <img src="{{ asset('sign_rj2.png') }}" alt="Arkananta Logo" class="brand-image mx-auto d-block" style="opacity: .8; width: 140px;">
          @if ($payreq->type === 'advance')
            @foreach ($approvers as $approver) 
              ({{ $approver }} |   
            @endforeach ...............................)
          @else
            (...............................)
          @endif 
        </div>

        <div class="col-sm-3 invoice-col">
            <b>Cashier</b>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            (.......................................)
        </div>
        
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>
<!-- ./wrapper -->
<!-- Page specific script -->
<script>
  window.addEventListener("load", window.print());
</script> 
</body>
</html>