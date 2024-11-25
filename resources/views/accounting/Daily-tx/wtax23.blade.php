@extends('templates.main')

@section('title_page')
    DAILY TX UPLOAD
@endsection

@section('breadcrumb_title')
    accounting / daily tx
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <a href="{{ route('accounting.daily-tx.index') }}">Daily Document Creation</a> |
                    DAILY WTAX23
                    <button href="#" class="btn btn-xs btn-primary float-right mr-2" data-toggle="modal"
                        data-target="#modal-upload"> Upload</button>
                </div> <!-- /.card-header -->

                <div class="card-body">
                    <table id="wtax23-data" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>CreateD</th>
                                <th>PostD</th>
                                <th>DocNum</th>
                                <th>DocType</th>
                                <th>Amount</th>
                                <th>Remarks</th>
                                <th>User</th>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->

    {{-- modal upload --}}
    @include('accounting.daily-tx.wtax23-upload')
@endsection

@section('styles')
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
            $("#wtax23-data").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('accounting.daily-tx.wtax23data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'create_date'
                    },
                    {
                        data: 'posting_date'
                    },
                    {
                        data: 'doc_num'
                    },
                    {
                        data: 'doc_type'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'remarks'
                    },
                    {
                        data: 'user_code'
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [5],
                    "className": "text-right"
                }]
            })
        });
    </script>
@endsection
