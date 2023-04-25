@extends('templates.main')

@section('title_page')
    Invoices
@endsection

@section('breadcrumb_title')
    invoices
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <a href="#" class="float-right mx-2"><b>Paid</b></a>
        <a href="{{ route('invoices.index') }}" class="float-right">Wait Payment | </a>
        {{-- <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Invoice</a> --}}
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="invoices" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>No.</th>
            <th>Vendor</th>
            <th>CRD</th> {{-- Cashier Received Date  --}}
            <th>PaidD</th>
            <th>Amount</th>
            <th>Origin</th>
            <th>Days</th>
            <th>Sender</th>
            {{-- <th></th> --}}
          </tr>
          </thead>
        </table>
      </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.col -->
</div>
<!-- /.row -->

@endsection

@section('styles')
    <!-- DataTables -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}"/>
  <!-- Select2 -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

<script>
  $(function () {
    $("#invoices").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('invoices.paid_data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor_invoice'},
        {data: 'vendor_name'},
        {data: 'created_at'},
        {data: 'payment_date'},
        {data: 'amount'},
        {data: 'origin'},
        {data: 'days'},
        {data: 'sender_name'},
        // {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [5, 7],
                "className": "text-right"
              },
            ]
    })
  });
</script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  }) 
</script>
@endsection