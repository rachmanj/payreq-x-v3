@extends('templates.main')

@section('title_page')
    Delivery
@endsection

@section('breadcrumb_title')
    accounting / delivery
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-delivery-links page="list" />

            <div class="card">
                <div class="card-header">
                    Deliveries List
                </div>
                <div class="card-body">
                    <table id="deliveries-list" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Delivery No</th>
                                <th>Date</th>
                                <th>To</th>
                                <th>Voucher Journal Nos</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->

        </div>
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
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

    <script>
        $(function() {
            $("#deliveries-list").DataTable({
                ajax: {
                    url: "{{ route('accounting.deliveries.data') }}",
                    type: 'GET'
                },
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex'
                }, {
                    data: 'delivery_number',
                    name: 'delivery_number'
                }, {
                    data: 'document_date',
                    name: 'document_date'
                }, {
                    data: 'destination',
                    name: 'destination'
                }, {
                    data: 'vj_no',
                    name: 'vj_no'
                }, {
                    data: 'status',
                    name: 'status'
                }, {
                    data: 'action',
                    name: 'action'
                }]
            });
        });
    </script>
@endsection
