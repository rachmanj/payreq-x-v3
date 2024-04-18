@extends('templates.main')

@section('title_page')
Reports
@endsection

@section('breadcrumb_title')
reports / loans
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mr-5">BG Jatuh Tempo Bulan Ini</h3>
        <a href="#" class="bg-yellow"><b>1490004194751</b></a>  | 
        <a href="{{ route('reports.loan.index_7997') }}">1270077977997</a> | 
        <a href="{{ route('reports.loan.index_all') }}">All</a>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back to Index</a>
        <br>
        Saldo: {{ number_format((float)$saldo->param_value, 2) }} | at: {{ \Carbon\Carbon::parse($saldo->updated_at)->addHours(8)->format('d-M-y H:i') . ' wita' }}
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="unpaid-table" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Due Date</th>
            <th>Creditor</th>
            <th>Desc</th>
            <th>Angs ke</th>
            <th>Bilyet No</th>
            <th>Amount</th>
            <th></th>
          </tr>
          </thead>
         
        </table>
      </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->

    <div class="row">
      <div class="col-12">
        <div class="card">

          <div class="card-header">
            <h3 class="card-title">Paid Installment this month</h3>
          </div>

          <div class="card-body">
            <table id="paid-table" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>#</th>
                <th>DueDate</th>
                <th>PaidDate</th>
                <th>Creditor</th>
                <th>Desc</th>
                <th>Angs ke</th>
                <th>Bilyet No</th>
                <th>Amount</th>
                <th></th>
              </tr>
              </thead>
             
            </table>
          </div>
          <!-- /.card-body -->
        </div>
      </div>
    </div>
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
    $("#unpaid-table").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('reports.loan.data') . "?akun_no=1490004194751" }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'due_date'},
        {data: 'creditor'},
        {data: 'loan.description'},
        {data: 'angsuran_ke'},
        {data: 'bilyet_no'},
        {data: 'bilyet_amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [4, 6],
                "className": "text-right"
              },
              {
                "targets": [5],
                "className": "text-center"
              }
            ]
    })

    $("#paid-table").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('reports.loan.paid_data') . "?akun_no=1490004194751" }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'due_date'},
        {data: 'paid_date'},
        {data: 'creditor'},
        {data: 'loan.description'},
        {data: 'angsuran_ke'},
        {data: 'bilyet_no'},
        {data: 'bilyet_amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [5, 7],
                "className": "text-right"
              },
              {
                "targets": [6],
                "className": "text-center"
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