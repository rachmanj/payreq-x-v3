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
                                <label class="small font-weight-bold">Account Code</label>
                                <select class="form-control form-control-sm" id="account_code" required>
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
                    <div id="sap-alert" class="alert alert-danger d-none"></div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="account-info bg-light p-2 rounded">
                                <span class="small font-weight-bold">Account:</span>
                                <span id="account-code" class="badge badge-info mr-1"></span>
                                <span id="account-name" class="ml-1"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="account-info bg-light p-2 rounded text-right">
                                <span class="small font-weight-bold">Period:</span>
                                <span id="statement-period"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <p class="mb-1">Opening Balance</p>
                                    <h5 id="opening-balance" class="mb-0">-</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <p class="mb-1">Closing Balance</p>
                                    <h5 id="closing-balance" class="mb-0">-</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <p class="mb-1">Total Debit</p>
                                    <h5 id="total-debit" class="mb-0">-</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <p class="mb-1">Total Credit</p>
                                    <h5 id="total-credit" class="mb-0">-</h5>
                                </div>
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
                                    <th class="text-center" width="10%">Tx Number</th>
                                    <th class="text-center" width="10%">Unit</th>
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
            const alertBox = $('#sap-alert');
            const summaryFields = {
                opening: $('#opening-balance'),
                closing: $('#closing-balance'),
                totalDebit: $('#total-debit'),
                totalCredit: $('#total-credit')
            };

            function formatCurrency(value) {
                const number = Number(value ?? 0);
                return Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number);
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return dateString;
                
                const day = String(date.getDate()).padStart(2, '0');
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const month = months[date.getMonth()];
                const year = date.getFullYear();
                
                return `${day}-${month}-${year}`;
            }

            function resetSummary() {
                summaryFields.opening.text('-');
                summaryFields.closing.text('-');
                summaryFields.totalDebit.text('-');
                summaryFields.totalCredit.text('-');
                $('#statement-period').text('-');
            }

            function updateSummary(payload) {
                summaryFields.opening.text(formatCurrency(payload.opening_balance ?? 0));
                summaryFields.closing.text(formatCurrency(payload.closing_balance ?? 0));

                if (payload.summary) {
                    summaryFields.totalDebit.text(formatCurrency(payload.summary.total_debit ?? 0));
                    summaryFields.totalCredit.text(formatCurrency(payload.summary.total_credit ?? 0));
                } else {
                    summaryFields.totalDebit.text('-');
                    summaryFields.totalCredit.text('-');
                }

                if (payload.start_date && payload.end_date) {
                    $('#statement-period').text(`${formatDate(payload.start_date)} s/d ${formatDate(payload.end_date)}`);
                } else {
                    $('#statement-period').text('-');
                }
            }

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
                            data: 'posting_date',
                            className: 'text-center',
                            defaultContent: '-',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'description',
                            defaultContent: '-'
                        },
                        {
                            data: 'doc_num',
                            className: 'text-center',
                            defaultContent: '-'
                        },
                        {
                            data: 'doc_type',
                            className: 'text-center',
                            defaultContent: '-'
                        },
                        {
                            data: 'project_code',
                            className: 'text-center',
                            defaultContent: '-'
                        },
                        {
                            data: 'debit_amount',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'credit_amount',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'running_balance',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'tx_num',
                            className: 'text-center',
                            defaultContent: '-'
                        },
                        {
                            data: 'unit_no',
                            className: 'text-center',
                            defaultContent: '-'
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
                alertBox.addClass('d-none').text('');
                resetSummary();
                $('#account-code').text('');
                $('#account-name').text('');

                // Validate account selection
                if (!$('#account_code').val()) {
                    alert('Silahkan pilih Account terlebih dahulu');
                    return;
                }

                // Validate date range (<= 6 months)
                const startDateVal = $('#start_date').val();
                const endDateVal = $('#end_date').val();
                const startDateObj = new Date(startDateVal);
                const endDateObj = new Date(endDateVal);
                const sixMonthsLater = new Date(startDateObj);
                sixMonthsLater.setMonth(sixMonthsLater.getMonth() + 6);

                if (endDateObj > sixMonthsLater) {
                    alert('Date range cannot exceed 6 months.');
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
                        account_code: $('#account_code').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        draw: 1
                    },
                    success: function(response) {
                        // Update account info
                        if (response.account) {
                            $('#account-code').text(response.account.code);
                            $('#account-name').text(response.account.name);
                        }

                        updateSummary(response);

                        // Clear and add new data
                        table.clear();
                        if (response.data && response.data.length > 0) {
                            table.rows.add(response.data);
                        }
                        table.draw();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        const message = xhr.responseJSON && xhr.responseJSON.error ?
                            xhr.responseJSON.error :
                            'Failed to fetch data from SAP Bridge.';
                        alertBox.removeClass('d-none').text(message);
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
