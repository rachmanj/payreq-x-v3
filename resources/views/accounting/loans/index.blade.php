@extends('templates.main')

@section('title_page')
    Loans
@endsection

@section('breadcrumb_title')
    accounting / loans
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <x-loan-links page="index" />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Loans</h3>
                    {{-- create payreq --}}
                    <div class="card-tools">
                        <a href="{{ route('accounting.loans.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Loan
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table id="loans" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Agreement</th>
                                <th>Creditor</th>
                                <th>Desc</th>
                                <th>Principal IDR</th>
                                <th>StartD</th>
                                <th>status</th>
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
            $("#loans").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('accounting.loans.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'loan_code'
                    },
                    {
                        data: 'creditor_name'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'principal'
                    },
                    {
                        data: 'start_date'
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
                    "targets": [4],
                    "className": "text-right"
                }, ]
            })
        });
    </script>
@endsection
