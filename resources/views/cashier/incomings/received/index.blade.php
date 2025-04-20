@extends('templates.main')

@section('title_page')
    Incoming Payment
@endsection

@section('breadcrumb_title')
    incoming
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <a href="{{ route('cashier.incomings.index') }}">Not Receive Yet</a> |
                    <a href="#" style="color: black">HAS RECEIVED</a>
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
                                <th>Receive Date</th>
                                <th>IDR</th>
                                <th>Account</th>
                                <th>Status</th>
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
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}" defer></script>
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $("#incomings").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('cashier.incomings.received.data') }}',
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
                        data: 'receive_date'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'account'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                        "targets": [2, 5],
                        "className": "text-center"
                    },
                    {
                        "targets": [6],
                        "className": "text-right"
                    }
                ],
                // Improve rendering performance
                deferRender: true,
                scroller: true,
                paging: true
            });

            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });
        });
    </script>
@endsection
