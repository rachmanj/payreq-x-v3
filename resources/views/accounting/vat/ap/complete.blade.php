@extends('templates.main')

@section('title_page')
    VAT
@endsection

@section('breadcrumb_title')
    accounting / vat
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-vat-links page="purchase" status="complete" />

            <div class="card">
                <div class="card-header">
                    <a
                        href="{{ route('accounting.vat.index', ['page' => 'purchase', 'status' => 'incomplete']) }}">Incomplete</a>
                    | COMPLETE
                </div>
                <div class="card-body">
                    <table id="purchase-complete" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vendor</th>
                                <th>DocNum</th>
                                <th>CreateD</th>
                                <th>PostD</th>
                                <th>Faktur</th>
                                <th>IDR</th>
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
            /* font-weight: bold; */
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
            $("#purchase-complete").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('accounting.vat.data') }}",
                    data: {
                        page: 'purchase',
                        status: 'complete'
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'customer'
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
                        data: 'faktur'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                fixedHeader: true,
                // columnDefs: [{
                //     "targets": [4],
                //     "className": "text-right"
                // }, {
                //     "targets": [2],
                //     "className": "text-center"
                // }]
            })
        });
    </script>
@endsection
