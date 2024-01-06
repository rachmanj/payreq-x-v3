@extends('templates.main')

@section('title_page')
Ongoing Payment Request
@endsection

@section('breadcrumb_title')
ongoings
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Ongoing Payreqs of <b>{{ implode(', ', $project_include) }}</b> | Total IDR <b>{{ number_format($total_amount, 2) }}</b></h3>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back to Index</a>
        <br>
        @hasanyrole('superadmin|admin|cashier')
        <b>000H</b> | <a href="{{ route('reports.ongoing.project', 1) }}">017C</a> | <a href="">021C</a>
        @endhasanyrole
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="ongoings" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Payreq No</th>
            <th>Status</th>
            <th>PaidD</th>
            <th>IDR</th>
            <th>Days</th>
            {{-- <th></th> --}}
          </tr>
          </thead>
          {{-- <tfoot>
          <tr>
            <th colspan="5" class="text-right">TOTAL</th>
            <th>{{ number_format($total_amount, 0) }}</th>
            <th></th>
            <th></th>
          </tfoot> --}}
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
    $("#ongoings").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('reports.ongoing.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'employee'},
        {data: 'nomor'},
        {data: 'status'},
        {data: 'outgoing_date'},
        {data: 'amount'},
        {data: 'days'},
        // {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [4],
                "className": "text-center"
              },
              {
                "targets": [5, 6],
                "className": "text-right"
              }
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