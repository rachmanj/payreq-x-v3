@extends('templates.main')

@section('title_page')
    SAP Sync
@endsection

@section('breadcrumb_title')
    accounting / sap-sync
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-sync-links page="001H" />
            <div class="card">
                <div class="card-body">
                    <table id="verifications" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>VerificationJ No</th>
                                <th>Date</th>
                                <th>Status</th> {{-- posted or not posted --}}
                                <th>IDR</th>
                                <th>SAPJ No</th>
                                <th>Sync at</th>
                                <th></th>
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
        $(function() {
            $("#verifications").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('accounting.sap-sync.data', ['project' => '001H']) }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'sap_journal_no'
                    },
                    {
                        data: 'sap_posting_date'
                    },
                    // {data: 'amount'},
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [4],
                    "className": "text-right"
                }]
            })
        });
    </script>
@endsection
