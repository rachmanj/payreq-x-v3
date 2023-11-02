@extends('templates.main')

@section('title_page')
    Cash Journals
@endsection

@section('breadcrumb_title')
    journals
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        @if ($outgoings_count > 0)
        <a href="{{ route('cash-journals.out.create') }}" class="btn btn-sm btn-success">Create Cash-Out Journal <span class="badge badge-danger">{{ $outgoings_count }}</span></a>
        @else
        <a href="{{ route('cash-journals.out.create') }}" class="btn btn-sm btn-success">Create Cash-Out Journal</a>
        @endif

        @if ($incomings_count > 0)
        <a href="{{ route('cash-journals.in.create') }}" class="btn btn-sm btn-primary">Create Cash-In Journal <span class="badge badge-danger">{{ $incomings_count }}</span></a>
        @else
        <a href="{{ route('cash-journals.in.create') }}" class="btn btn-sm btn-primary">Create Cash-In Journal</a>
        @endif
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="cash-journals" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>CashJ No</th>
            <th>Date</th>
            <th>Type</th>
            <th>Status</th> {{-- posted or not --}}
            <th>IDR</th>
            <th>SAPJ No</th>
            {{-- <th>By</th> --}}
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
    $("#cash-journals").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cash-journals.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'journal_no'},
        {data: 'date'},
        {data: 'type'},
        {data: 'status'},
        {data: 'amount'},
        {data: 'sap_journal_no'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              // {
              //   "targets": [2],
              //   "className": "text-center"
              // },
              {
                "targets": [5],
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
