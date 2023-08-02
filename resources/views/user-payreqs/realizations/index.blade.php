@extends('templates.main')

@section('title_page')
    My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Realizations</h3>
        <a href="{{ route('user-payreqs.realizations.create') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-plus"></i> New Realization</a>
      </div>
      
      <div class="card-body">
        <table id="realizations" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>No</th>
            <th>Payreq No</th>
            <th>Date</th>
            <th>Status</th>
            <th>IDR</th>
            <th>Days</th>
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
    $("#realizations").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('user-payreqs.realizations.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'number'},
        {data: 'payreq_no'},
        {data: 'created_at'},
        {data: 'status'},
        {data: 'amount'},
        {data: 'days'},
        // {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [5, 6],
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