@extends('templates.main')

@section('title_page')
  Journals  
@endsection

@section('breadcrumb_title')
    journals
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="journals" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>DocumentNo</th>
            <th>OffsetAccount</th>
            <th>Description</th>
            <th>Project</th>
            <th>debet</th>
            <th>credit</th>
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
    $("#journals").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('journals.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'posting_date'},
        {data: 'journal_no'},
        {data: 'account'},
        {data: 'remarks'},
        {data: 'project'},
        {data: 'debit'},
        {data: 'credit'},
        // {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [6, 7],
                "className": "text-right"
              }
            ]
    })
  });
</script>
@endsection