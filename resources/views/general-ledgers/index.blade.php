@extends('templates.main')

@section('title_page')
  General Ledgers  
@endsection

@section('breadcrumb_title')
    general-ledgers
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <form action="{{ route('general-ledgers.search') }}" method="POST">
        @csrf
        <div class="input-group">
          <select class="form-control form-control-sm select2bs4" placeholder="Type your keywords here" id="account_id" name="account_id">
          <option value="">-- select account --</option>
          @foreach($accounts as $account)
            <option value="{{ $account->id }}">{{ $account->account_number . ' - ' . $account->account_name }}</option>
          @endforeach
          </select>
          <div class="input-group-append">
            <button type="submit" class="btn btn-sm btn-primary">
              <i class="fa fa-search"></i>
            </button>
          </div>
        </div>
        <form>
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="general-ledgers" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>DocumentNo</th>
            <th>Account</th>
            <th>Description</th>
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
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    $('#account_id').change(function () {
      $('#general-ledgers').DataTable({
        columnDefs: [
              {
                "targets": [4, 5, 6],
                "className": "text-right"
              }
            ]
      }).draw(true)
    })
  });


</script>
{{--
<script>
  $(function () {
    $("#general-ledgers").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('general-ledgers.search') }}',
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
                "targets": [5, 6],
                "className": "text-right"
              }
            ]
    });
       //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  });

</script>--}}
@endsection
