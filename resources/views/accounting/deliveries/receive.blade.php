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

            <x-delivery-links page="receive" />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Receive Delivery</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="receive-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nomor</th>
                                <th>From</th>
                                <th>Date</th>
                                <th>User</th>
                                <th>Action</th>
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
        $(document).ready(function() {
            $('#receive-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounting.deliveries.receive_data') }}',
                    type: 'GET'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'delivery_number',
                        name: 'delivery_number'
                    },
                    {
                        data: 'origin',
                        name: 'origin'
                    },
                    {
                        data: 'sent_date',
                        name: 'sent_date'
                    },
                    {
                        data: 'sender_name',
                        name: 'sender_name'
                    },

                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [2, 'desc']
                ]
            });
        });
    </script>
@endsection
