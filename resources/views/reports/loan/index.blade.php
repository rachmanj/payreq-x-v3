@extends('templates.main')

@section('title_page')
Reports
@endsection

@section('breadcrumb_title')
reports / loans
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header vj-card-header-tabs d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar"></i> BG Jatuh Tempo Bulan Ini
                        </h3>
                        <a href="{{ route('reports.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Back to Index
                        </a>
                    </div>

                    <nav class="vj-approval-doc-tabs" aria-label="Account filters" role="tablist">
                        <span class="vj-approval-doc-tab is-active" role="tab" aria-selected="true">
                            <i class="fas fa-university"></i>
                            <span>1490004194751</span>
                        </span>
                        <a href="{{ route('reports.loan.index_7997') }}" class="vj-approval-doc-tab" role="tab"
                            aria-selected="false">
                            <i class="fas fa-university"></i>
                            <span>1270077977997</span>
                        </a>
                        <a href="{{ route('reports.loan.index_all') }}" class="vj-approval-doc-tab" role="tab"
                            aria-selected="false">
                            <i class="fas fa-list"></i>
                            <span>All</span>
                        </a>
                    </nav>

                    <div class="card-body">
                        <div class="vj-note mb-3">
                            <i class="fas fa-wallet"></i>
                            <div>
                                Saldo: {{ number_format((float) $saldo->param_value, 2) }} | at:
                                {{ \Carbon\Carbon::parse($saldo->updated_at)->format('d-M-y H:i') . ' wita' }}
                            </div>
                        </div>

                        <table id="unpaid-table" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Due Date</th>
                                    <th>Creditor</th>
                                    <th>Desc</th>
                                    <th>Angs ke</th>
                                    <th>Bilyet No</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="card card-outline card-primary mt-3">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-check-circle"></i> Paid Installment this month
                        </h3>
                    </div>

                    <div class="card-body">
                        <table id="paid-table" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>DueDate</th>
                                    <th>PaidDate</th>
                                    <th>Creditor</th>
                                    <th>Desc</th>
                                    <th>Angs ke</th>
                                    <th>Bilyet No</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            $("#unpaid-table").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('reports.loan.data') . "?akun_no=11201001" }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'due_date'
                    },
                    {
                        data: 'creditor'
                    },
                    {
                        data: 'loan.description'
                    },
                    {
                        data: 'angsuran_ke'
                    },
                    {
                        data: 'bilyet_no'
                    },
                    {
                        data: 'bilyet_amount'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                        "targets": [4, 6],
                        "className": "text-right"
                    },
                    {
                        "targets": [5],
                        "className": "text-center"
                    }
                ]
            })

            $("#paid-table").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('reports.loan.paid_data') . "?akun_no=11201001" }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'due_date'
                    },
                    {
                        data: 'paid_date'
                    },
                    {
                        data: 'creditor'
                    },
                    {
                        data: 'loan.description'
                    },
                    {
                        data: 'angsuran_ke'
                    },
                    {
                        data: 'bilyet_no'
                    },
                    {
                        data: 'bilyet_amount'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                        "targets": [5, 7],
                        "className": "text-right"
                    },
                    {
                        "targets": [6],
                        "className": "text-center"
                    }
                ]
            })
        });
    </script>
    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
@endsection
