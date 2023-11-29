@extends('templates.main')

@section('title_page')
    Verification Journal
@endsection

@section('breadcrumb_title')
    verification journal
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Verification Journals</h3>
        @if ($realizations_count > 0)
        <a href="{{ route('verifications.journal.create') }}" class="btn btn-sm btn-success float-right mx-2">Prepare Journal <span class="badge badge-danger">{{ $realizations_count }}</span></a>
        @else
        <a href="{{ route('verifications.journal.create') }}" class="btn btn-sm btn-success float-right mx-2 disabled">Prepare Journal</a>
        @endif
      </div>
      <div class="card-body">
        <table id="verifications" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>VerificationJ No</th>
            <th>Date</th>
            <th>Status</th> {{-- posted or not posted --}}
            <th>IDR</th>
            <th>SAPJ No</th>
            <th>SAPJ Date</th>
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
    $("#verifications").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('verifications.journal.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'date'},
        {data: 'status'},
        {data: 'amount'},
        {data: 'sap_journal_no'},
        {data: 'sap_posting_date'},
        // {data: 'amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [4],
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