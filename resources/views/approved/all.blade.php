@extends('templates.main')

@section('title_page')
 All Payment Request  
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="all-payreqs" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>PayreqNo</th>
            <th>ApprvD</th>
            <th>IDR</th>
            <th>RealzNo</th>
            <th>RealzD</th>
            <th>RealzIDR</th>
            <th>VerifyD</th>
            {{-- <th>Days</th> --}}
            <th></th>
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
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

<script>
  $(function () {
    $("#all-payreqs").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('approved.all.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'employee'},
        {data: 'payreq_num'},
        {data: 'approve_date'},
        {data: 'payreq_idr'},
        {data: 'realization_num'},
        {data: 'realization_date'},
        {data: 'realization_amount'},
        {data: 'verify_date'},
        // {data: 'days'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [6, 7],
                "className": "text-right"
              },
              {
                "targets": [2, 3, 4, 5, 8],
                "className": "text-center"
              }
            ]
    })
  });
</script>
@endsection