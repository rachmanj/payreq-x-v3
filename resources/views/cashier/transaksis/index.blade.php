@extends('templates.main')

@section('title_page')
  TX HISTORY  
@endsection

@section('breadcrumb_title')
    transaksi
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Account No: {{ $account->account_number }} - {{ $account->account_name }}</h3>
      </div>  <!-- /.card-header -->
     
      <div class="card-body">
        <table id="transaksis" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th class="text-right">#</th>
            <th class="text-center">Create at</th>
            <th class="text-center">PostD</th>
            <th class="text-center">Type</th>
            <th class="text-center">Desc</th>
            <th class="text-right">Debit</th>
            <th class="text-right">Credit</th>
            <th class="text-right">Balance</th>
            {{-- <th></th> --}}
          </tr>
          </thead>
        </table>
      </div> <!-- /.card-body -->
    </div> <!-- /.card -->
  </div> <!-- /.col -->
</div>  <!-- /.row -->
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
    $("#transaksis").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cashier.transaksis.data') . '?account_id=' . $account->id }}',
      columns: [
        // {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'id'},
        {data: 'created_at'},
        {data: 'posting_date'},
        {data: 'document_type'},
        {data: 'description'},
        {data: 'debit'},
        {data: 'credit'},
        {data: 'row_balance'},
        // {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [1, 2, 3],
                "className": "text-center"
              },
              {
                "targets": [0, 5, 6],
                "className": "text-right"
              }
            ]
    })
  });
</script>
@endsection