@extends('templates.main')

@section('title_page')
    Outgoing Payment Request
@endsection

@section('breadcrumb_title')
    outgoings
@endsection

@section('content')
<div class="vj-show">
<div class="row">
  <div class="col-12">

    <div class="card card-outline card-primary">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h3 class="card-title mb-0"><i class="fas fa-arrow-circle-up"></i> Outgoing Payment Request</h3>
        @can('create_outgoing')
        <a href="{{ route('cashier.outgoings.create') }}" class="vj-btn vj-btn-primary"><i class="fas fa-plus"></i> New Outgoing</a>
        @endcan
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <table id="outgoings" class="table table-bordered table-striped">
          <thead>
          <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Payreq No</th>
            <th>Payment Date</th>
            <th>IDR</th>
            <th>Account</th>
            <th>Bukti Transfer</th>
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
</div>

@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
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
    $("#outgoings").DataTable({
      processing: true,
      serverSide: true,
      ajax: '{{ route('cashier.outgoings.data') }}',
      columns: [
        {data: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'employee'},
        {data: 'payreq_no'},
        {data: 'outgoing_date'},
        {data: 'amount'},
        {data: 'account'},
        {data: 'transfer_proof', orderable: false, searchable: false},
        {data: 'action', orderable: false, searchable: false},
      ],
      fixedHeader: true,
      columnDefs: [
              {
                "targets": [2],
                "className": "text-center"
              },
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
@include('partials.vj-soft-ui-swal')
<script>
  // Konfirmasi hapus bukti transfer via SweetAlert (DataTables rows)
  $(document).on('submit', '.js-delete-attachment', function (e) {
    e.preventDefault();
    const form = this;
    VjSwal.fire({
      title: 'Hapus Bukti Transfer',
      html: '<p>Hapus file bukti transfer ini?</p>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus',
      cancelButtonText: 'Batal',
      confirmVariant: 'danger',
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  });
</script>
@endsection
