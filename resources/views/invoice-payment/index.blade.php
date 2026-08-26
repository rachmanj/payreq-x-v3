@extends('templates.main')

@section('title_page')
    Invoice Payment
@endsection

@section('breadcrumb_title')
    Invoice Payment
@endsection

@section('content')
    <div class="vj-show">
        @if (($departmentValidation['status'] ?? '') === 'missing_department')
            <div class="vj-alert vj-alert-secondary mb-3">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Missing DDS Department Code</strong><br>
                    Set your DDS department location code (e.g. <code>000HCASHO</code>) in your user profile or contact an
                    administrator.
                </div>
            </div>
        @elseif (($departmentValidation['status'] ?? '') === 'missing_config')
            <div class="vj-alert vj-alert-danger mb-3">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong>DDS API Not Configured</strong><br>
                    {{ $departmentValidation['message'] ?? 'DDS API URL or API key is missing.' }}
                </div>
            </div>
        @elseif (($departmentValidation['status'] ?? '') === 'invalid_department')
            <div class="vj-alert vj-alert-danger mb-3">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong>Invalid DDS Department Code</strong><br>
                    Code <code>{{ $departmentValidation['department_code'] }}</code> was not found in DDS. Update your user
                    profile with a valid location code.
                    @if (!empty($departmentValidation['valid_codes_sample']))
                        <br><small class="text-muted">Examples:
                            {{ implode(', ', $departmentValidation['valid_codes_sample']) }}</small>
                    @endif
                </div>
            </div>
        @elseif (($departmentValidation['status'] ?? '') === 'api_error')
            <div class="vj-alert vj-alert-secondary mb-3">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>DDS Connection Issue</strong><br>
                    {{ $departmentValidation['message'] ?? 'Could not validate department code against DDS.' }}
                </div>
            </div>
        @endif

        <div id="invoicePaymentApiAlert" class="vj-alert vj-alert-danger mb-3 d-none" role="alert">
            <i class="fas fa-times-circle"></i>
            <div>
                <strong id="invoicePaymentApiAlertTitle">Error</strong><br>
                <span id="invoicePaymentApiAlertMessage"></span>
            </div>
        </div>

        <div class="vj-stat-grid vj-stat-grid-4 mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Total Invoices</span>
                    <span class="vj-stat-value" id="totalInvoices">-</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-clock"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Waiting Payment</span>
                    <span class="vj-stat-value" id="waitingInvoices">-</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Paid Invoices</span>
                    <span class="vj-stat-value" id="paidInvoices">-</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-danger">
                <div class="vj-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Overdue Invoices</span>
                    <span class="vj-stat-value" id="overdueInvoices">-</span>
                </div>
            </div>
        </div>

        <div class="vj-stat-grid mb-3" style="grid-template-columns: repeat(2, 1fr);">
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Total Waiting Amount</span>
                    <span class="vj-stat-value" id="totalWaitingAmount">-</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Total Paid Amount</span>
                    <span class="vj-stat-value" id="totalPaidAmount">-</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice-dollar"></i> Invoice Payment
                        </h3>
                        @if (($departmentValidation['status'] ?? '') === 'ok')
                            <span class="vj-chip vj-chip-info">
                                <i class="fas fa-building"></i> {{ $departmentValidation['department_code'] }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row mb-3" id="invoicePaymentFilters">
                            <div class="col-md-2">
                                <label class="small text-muted mb-1">Status</label>
                                <select id="filter_status" class="form-control form-control-sm">
                                    <option value="">All</option>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted mb-1">Invoice Date From</label>
                                <input type="date" id="filter_date_from" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted mb-1">Invoice Date To</label>
                                <input type="date" id="filter_date_to" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted mb-1">Project</label>
                                <input type="text" id="filter_project" class="form-control form-control-sm"
                                    placeholder="Project code">
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted mb-1">Supplier</label>
                                <input type="text" id="filter_supplier" class="form-control form-control-sm"
                                    placeholder="Name or SAP code">
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted mb-1">Quick Search</label>
                                <input type="text" id="filter_search" class="form-control form-control-sm"
                                    placeholder="Invoice, supplier, project">
                            </div>
                            <div class="col-12 d-flex flex-wrap align-items-center mt-2">
                                <button type="button" class="vj-btn vj-btn-primary mr-2 mb-2" id="btnApplyFilters">
                                    <i class="fas fa-filter" aria-hidden="true"></i>
                                    <span>Apply Filters</span>
                                </button>
                                <button type="button" class="vj-btn vj-btn-warning mb-2" id="btnResetFilters">
                                    <i class="fas fa-undo" aria-hidden="true"></i>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>

                        <div class="vj-approval-doc-tabs" id="invoicePaymentTabs" role="tablist">
                            <a class="vj-approval-doc-tab is-active" id="waiting-tab" data-toggle="tab" href="#waiting"
                                role="tab" aria-controls="waiting" aria-selected="true">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <span>Waiting Payment</span>
                            </a>
                            <a class="vj-approval-doc-tab" id="paid-tab" data-toggle="tab" href="#paid" role="tab"
                                aria-controls="paid" aria-selected="false">
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                <span>Paid Invoices</span>
                            </a>
                        </div>

                        <div class="tab-content pt-3" id="invoicePaymentTabContent">
                            <div class="tab-pane fade show active" id="waiting" role="tabpanel"
                                aria-labelledby="waiting-tab">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <span class="vj-chip vj-chip-neutral">Unpaid invoices for your department</span>
                                    <button type="button" class="vj-btn vj-btn-primary" id="refreshWaitingBtn">
                                        <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                        <span>Refresh</span>
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="waitingTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Invoice #</th>
                                                <th>Faktur #</th>
                                                <th>Supplier</th>
                                                <th>Project</th>
                                                <th>Amount</th>
                                                <th>Receive Date</th>
                                                <th>Days</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="paid" role="tabpanel" aria-labelledby="paid-tab">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <span class="vj-chip vj-chip-success">Paid invoices for your department</span>
                                    <button type="button" class="vj-btn vj-btn-primary" id="refreshPaidBtn">
                                        <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                        <span>Refresh</span>
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="paidTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Invoice #</th>
                                                <th>Faktur #</th>
                                                <th>Supplier</th>
                                                <th>Project</th>
                                                <th>Amount</th>
                                                <th>Receive Date</th>
                                                <th>Payment Date</th>
                                                <th>Days</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="fas fa-check-circle text-success"></i> Mark Invoice as Paid
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="paymentForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_number_display" class="small text-muted">Invoice Number</label>
                                    <input type="text" class="form-control" id="invoice_number_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_display" class="small text-muted">Supplier</label>
                                    <input type="text" class="form-control" id="supplier_display" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_display" class="small text-muted">Amount</label>
                                    <input type="text" class="form-control" id="amount_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="payment_date" name="payment_date"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_project" class="small text-muted">Payment Project</label>
                            <input type="text" class="form-control" id="payment_project" name="payment_project"
                                placeholder="Enter project code">
                        </div>
                        <div class="form-group mb-0">
                            <label for="remarks" class="small text-muted">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                placeholder="Enter payment remarks"></textarea>
                        </div>
                        <input type="hidden" id="invoice_id" name="invoice_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="vj-btn vj-btn-warning" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="vj-btn vj-btn-success">
                            <i class="fas fa-save" aria-hidden="true"></i>
                            <span>Update Payment</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    @include('partials.vj-soft-ui-styles')
@endsection

@push('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            let waitingTable = null;
            let paidTable = null;

            initializeWaitingTable();
            initializePaidTable();
            loadDashboardData();

            $('#invoicePaymentTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                $('#invoicePaymentTabs .vj-approval-doc-tab').removeClass('is-active');
                $(e.target).addClass('is-active');
            });

            $('#btnApplyFilters').on('click', function() {
                applyFilters();
            });

            $('#btnResetFilters').on('click', function() {
                $('#invoicePaymentFilters').find('input, select').val('');
                applyFilters();
            });

            $('#filter_search').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    applyFilters();
                }
            });

            $('#refreshWaitingBtn').on('click', function() {
                if (waitingTable) {
                    waitingTable.ajax.reload();
                }
            });

            $('#refreshPaidBtn').on('click', function() {
                if (paidTable) {
                    paidTable.ajax.reload();
                }
            });

            function getFilterParams() {
                return {
                    status: $('#filter_status').val(),
                    date_from: $('#filter_date_from').val(),
                    date_to: $('#filter_date_to').val(),
                    project: $('#filter_project').val(),
                    supplier: $('#filter_supplier').val(),
                    search: $('#filter_search').val()
                };
            }

            function applyFilters() {
                $('#invoicePaymentApiAlert').addClass('d-none');
                loadDashboardData();
                if (waitingTable) {
                    waitingTable.ajax.reload();
                }
                if (paidTable) {
                    paidTable.ajax.reload();
                }
            }

            function loadDashboardData() {
                $.ajax({
                    url: '{{ route('cashier.invoice-payment.dashboard') }}',
                    method: 'GET',
                    data: getFilterParams(),
                    success: function(response) {
                        if (response.total_invoices !== undefined) {
                            $('#totalInvoices').text(response.total_invoices);
                            $('#waitingInvoices').text(response.waiting_invoices);
                            $('#paidInvoices').text(response.paid_invoices);
                            $('#overdueInvoices').text(response.overdue_invoices);
                            $('#totalWaitingAmount').text(response.currency + ' ' + response
                                .total_waiting_amount);
                            $('#totalPaidAmount').text(response.currency + ' ' + response
                                .total_paid_amount);
                        }
                    },
                    error: function(xhr) {
                        handleApiError(xhr, 'Failed to load dashboard data');
                    }
                });
            }

            function initializeWaitingTable() {
                waitingTable = $('#waitingTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '{{ route('cashier.invoice-payment.waiting') }}',
                        data: function() {
                            return getFilterParams();
                        },
                        dataSrc: 'invoices',
                        error: function(xhr) {
                            handleApiError(xhr, 'Failed to load waiting payment invoices');
                        }
                    },
                    columns: [{
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        {
                            data: 'invoice_number',
                            defaultContent: '-'
                        },
                        {
                            data: 'faktur_no',
                            defaultContent: '-'
                        },
                        {
                            data: 'supplier_name',
                            defaultContent: '-'
                        },
                        {
                            data: 'invoice_project',
                            defaultContent: '-'
                        },
                        {
                            data: 'amount',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'receive_date',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'days_diff',
                            className: 'text-right',
                            render: function(data) {
                                const chipClass = data > 30 ? 'vj-chip-danger' : data > 15 ?
                                    'vj-chip-warning' : 'vj-chip-success';
                                return '<span class="vj-chip ' + chipClass + '">' + data + ' days</span>';
                            }
                        },
                        {
                            data: 'status',
                            render: function(data) {
                                return renderStatusChip(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return '<button type="button" class="vj-btn vj-btn-success mark-paid-btn" data-invoice-id="' +
                                    row.id + '" data-invoice-number="' + escapeAttr(row.invoice_number) +
                                    '" data-supplier="' + escapeAttr(row.supplier_name) +
                                    '" data-amount="' + row.amount +
                                    '"><i class="fas fa-check" aria-hidden="true"></i><span>Mark Paid</span></button>';
                            }
                        }
                    ],
                    order: [
                        [6, 'desc']
                    ],
                    pageLength: 25,
                    responsive: true,
                    language: dataTableLanguage()
                });
            }

            function initializePaidTable() {
                paidTable = $('#paidTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '{{ route('cashier.invoice-payment.paid') }}',
                        data: function() {
                            return getFilterParams();
                        },
                        dataSrc: 'invoices',
                        error: function(xhr) {
                            handleApiError(xhr, 'Failed to load paid invoices');
                        }
                    },
                    columns: [{
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        {
                            data: 'invoice_number',
                            defaultContent: '-'
                        },
                        {
                            data: 'faktur_no',
                            defaultContent: '-'
                        },
                        {
                            data: 'supplier_name',
                            defaultContent: '-'
                        },
                        {
                            data: 'invoice_project',
                            defaultContent: '-'
                        },
                        {
                            data: 'amount',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'receive_date',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'payment_date',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'days_diff',
                            className: 'text-right',
                            render: function(data) {
                                return '<span class="vj-chip vj-chip-neutral">' + data + ' days</span>';
                            }
                        },
                        {
                            data: 'status',
                            render: function(data) {
                                return renderStatusChip(data);
                            }
                        }
                    ],
                    order: [
                        [7, 'desc']
                    ],
                    pageLength: 25,
                    responsive: true,
                    language: dataTableLanguage()
                });
            }

            function dataTableLanguage() {
                return {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    zeroRecords: "<div class='text-center py-3 text-muted'>No matching records found</div>"
                };
            }

            function renderStatusChip(status) {
                const normalized = (status || '').toLowerCase();
                const chipMap = {
                    open: 'vj-chip-info',
                    closed: 'vj-chip-success',
                    overdue: 'vj-chip-danger',
                    cancelled: 'vj-chip-neutral'
                };
                const chipClass = chipMap[normalized] || 'vj-chip-warning';

                return '<span class="vj-chip ' + chipClass + '">' + (status || '-') + '</span>';
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(amount);
            }

            function formatDate(dateString) {
                if (!dateString) {
                    return '-';
                }

                const date = new Date(dateString);
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const day = date.getDate().toString().padStart(2, '0');
                const month = months[date.getMonth()];
                const year = date.getFullYear();

                return day + '-' + month + '-' + year;
            }

            function escapeAttr(value) {
                return String(value || '').replace(/"/g, '&quot;');
            }

            function showApiError(title, message) {
                $('#invoicePaymentApiAlertTitle').text(title || 'Error');
                $('#invoicePaymentApiAlertMessage').text(message || 'An unexpected error occurred.');
                $('#invoicePaymentApiAlert').removeClass('d-none');
            }

            function handleApiError(xhr, fallbackMessage) {
                const response = xhr.responseJSON || {};
                const title = response.error || 'Request Failed';
                const message = response.message || fallbackMessage;
                showApiError(title, message);
                console.error(fallbackMessage, xhr);
            }

            $(document).on('click', '.mark-paid-btn', function() {
                const invoiceId = $(this).data('invoice-id');
                const invoiceNumber = $(this).data('invoice-number');
                const supplier = $(this).data('supplier');
                const amount = $(this).data('amount');

                $('#invoice_id').val(invoiceId);
                $('#invoice_number_display').val(invoiceNumber);
                $('#supplier_display').val(supplier);
                $('#amount_display').val(formatCurrency(amount));
                $('#payment_date').val(new Date().toISOString().split('T')[0]);
                $('#payment_project').val('');
                $('#remarks').val('');
                $('#paymentModal').modal('show');
            });

            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                const invoiceId = $('#invoice_id').val();
                const formData = {
                    payment_date: $('#payment_date').val(),
                    remarks: $('#remarks').val(),
                    payment_project: $('#payment_project').val()
                };

                Object.keys(formData).forEach(function(key) {
                    if (formData[key] === '') {
                        delete formData[key];
                    }
                });

                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

                $.ajax({
                    url: '{{ route('cashier.invoice-payment.update-payment', ['invoiceId' => ':invoiceId']) }}'
                        .replace(':invoiceId', invoiceId),
                    method: 'PUT',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            $('#paymentModal').modal('hide');

                            setTimeout(function() {
                                applyFilters();
                            }, 1000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to update payment'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update payment';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage
                        });
                    },
                    complete: function() {
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
