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
                    <a href="{{ route('accounting.daily-tx.truncate') }}" id="truncate-tbl"
                        class="btn btn-xs btn-danger float-right mr-2"
                        onclick="return confirm('Are you sure you want to truncate this table?')">Truncate</a>
                    <a href="{{ route('accounting.daily-tx.copyToInvoiceCreation') }}"
                        class="btn btn-xs btn-success float-right mr-2">Copy to DocCreat-table</a>
                    <a href="{{ route('accounting.daily-tx.copyToWtax23') }}"
                        class="btn btn-xs btn-success float-right mr-2">Copy to WTax23-table</a>
                    <button href="#" class="btn btn-xs btn-primary float-right mr-2" data-toggle="modal"
                        data-target="#modal-upload"> Upload</button>
                </div> <!-- /.card-header -->

                <div class="card-body">
                    <table id="invoice-data" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>DocNum</th>
                                <th>CreateD</th>
                                <th>PostD</th>
                                <th>DocType</th>
                                <th>Duration</th>
                                <th>User</th>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->

    {{-- modal upload --}}
    @include('accounting.daily-tx.modal-upload')
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
            $("#invoice-data").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('accounting.daily-tx.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'doc_num'
                    },
                    {
                        data: 'create_date'
                    },
                    {
                        data: 'posting_date'
                    },
                    {
                        data: 'doc_type'
                    },
                    {
                        data: 'duration'
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
