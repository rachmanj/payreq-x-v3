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
        Type : <b>Reimburse</b><br>
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->

    <!-- Table row -->
    <div class="row">
      <div class="col-12 table-responsive">
        <table class="table table-bordered" style="border: 1px solid black;">
          <thead>
            <tr>
              <th style="border: 1px solid black;" class="text-right">No</th>
              <th style="border: 1px solid black;">Description</th>
              <th style="border: 1px solid black;" class="text-right">Amount (IDR)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($realization_details as $item)
            <tr>
              <td style="border: 1px solid black;" class="text-right">{{ $loop->iteration }}</td>
              <td style="border: 1px solid black;">{{ $item->description }} 
          @if ($item->unit_no != null)
            <br/>
            @if ($item->type === 'fuel')
              <small>Unit No: {{ $item->unit_no }}, {{ $item->type }} {{ $item->qty }} {{ $item->uom }}. HM: {{ $item->km_position }}</small>
            @else
              <small>{{ $item->type }}, HM: {{ $item->km_position }}</small>
            @endif 
          @endif
              </td>
              <td style="border: 1px solid black;" class="text-right">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
          </tbody>


          <tfoot>
            <tr>
              <th style="border: 1px solid black;" colspan="2" class="text-right">TOTAL</th>
              <th style="border: 1px solid black;" class="text-right">{{ number_format($payreq->amount, 2) }}</th>
            </tr>
            <tr>
              <th style="border: 1px solid black;" class="text-right">Say</th>
              <th style="border: 1px solid black;" colspan="2">{{ ucfirst($terbilang) }}</th>
            </tr>
            <tr>
              <td style="border: 1px solid black;" colspan="3"><b>Remarks:</b> {{ $payreq->remarks }} <br>
                {{ $payreq->rab_id ? 'RAB No. ' . $payreq->anggaran->nomor . ' | ' . $payreq->anggaran->rab_project . ' | ' . substr($payreq->anggaran->description, 0, 100) : '' }}</td>
            </tr>
            <tr>
              <td style="border: 1px solid black;" colspan="3">Transfer Info (Bank / Acc No / Acc Name) :</td>
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
          <br>
          <br>
          <br>
          <br>
          <br>
          <br>
          @foreach ($approvers as $approver) 
           ({{ $approver }} |   
          @endforeach ...............................)
        </div>

        <div class="col-sm-3 invoice-col">
          <b>Cashier</b>
          <br>
          <br>
          <br>
          <br>
          <br>
          <br>
          (....................................................)
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