@extends('templates.main')

@section('title_page')
    Cash On-Hand Transaction
@endsection

@section('breadcrumb_title')
    cashonhand-transactions
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header p-2">
                    <!-- Tabs navigation -->
                    <ul class="nav nav-tabs" id="cashTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="statement-tab" data-toggle="tab" href="#statement" role="tab"
                                aria-controls="statement" aria-selected="true">Statement</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="incomings-tab" data-toggle="tab" href="#incomings" role="tab"
                                aria-controls="incomings" aria-selected="false">Incomings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="outgoings-tab" data-toggle="tab" href="#outgoings" role="tab"
                                aria-controls="outgoings" aria-selected="false">Outgoings</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-2">
                    <!-- Tabs content -->
                    <div class="tab-content" id="cashTabsContent">
                        <!-- Statement Tab -->
                        <div class="tab-pane fade show active" id="statement" role="tabpanel"
                            aria-labelledby="statement-tab">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">Account Number</label>
                                        <input type="text" class="form-control form-control-sm" id="account_number"
                                            value="{{ $cash_account->account_number }} - {{ $cash_account->account_name }}"
                                            readonly>
                                        <input type="hidden" id="account_id" value="{{ $cash_account->id }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">Start Date</label>
                                        <input type="date" class="form-control form-control-sm" id="start_date">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">End Date</label>
                                        <input type="date" class="form-control form-control-sm" id="end_date">
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
                            <div class="row mb-2 mt-3">
                                <div class="col-md-6">
                                    <div class="account-info bg-light p-2 rounded">
                                        <span class="small font-weight-bold">Account:</span>
                                        <span id="account-number" class="badge badge-info"></span>
                                        <span id="account-name" class="ml-1"></span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <form action="{{ route('cashier.cashonhand-transactions.export-excel') }}"
                                        method="POST" id="export-form" class="d-none">
                                        @csrf
                                        <input type="hidden" name="account_id" value="{{ $cash_account->id }}">
                                        <input type="hidden" name="start_date" id="export-start-date">
                                        <input type="hidden" name="end_date" id="export-end-date">
                                    </form>
                                    <button type="button" id="export-excel" class="btn btn-sm btn-primary"
                                        style="display: none;">
                                        <i class="fas fa-file-excel"></i> Export to Excel
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="statement-table"
                                    class="table table-sm table-bordered table-striped table-hover w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" width="3%">#</th>
                                            <th class="text-center" width="8%">Date</th>
                                            <th class="text-center" width="28%">Description</th>
                                            <th class="text-center" width="10%">Doc Number</th>
                                            <th class="text-center" width="8%">Doc Type</th>
                                            <th class="text-center" width="8%">Project</th>
                                            <th class="text-center" width="12%">Debit</th>
                                            <th class="text-center" width="12%">Credit</th>
                                            <th class="text-center" width="11%">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Incomings Tab -->
                        <div class="tab-pane fade" id="incomings" role="tabpanel" aria-labelledby="incomings-tab">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">Month</label>
                                        <input type="month" class="form-control form-control-sm"
                                            id="incomings_month_year">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">&nbsp;</label>
                                        <button class="btn btn-primary btn-sm btn-block" id="search_incomings">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table id="incomings-table"
                                    class="table table-sm table-bordered table-striped table-hover w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" width="3%">#</th>
                                            <th class="text-center" width="8%">Date</th>
                                            <th class="text-center" width="10%">Document No</th>
                                            <th class="text-center" width="29%">Description</th>
                                            <th class="text-center" width="8%">Project</th>
                                            <th class="text-center" width="12%">Amount</th>
                                            <th class="text-left" width="15%">Cashier</th>
                                            <th class="text-left" width="15%">From</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Outgoings Tab -->
                        <div class="tab-pane fade" id="outgoings" role="tabpanel" aria-labelledby="outgoings-tab">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">Month</label>
                                        <input type="month" class="form-control form-control-sm"
                                            id="outgoings_month_year">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">&nbsp;</label>
                                        <button class="btn btn-primary btn-sm btn-block" id="search_outgoings">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table id="outgoings-table"
                                    class="table table-sm table-bordered table-striped table-hover w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" width="3%">#</th>
                                            <th class="text-center" width="8%">Date</th>
                                            <th class="text-center" width="10%">Payreq No</th>
                                            <th class="text-center" width="29%">Description</th>
                                            <th class="text-center" width="8%">Project</th>
                                            <th class="text-center" width="12%">Amount</th>
                                            <th class="text-left" width="15%">Cashier</th>
                                            <th class="text-left" width="15%">To</th>
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

        .nav-tabs .nav-item .nav-link {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .nav-tabs .nav-item .nav-link.active {
            font-weight: 600;
        }

        .table {
            width: 100% !important;
        }

        .tab-pane {
            padding-top: 15px;
        }

        .amount-right {
            text-align: right !important;
            font-weight: 500;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        // Date formatting function for tables
        function formatDate(dateString) {
            if (!dateString) return '';

            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = monthNames[date.getMonth()];
            const year = date.getFullYear();

            return `${day}-${month}-${year}`;
        }

        $(function() {
            // Set default month for month inputs to current month
            const setDefaultMonth = function() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0'); // JavaScript months are 0-indexed
                const monthYear = `${year}-${month}`;

                $('#incomings_month_year, #outgoings_month_year').val(monthYear);

                // Also set default dates for statement tab
                const firstDayOfMonth = `${year}-${month}-01`;
                const lastDay = new Date(year, month, 0).getDate();
                const lastDayOfMonth = `${year}-${month}-${lastDay}`;

                $('#start_date').val(firstDayOfMonth);
                $('#end_date').val(lastDayOfMonth);
            };

            setDefaultMonth();

            // Statement table
            let statementTable = $('#statement-table').DataTable({
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
                    }
                ],
                order: [
                    [1, 'asc']
                ]
            });

            // Incomings table
            let incomingsTable = $('#incomings-table').DataTable({
                processing: true,
                serverSide: false,
                searching: false,
                lengthChange: false,
                pageLength: 100,
                paging: true,
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
                        data: 'receive_date',
                        className: 'text-center',
                        render: function(data) {
                            return formatDate(data);
                        }
                    },
                    {
                        data: 'nomor',
                        className: 'text-center'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'project',
                        className: 'text-center'
                    },
                    {
                        data: 'amount',
                        className: 'amount-right',
                        render: function(data) {
                            return new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }).format(data);
                        }
                    },
                    {
                        data: 'cashier',
                        className: 'text-left'
                    },
                    {
                        data: 'from_user',
                        className: 'text-left'
                    }
                ],
                order: [
                    [1, 'asc'] // Order by date ascending
                ]
            });

            // Outgoings table
            let outgoingsTable = $('#outgoings-table').DataTable({
                processing: true,
                serverSide: false,
                searching: false,
                lengthChange: false,
                pageLength: 100,
                paging: true,
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
                        data: 'outgoing_date',
                        className: 'text-center',
                        render: function(data) {
                            return formatDate(data);
                        }
                    },
                    {
                        data: 'payreq_nomor',
                        className: 'text-center'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'project',
                        className: 'text-center'
                    },
                    {
                        data: 'amount',
                        className: 'amount-right',
                        render: function(data) {
                            return new Intl.NumberFormat('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }).format(data);
                        }
                    },
                    {
                        data: 'cashier',
                        className: 'text-left'
                    },
                    {
                        data: 'to_user',
                        className: 'text-left'
                    }
                ],
                order: [
                    [1, 'asc'] // Order by date ascending
                ]
            });

            // Search Statement
            $('#search').click(function() {
                // Validate required fields
                if (!$('#account_number').val()) {
                    alert('Silahkan pilih Account terlebih dahulu');
                    return;
                }

                // Validate date range
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();

                if (!startDate || !endDate) {
                    alert('Silahkan pilih rentang tanggal (Start Date dan End Date) terlebih dahulu');
                    return;
                }

                // Check if start date is after end date
                if (new Date(startDate) > new Date(endDate)) {
                    alert('Start Date tidak boleh lebih besar dari End Date');
                    return;
                }

                // Disable search button and show loading state
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

                // Make ajax request to get cash on hand transactions
                $.ajax({
                    url: '{{ route('cashier.cashonhand-transactions.data') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        account_number: $('#account_number').val(),
                        start_date: startDate,
                        end_date: endDate,
                        draw: 1
                    },
                    success: function(response) {
                        // Update account info
                        if (response.account) {
                            $('#account-number').text(response.account.account_number);
                            $('#account-name').text(response.account.name);
                        }

                        // Clear and add new data
                        statementTable.clear();
                        if (response.data && response.data.length > 0) {
                            statementTable.rows.add(response.data);

                            // Show export button and update form fields
                            $('#export-excel').show();
                            $('#export-start-date').val(startDate);
                            $('#export-end-date').val(endDate);
                        } else {
                            $('#export-excel').hide();
                        }
                        statementTable.draw();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        let errorMessage = 'Terjadi kesalahan: ';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            for (const field in errors) {
                                errorMessage += errors[field].join(', ');
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage += xhr.responseJSON.error;
                        } else {
                            errorMessage += error;
                        }

                        alert(errorMessage);
                        statementTable.clear().draw();
                        $('#export-excel').hide();
                    },
                    complete: function() {
                        // Re-enable search button
                        $('#search').prop('disabled', false).html(
                            '<i class="fas fa-search"></i> Search');
                    }
                });
            });

            // Search Incomings
            $('#search_incomings').click(function() {
                // Disable search button and show loading state
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

                // Get selected month-year
                const monthYearValue = $('#incomings_month_year').val();
                if (!monthYearValue) {
                    alert('Please select a month and year');
                    $(this).prop('disabled', false).html('<i class="fas fa-search"></i> Search');
                    return;
                }

                // Parse month and year from the input (format: "YYYY-MM")
                const [year, month] = monthYearValue.split('-');

                // Make ajax request to get incomings
                $.ajax({
                    url: '{{ route('cashier.cashonhand-transactions.incomings') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        month: month,
                        year: year
                    },
                    success: function(response) {
                        // Clear and add new data
                        incomingsTable.clear();
                        if (response.data && response.data.length > 0) {
                            incomingsTable.rows.add(response.data);
                        }
                        incomingsTable.draw();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        let errorMessage = 'Terjadi kesalahan: ';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            for (const field in errors) {
                                errorMessage += errors[field].join(', ');
                            }
                        } else {
                            errorMessage += error;
                        }

                        alert(errorMessage);
                        incomingsTable.clear().draw();
                    },
                    complete: function() {
                        // Re-enable search button
                        $('#search_incomings').prop('disabled', false).html(
                            '<i class="fas fa-search"></i> Search');
                    }
                });
            });

            // Search Outgoings
            $('#search_outgoings').click(function() {
                // Disable search button and show loading state
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

                // Get selected month-year
                const monthYearValue = $('#outgoings_month_year').val();
                if (!monthYearValue) {
                    alert('Please select a month and year');
                    $(this).prop('disabled', false).html('<i class="fas fa-search"></i> Search');
                    return;
                }

                // Parse month and year from the input (format: "YYYY-MM")
                const [year, month] = monthYearValue.split('-');

                // Make ajax request to get outgoings
                $.ajax({
                    url: '{{ route('cashier.cashonhand-transactions.outgoings') }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        month: month,
                        year: year
                    },
                    success: function(response) {
                        // Clear and add new data
                        outgoingsTable.clear();
                        if (response.data && response.data.length > 0) {
                            outgoingsTable.rows.add(response.data);
                        }
                        outgoingsTable.draw();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        let errorMessage = 'Terjadi kesalahan: ';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            for (const field in errors) {
                                errorMessage += errors[field].join(', ');
                            }
                        } else {
                            errorMessage += error;
                        }

                        alert(errorMessage);
                        outgoingsTable.clear().draw();
                    },
                    complete: function() {
                        // Re-enable search button
                        $('#search_outgoings').prop('disabled', false).html(
                            '<i class="fas fa-search"></i> Search');
                    }
                });
            });

            // Excel export button
            $('#export-excel').click(function() {
                $('#export-form').submit();
            });

            // Tab change handler to refresh tables when tabs are selected
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                // Adjust table layout when tab is shown
                if (e.target.id === 'incomings-tab') {
                    incomingsTable.columns.adjust();
                } else if (e.target.id === 'outgoings-tab') {
                    outgoingsTable.columns.adjust();
                } else if (e.target.id === 'statement-tab') {
                    statementTable.columns.adjust();
                }
            });
        });
    </script>
@endsection
