@extends('templates.main')

@section('title_page')
    Migrasi Payreqs
@endsection

@section('breadcrumb_title')
    migrasi / payreqs
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Migrasi Payreqs</h3>
            {{-- create payreq --}}
            <div class="card-tools">
              <a href="{{ route('cashier.migrasi.index') }}" class="btn btn-primary btn-sm float-right">
                  <i class="fas fa-arrow-left"></i> Back
              </a>
              <a href="{{ route('cashier.migrasi.payreqs.create') }}" class="btn btn-success btn-sm float-right mr-2">
                <i class="fas fa-plus"></i> New Payreq Migrasi
              </a>
              <a href="{{ route('cashier.migrasi.payreqs.update_status') }}" class="btn btn-warning btn-sm float-right mr-2">
                <i class="fas fa-pencil"></i> Update Status
              </a>
            </div>
          </div>

        <div class="card-body">
          <table id="payreq_migrasi" class="table table-bordered table-striped">
            <thead>
            <tr>
              <th>#</th>
              <th>Employee</th>
              <th>PayreqNo</th>
              <th>PaidDate</th>
              <th>Project</th>
              <th>Cashier</th>
              <th>Status</th>
              <th>Amount</th>
              <th></th>
            </tr>
            </thead>
          </table>
        </div>
    </div>

  </div>
</div>
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
    $("#payreq_migrasi").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cashier.migrasi.payreqs.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'requestor'},
        {data: 'nomor'},
        {data: 'approved_at'},
        {data: 'project'},
        {data: 'cashier'},
        {data: 'status'},
        {data: 'amount'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [7],
                "className": "text-right"
              },
            ]
    })
  });
</script>
@endsection