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

            <x-vat-links page="purchase" status="outstanding" />

            <div class="card">
                <div class="card-header">
                    OUTSTANDING | <a
                        href="{{ route('accounting.vat.index', ['page' => 'sales', 'status' => 'complete']) }}">Complete</a>
                </div>
                <div class="card-body">
                    <table id="sales-outs" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Invoice</th>
                                <th>Faktur</th>
                                <th>IDR</th>
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
            $("#sales-outs").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('accounting.vat.data') }}",
                    data: {
                        page: 'sales',
                        status: 'outstanding'
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
                        data: 'invoice'
                    },
                    {
                        data: 'faktur'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'remarks'
                    },
                    {
                        data: 'sales_days'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [6],
                    "className": "text-right"
                }]
            })
        });
    </script>
@endsection
