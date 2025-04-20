@extends('templates.main')

@section('title_page')
    Verifications
@endsection

@section('breadcrumb_title')
    verifications
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Verifications</h3>
                    <a href="{{ route('verifications.journal.index') }}"
                        class="btn btn-sm btn-success float-right mx-2">Create Journal</a>
                </div>
                <div class="card-body">
                    <table id="verifications" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Realization No</th>
                                <th>Realization Date</th>
                                <th>Payreq No</th>
                                <th>Employee</th>
                                <th>Project</th>
                                <th>Accounts</th>
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
    <!-- DataTables - Only include essential CSS -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Select2 - Only needed if actually using select2 on this page -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables - Only essential scripts -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            $("#verifications").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                autoWidth: false,
                ajax: {
                    url: '{{ route('verifications.data') }}',
                    cache: true,
                    timeout: 15000
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'realization_no'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'payreq_no'
                    },
                    {
                        data: 'requestor'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'is_complete'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                responsive: true,
                order: [], // Default no ordering on initialization
            });

            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });
        });
    </script>
@endsection
