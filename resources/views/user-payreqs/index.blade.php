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
        <h3 class="card-title">Payment Request</h3>
        @if ($enable_payreq)
        <button type="button" class="btn btn-sm btn-primary float-right" data-toggle="modal" data-target="#new-payreq">
          <i class="fas fa-plus"></i> New Payreq
        </button>
        @else
        <button type="button" class="btn btn-sm btn-primary float-right" disabled>
          <i class="fas fa-plus"></i> New Payreq
        </button>
        @endif
        <br>
        @if ($overdue_payreqs > 0)
        <p class="text-red">Terdapat <b>{{ $overdue_payreqs }}</b> Payreq Advance yang Overdue. Silahkan buat Realization terlebih dahulu..</p>
        @endif
        @if ($overdue_realizations > 0)
        <p class="text-red">Terdapat <b>{{ $overdue_realizations }}</b> dokumen Realization yang belum diserahkan ke Accounting. Silahkan segera diselesaikan ..</p>
        @endif
      </div>
      
      <div class="card-body">
        <table id="mypayreqs" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Payreq No</th>
            <th>Type</th>
            <th>Status</th>
            <th>Date</th>
            <th>IDR</th>
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

{{-- MODAL NEW PAYREQ --}}
<div class="modal fade" id="new-payreq">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Payment Request Type</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body justify-content-between">
          <a href="{{ route('user-payreqs.advance.create') }}" class="btn btn-outline-success btn-lg btn-block">Advance</a>
          <a href="{{ route('user-payreqs.reimburse.create') }}" class="btn btn-outline-primary btn-lg btn-block">Reimburse</a>
      </div>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->

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
    $("#mypayreqs").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('user-payreqs.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'nomor'},
        {data: 'type'},
        {data: 'status'},
        {data: 'submit_at'},
        {data: 'amount'},
        // {data: 'days'},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [5],
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