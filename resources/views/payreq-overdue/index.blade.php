@extends('templates.main')

@section('title_page')
    Payreqs Overdue
@endsection

@section('breadcrumb_title')
    payreqs / overdue
@endsection

@section('content')
<div class="row">
  <div class="col-12">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payreq Overdue</h3>
        </div>

        <div class="card-body">
            <table id="payreq-overdue" class="table table-bordered table-striped" >
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Project</th>
                        <th>Payreq No</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>IDR</th>
                        <th>Days</th>
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
      $("#payreq-overdue").DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('payreq-overdue.data') }}',
        columns: [
          {data: 'DT_RowIndex', orderable: false, searchable: false},
          {data: 'employee'},
          {data: 'project'},
          {data: 'nomor'},
          {data: 'type'},
          {data: 'status'},
          {data: 'amount'},
          {data: 'days'},
          {data: 'action', orderable: false, searchable: false},
        ],
        fixedHeader: true,
        columnDefs: [
                {
                  "targets": [6, 7],
                  "className": "text-right"
                },
              ]
      })
    });
</script>
@endsection