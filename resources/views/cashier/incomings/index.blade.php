@extends('templates.main')

@section('title_page')
    Incoming Payment
@endsection

@section('breadcrumb_title')
    incoming
@endsection

@section('content')
    <div class="vj-show">
    <div class="row">
        <div class="col-12">

            <div class="card card-outline card-primary">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h3 class="card-title mb-0"><i class="fas fa-arrow-circle-down"></i> Incoming Payment</h3>
                        <span>
                            <a href="#" style="color: black">NOT RECEIVE YET</a> |
                            <a href="{{ route('cashier.incomings.received.index') }}">Has Received</a>
                        </span>
                    </div>
                    <a href="{{ route('cashier.incomings.create') }}" class="vj-btn vj-btn-success"><i class="fas fa-plus"></i> New Incoming Payment</a>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table id="incomings" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Project</th>
                                <th>Dept</th>
                                <th>Realization No / Desc</th>
                                <th>IDR</th>
                                <th>Account</th>
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
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
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
        $(function() {
            $("#incomings").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('cashier.incomings.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'employee'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'dept'
                    },
                    {
                        data: 'realization_no'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'account'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                        "targets": [2, 6],
                        "className": "text-center"
                    },
                    {
                        "targets": [5],
                        "className": "text-right"
                    }
                ]
            })
        });
    </script>
    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
@endsection
