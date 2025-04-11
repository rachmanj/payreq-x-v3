@extends('templates.main')

@section('title_page')
    SAP Transactions
@endsection

@section('breadcrumb_title')
    sap-transactions
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header p-2">
                    <div class="alert alert-info py-2 mb-2">
                        <i class="fas fa-info-circle"></i> Halaman ini menampilkan transaksi dari tanggal 1 Januari 2025
                        sampai dengan kemarin.
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Account Number</label>
                                <select class="form-control form-control-sm" id="account_number" required>
                                    <option value="">-- Pilih Account --</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->account_number }}">
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Start Date</label>
                                <input type="date" class="form-control form-control-sm" id="start_date" min="2025-01-01">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">End Date</label>
                                <input type="date" class="form-control form-control-sm" id="end_date" min="2025-01-01">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">&nbsp;</label>
                                <button class="btn btn-primary btn-sm btn-block" id="search">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="account-info bg-light p-2 rounded">
                                <span class="small font-weight-bold">Account:</span>
                                <span id="account-number" class="badge badge-info"></span>
                                <span id="account-name" class="ml-1"></span>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="sap-transactions" class="table table-sm table-bordered table-striped table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" width="3%">#</th>
                                    <th class="text-center" width="8%">Date</th>
                                    <th class="text-center" width="28%">Description</th>
                                    <th class="text-center" width="8%">Doc Number</th>
                                    <th class="text-center" width="8%">Doc Type</th>
                                    <th class="text-center" width="7%">Project</th>
                                    <th class="text-center" width="11%">Debit</th>
                                    <th class="text-center" width="11%">Credit</th>
                                    <th class="text-center" width="11%">Balance</th>
                                    <th class="text-center" width="8%">SAP User</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                            </tbody>
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
    <style>
        .table th,
        .table td {
            padding: 0.4rem;
            font-size: 0.85rem;
        }

        .table thead th {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .dataTables_info,
        .dataTables_paginate {
            font-size: 0.85rem;
            margin-top: 0.5rem !important;
        }

        .account-info {
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            // Set default dates
            const startDate = new Date('2025-01-01');
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            // Set start date to Jan 1, 2025
            $('#start_date').val('2025-01-01');

            // Set end date to yesterday or Jan 1, 2025 if we're before that date
            if (yesterday >= startDate) {
                $('#end_date').val(yesterday.toISOString().split('T')[0]);
            } else {
                $('#end_date').val('2025-01-01');
            }

            let table;

            function initializeTable() {
                if (table) {
                    table.destroy();
                }

                table = $('#sap-transactions').DataTable({
                    processing: true,
                    serverSide: false,
                    searching: false,
                    lengthChange: false,
                    pageLength: 15,
                    data: [],
                    columns: [{
                            data: null,
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            },
                            className: 'text-center',
                            orderable: false
                        },
                        {
                            data: 'date',
                            className: 'text-center'
                        },
                        {
                            data: 'description'
                        },
                        {
                            data: 'doc_num',
                            className: 'text-center'
                        },
                        {
                            data: 'doc_type',
                            className: 'text-center'
                        },
                        {
                            data: 'project_code',
                            className: 'text-center'
                        },
                        {
                            data: 'debit',
                            className: 'text-right'
                        },
                        {
                            data: 'credit',
                            className: 'text-right'
                        },
                        {
                            data: 'balance',
                            className: 'text-right'
                        },
                        {
                            data: 'sap_user',
                            className: 'text-center'
                        }
                    ],
                    order: [
                        [1, 'asc']
                    ]
                });
            }

            // Initialize table without data
            initializeTable();

            $('#search').click(function() {
                // Validate account selection
                if (!$('#account_number').val()) {
                    alert('Silahkan pilih Account terlebih dahulu');
                    return;
                }

                // Disable search button and show loading state
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

                // Make manual ajax request
                $.ajax({
                    url: '{{ route('cashier.sap-transactions.data') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        account_number: $('#account_number').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        draw: 1
                    },
                    success: function(response) {
                        // Update account info
                        if (response.account) {
                            $('#account-number').text(response.account.account_number);
                            $('#account-name').text(response.account.name);
                        }

                        // Clear and add new data
                        table.clear();
                        if (response.data && response.data.length > 0) {
                            table.rows.add(response.data);
                        }
                        table.draw();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        table.clear().draw();
                    },
                    complete: function() {
                        // Re-enable search button
                        $('#search').prop('disabled', false).html(
                            '<i class="fas fa-search"></i> Search');
                    }
                });
            });
        });
    </script>
@endsection
