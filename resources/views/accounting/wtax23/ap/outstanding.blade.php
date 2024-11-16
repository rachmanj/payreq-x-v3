@extends('templates.main')

@section('title_page')
    PPh 23
@endsection

@section('breadcrumb_title')
    accounting / wtax23
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-dashboard-links page="purchase" status="outstanding" />

            <div class="card">
                <div class="card-header">
                    <b>OUTSTANDING</b> | <a
                        href="{{ route('accounting.wtax23.index', ['page' => 'purchase', 'status' => 'complete']) }}">Complete</a>
                </div>
                <div class="card-body">
                    <table id="purchase-outs" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>DocNum</th>
                                <th>CreateD</th>
                                <th>PostD</th>
                                <th>Amount</th>
                                <th>Remarks</th>
                                <th>Days</th>
                                <td></td>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->
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
    <style>
        .card-header .active {
            font-weight: bold;
            color: black;
            text-transform: uppercase;
        }
    </style>
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
            $("#purchase-outs").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('accounting.wtax23.data') }}",
                    data: {
                        page: 'purchase',
                        status: 'outstanding'
                    }
                },
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
                        data: 'amount'
                    },
                    {
                        data: 'remarks'
                    },
                    {
                        data: 'days'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [4, 6],
                    "className": "text-right"
                }]
            })
        });
    </script>
@endsection
