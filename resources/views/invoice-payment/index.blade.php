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
                                                <th>SAP Payment</th>
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
                                                <th>SAP Payment</th>
                                                <th>Action</th>
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
                        <i class="fas fa-check-circle text-warning"></i> Mark paid without SAP
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

    <div class="modal fade" id="sapPaymentModal" tabindex="-1" role="dialog" aria-labelledby="sapPaymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sapPaymentModalLabel">
                        <i class="fas fa-paper-plane text-primary" aria-hidden="true"></i>
                        Submit Vendor Outgoing Payment to SAP
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="sapPaymentForm">
                    <div class="modal-body">
                        <div id="sapPaymentPreviewAlert" class="vj-alert vj-alert-danger mb-3 d-none">
                            <i class="fas fa-times-circle" aria-hidden="true"></i>
                            <div>
                                <strong id="sapPaymentPreviewAlertTitle">Error</strong><br>
                                <span id="sapPaymentPreviewAlertMessage"></span>
                            </div>
                        </div>
                        <div id="sapPaymentMismatchAlert" class="vj-alert vj-alert-warning mb-3 d-none">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            <div>
                                <strong>Amount mismatch</strong><br>
                                Invoice amount and SAP AP Invoice DocTotal differ. Review before posting.
                            </div>
                        </div>
                        <div id="sapPaymentAlreadyPostedAlert" class="vj-alert vj-alert-info mb-3 d-none">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <div>
                                <strong>SAP AP Invoice fully paid</strong><br>
                                <span id="sapPaymentAlreadyPostedMessage"></span>
                            </div>
                        </div>
                        <div id="sapPaymentHistorySection" class="mb-3 d-none">
                            <label class="small text-muted d-block mb-2">Payment History</label>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" id="sapPaymentHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-right">Amount</th>
                                            <th>SAP OP #</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sapPaymentHistoryBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="small text-muted">Invoice Number</label>
                                    <input type="text" class="form-control" id="sap_invoice_number_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="small text-muted">Supplier</label>
                                    <input type="text" class="form-control" id="sap_supplier_display" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="small text-muted">Invoice Amount</label>
                                    <input type="text" class="form-control" id="sap_amount_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="small text-muted">Paid to Date (SAP)</label>
                                    <input type="text" class="form-control" id="sap_paid_to_date_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="small text-muted">Remaining Balance</label>
                                    <input type="text" class="form-control" id="sap_remaining_balance_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sap_payment_amount">Amount to Pay <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control text-right" id="sap_payment_amount"
                                        name="payment_amount" min="1" step="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="small text-muted">SAP AP Invoice</label>
                                    <input type="text" class="form-control" id="sap_ap_doc_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="small text-muted">SAP AP DocTotal</label>
                                    <input type="text" class="form-control" id="sap_ap_total_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-md-0">
                                    <label class="small text-muted">&nbsp;</label>
                                    <div id="sapPartialPaymentHint" class="small text-muted d-none">
                                        Partial payment — invoice stays open in DDS until fully paid in SAP.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sap_payment_date">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="sap_payment_date" name="payment_date"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sap_payment_means">Payment Means <span class="text-danger">*</span></label>
                                    <select class="form-control" id="sap_payment_means" name="payment_means" required>
                                        <option value="transfer">Transfer</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sap_account_id">Cash / Bank Account <span class="text-danger">*</span></label>
                                    <select class="form-control" id="sap_account_id" name="account_id" required>
                                        <option value="">Select account</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sap_payment_project">Payment Project</label>
                                    <input type="text" class="form-control" id="sap_payment_project"
                                        name="payment_project" placeholder="Project code">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sap_payment_remarks">Remarks</label>
                                    <textarea class="form-control" id="sap_payment_remarks" name="remarks" rows="2"
                                        placeholder="Payment remarks"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sap_prepared_by">Prepared by <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="sap_prepared_by" name="prepared_by"
                                        maxlength="100" placeholder="Name of preparer" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sap_approved_by">Approved by <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="sap_approved_by" name="approved_by"
                                        maxlength="100" placeholder="Name of approver" required>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="sap_invoice_id">
                        <input type="hidden" id="sap_supplier_code">
                        <input type="hidden" id="sap_invoice_amount">
                        <input type="hidden" id="sap_invoice_sap_doc">
                        <input type="hidden" id="sap_close_invoice_in_dds" value="0">
                        <input type="hidden" id="sap_close_dds_only" value="0">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="vj-btn vj-btn-warning" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="vj-btn vj-btn-primary" id="sapPaymentSubmitBtn">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            <span id="sapPaymentSubmitBtnLabel">Post to SAP</span>
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
            let sapPaymentContext = 'paid';
            const canSubmitSapPayment = @json($canSubmitSapPayment ?? false);
            const canMarkPaidWithoutSap = @json($canMarkPaidWithoutSap ?? false);
            const defaultPreparedBy = @json($defaultPreparedBy ?? '');
            const sapPreviewUrlTemplate =
                '{{ route('cashier.invoice-payment.sap-payment.preview', ['invoiceId' => ':invoiceId']) }}';
            const sapSubmitUrlTemplate =
                '{{ route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => ':invoiceId']) }}';

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
                            data: 'sap_payment',
                            orderable: false,
                            searchable: false,
                            render: function(data) {
                                return renderSapPaymentChip(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return renderWaitingAction(row);
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
                        },
                        {
                            data: 'sap_payment',
                            orderable: false,
                            searchable: false,
                            render: function(data) {
                                return renderSapPaymentChip(data);
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return renderSapPaymentAction(row);
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

            function renderWaitingAction(row) {
                let html = '';

                if (canSubmitSapPayment) {
                    if (row.supplier_sap_code) {
                        html += '<button type="button" class="vj-btn vj-btn-success pay-invoice-btn" title="This will create Outgoing Payment in SAP B1"><i class="fas fa-money-bill-wave" aria-hidden="true"></i><span>PAY</span></button>';
                    } else {
                        html += '<button type="button" class="vj-btn vj-btn-success" disabled title="Supplier SAP code is missing"><i class="fas fa-money-bill-wave" aria-hidden="true"></i><span>PAY</span></button>';
                    }

                    if (canMarkPaidWithoutSap) {
                        html += '<div class="mt-1"><a href="#" class="mark-paid-without-sap-link small text-muted">Mark paid without SAP</a></div>';
                    }
                } else if (canMarkPaidWithoutSap) {
                    html = '<button type="button" class="vj-btn vj-btn-warning mark-paid-without-sap-btn"><i class="fas fa-check" aria-hidden="true"></i><span>Mark paid without SAP</span></button>';
                } else {
                    html = '<span class="text-muted small">-</span>';
                }

                return html;
            }

            function renderSapPaymentChip(sapPayment) {
                if (!sapPayment) {
                    return '<span class="vj-chip vj-chip-neutral">Not posted</span>';
                }

                if (sapPayment.status === 'success') {
                    const docNum = sapPayment.doc_num ? '#' + sapPayment.doc_num : '';
                    const totalPaid = parseFloat(sapPayment.total_paid || 0);
                    const invoiceAmount = parseFloat(sapPayment.invoice_amount || 0);
                    const paymentCount = parseInt(sapPayment.payment_count || 1, 10);

                    if (sapPayment.is_partial && invoiceAmount > 0) {
                        return '<span class="vj-chip vj-chip-warning" title="Latest OP ' + escapeAttr(docNum) + '">' +
                            'Paid ' + formatCurrency(totalPaid) + ' / ' + formatCurrency(invoiceAmount) +
                            ' (' + paymentCount + ')</span>';
                    }

                    return '<span class="vj-chip vj-chip-success">Posted ' + docNum + '</span>';
                }

                return '<span class="vj-chip vj-chip-danger" title="' + escapeAttr(sapPayment.error_message ||
                    '') + '">Failed</span>';
            }

            function renderSapPaymentAction(row) {
                if (!canSubmitSapPayment) {
                    return '-';
                }

                if (row.sap_payment && row.sap_payment.status === 'success' && row.sap_payment.is_fully_paid) {
                    return '<span class="text-muted small">Posted</span>';
                }

                if (!row.supplier_sap_code) {
                    return '<button type="button" class="vj-btn vj-btn-warning" disabled title="Supplier SAP code is missing">Submit to SAP</button>';
                }

                const label = row.sap_payment && row.sap_payment.is_partial ? 'Pay remaining' : 'Submit to SAP';

                return '<button type="button" class="vj-btn vj-btn-primary submit-sap-btn">' + label + '</button>';
            }

            function renderPaymentHistory(history) {
                const rows = history || [];
                if (rows.length === 0) {
                    $('#sapPaymentHistorySection').addClass('d-none');
                    $('#sapPaymentHistoryBody').empty();
                    return;
                }

                let html = '';
                rows.forEach(function(item) {
                    html += '<tr>' +
                        '<td>' + (item.date ? formatDate(item.date) : '-') + '</td>' +
                        '<td class="text-right">' + (item.amount != null ? formatCurrency(item.amount) : '-') + '</td>' +
                        '<td>' + (item.doc_num ? '#' + escapeAttr(item.doc_num) : '-') + '</td>' +
                        '</tr>';
                });
                $('#sapPaymentHistoryBody').html(html);
                $('#sapPaymentHistorySection').removeClass('d-none');
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

            function getDataTableRowData(table, element) {
                const $row = $(element).closest('tr');
                if ($row.hasClass('child')) {
                    return table.row($row.prev()).data();
                }

                return table.row($row).data();
            }

            $(document).on('click', '.pay-invoice-btn', function() {
                const row = getDataTableRowData(waitingTable, this);
                if (!row) {
                    return;
                }
                openSapPaymentModal(row, 'waiting');
            });

            $(document).on('click', '.mark-paid-without-sap-link, .mark-paid-without-sap-btn', function(e) {
                e.preventDefault();
                const row = getDataTableRowData(waitingTable, this);
                if (!row) {
                    return;
                }
                openMarkPaidWithoutSapModal(row);
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
                                timer: 2500,
                                showConfirmButton: false
                            });
                            $('#paymentModal').modal('hide');
                            applyFilters();
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

            $(document).on('click', '.submit-sap-btn', function() {
                const row = getDataTableRowData(paidTable, this);
                if (!row) {
                    return;
                }
                openSapPaymentModal(row, 'paid');
            });

            $('#sapPaymentForm').on('submit', function(e) {
                e.preventDefault();
                confirmSapPayment();
            });

            function openMarkPaidWithoutSapModal(row) {
                $('#invoice_id').val(row.id);
                $('#invoice_number_display').val(row.invoice_number || '');
                $('#supplier_display').val(row.supplier_name || '');
                $('#amount_display').val(formatCurrency(row.amount));
                $('#payment_date').val(new Date().toISOString().split('T')[0]);
                $('#payment_project').val(row.payment_project || '');
                $('#remarks').val(row.remarks || '');
                $('#paymentModal').modal('show');
            }

            function openSapPaymentModal(row, context) {
                sapPaymentContext = context || 'paid';
                const closeInDds = sapPaymentContext === 'waiting';
                let sapRemainingBalance = 0;

                $('#sapPaymentPreviewAlert').addClass('d-none');
                $('#sapPaymentMismatchAlert').addClass('d-none');
                $('#sapPaymentAlreadyPostedAlert').addClass('d-none');
                $('#sapPartialPaymentHint').addClass('d-none');
                $('#sap_close_invoice_in_dds').val(closeInDds ? '1' : '0');
                $('#sap_close_dds_only').val('0');
                $('#sap_invoice_id').val(row.id);
                $('#sap_invoice_number_display').val(row.invoice_number || '');
                $('#sap_supplier_display').val((row.supplier_name || '') + (row.supplier_sap_code ? ' (' +
                    row.supplier_sap_code + ')' : ''));
                $('#sap_supplier_code').val(row.supplier_sap_code || '');
                $('#sap_invoice_amount').val(row.amount || '');
                $('#sap_amount_display').val(formatCurrency(row.amount));
                $('#sap_paid_to_date_display').val('-');
                $('#sap_remaining_balance_display').val('-');
                $('#sap_payment_amount').val('');
                $('#sap_payment_remarks').val(row.remarks || '');
                $('#sap_payment_project').val(row.payment_project || '');
                $('#sap_prepared_by').val(defaultPreparedBy);
                $('#sap_approved_by').val('');
                $('#sap_invoice_sap_doc').val(row.sap_doc || '');
                $('#sap_payment_date').val(row.payment_date || new Date().toISOString().split('T')[0]);
                $('#sap_payment_means').val('transfer');
                $('#sap_account_id').html('<option value="">Loading accounts...</option>');
                $('#sap_ap_doc_display').val('Resolving...');
                $('#sap_ap_total_display').val('-');
                renderPaymentHistory([]);
                $('#sapPaymentSubmitBtn').prop('disabled', true);
                $('#sapPaymentSubmitBtnLabel').text(closeInDds ? 'Pay' : 'Post to SAP');
                $('#sapPaymentModalLabel').html(
                    closeInDds ?
                    '<i class="fas fa-money-bill-wave text-success" aria-hidden="true"></i> Pay Invoice via SAP B1' :
                    '<i class="fas fa-paper-plane text-primary" aria-hidden="true"></i> Submit Vendor Outgoing Payment to SAP'
                );
                $('#sapPaymentModal').modal('show');

                $.ajax({
                    url: sapPreviewUrlTemplate.replace(':invoiceId', row.id),
                    method: 'GET',
                    data: {
                        invoice_number: row.invoice_number,
                        supplier_sap_code: row.supplier_sap_code,
                        amount: row.amount,
                        payment_date: row.payment_date || '',
                        remarks: row.remarks || '',
                        sap_doc: row.sap_doc || ''
                    },
                    success: function(response) {
                        const preview = response.preview || {};
                        const apInvoice = preview.ap_invoice || {};
                        const invoice = preview.invoice || {};
                        const sapPayment = preview.sap_payment || {};

                        renderPaymentHistory(response.payment_history || []);

                        if (response.fully_paid) {
                            $('#sap_ap_doc_display').val(
                                (apInvoice.doc_num ? '#' + apInvoice.doc_num : '-') +
                                (apInvoice.doc_entry ? ' / Entry ' + apInvoice.doc_entry : '')
                            );
                            $('#sap_ap_total_display').val(apInvoice.doc_total != null ? formatCurrency(
                                apInvoice.doc_total) : '-');
                            $('#sap_paid_to_date_display').val(apInvoice.paid_to_date != null ? formatCurrency(
                                apInvoice.paid_to_date) : '-');
                            $('#sap_remaining_balance_display').val(formatCurrency(0));
                            $('#sap_payment_amount').val(0).prop('disabled', true);
                            $('#sapPaymentAlreadyPostedMessage').text(
                                'SAP AP Invoice is fully paid' +
                                (sapPayment.doc_num ? ' (latest OP #' + sapPayment.doc_num + ').' : '.') +
                                ' Confirm to close this invoice in DDS only.'
                            );
                            $('#sapPaymentAlreadyPostedAlert').removeClass('d-none');
                            $('#sap_close_dds_only').val('1');
                            $('#sap_payment_means, #sap_account_id, #sap_payment_amount, #sap_prepared_by, #sap_approved_by')
                                .closest('.form-group').addClass('d-none');
                            $('#sapPaymentSubmitBtn').prop('disabled', false);
                            $('#sapPaymentSubmitBtnLabel').text('Close in DDS');
                            if (invoice.payment_date) {
                                $('#sap_payment_date').val(invoice.payment_date);
                            }
                            return;
                        }

                        $('#sap_payment_means, #sap_account_id, #sap_payment_amount, #sap_prepared_by, #sap_approved_by')
                            .closest('.form-group').removeClass('d-none');
                        $('#sap_payment_amount').prop('disabled', false);
                        $('#sap_ap_doc_display').val(
                            (apInvoice.doc_num ? '#' + apInvoice.doc_num : '-') +
                            (apInvoice.doc_entry ? ' / Entry ' + apInvoice.doc_entry : '')
                        );
                        $('#sap_ap_total_display').val(apInvoice.doc_total != null ? formatCurrency(
                            apInvoice.doc_total) : '-');
                        $('#sap_paid_to_date_display').val(apInvoice.paid_to_date != null ? formatCurrency(
                            apInvoice.paid_to_date) : formatCurrency(0));
                        sapRemainingBalance = Math.round(parseFloat(apInvoice.remaining_balance || 0));
                        $('#sap_remaining_balance_display').val(formatCurrency(sapRemainingBalance));
                        $('#sap_payment_amount').attr('max', sapRemainingBalance).val(sapRemainingBalance);
                        if (invoice.payment_date) {
                            $('#sap_payment_date').val(invoice.payment_date);
                        }

                        if (preview.is_partial || sapRemainingBalance < parseFloat(row.amount || 0) - 0.5) {
                            $('#sapPartialPaymentHint').removeClass('d-none');
                        }

                        const accounts = response.accounts || [];
                        let options = '<option value="">Select account</option>';
                        accounts.forEach(function(account) {
                            options += '<option value="' + account.id + '">' + escapeAttr(
                                account.label) + '</option>';
                        });
                        $('#sap_account_id').html(options);

                        if (preview.amount_mismatch) {
                            $('#sapPaymentMismatchAlert').removeClass('d-none');
                        }

                        $('#sapPaymentSubmitBtn').prop('disabled', accounts.length === 0);
                        if (accounts.length === 0) {
                            showSapPreviewError('No accounts',
                                'No cash/bank accounts with SAP mapping are available.');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        showSapPreviewError(response.error || 'Preview failed', response.message ||
                            'Could not resolve the SAP AP Invoice.');
                        $('#sap_ap_doc_display').val('-');
                        $('#sap_account_id').html('<option value="">Select account</option>');
                        $('#sapPaymentSubmitBtn').prop('disabled', true);
                    }
                });
            }

            function confirmSapPayment() {
                const invoiceId = $('#sap_invoice_id').val();
                const closeDdsOnly = $('#sap_close_dds_only').val() === '1';
                const submitBtn = $('#sapPaymentSubmitBtn');
                const originalHtml = submitBtn.html();
                const postingLabel = closeDdsOnly ? 'Closing...' : (sapPaymentContext === 'waiting' ?
                    'Paying...' : 'Posting...');
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> ' + postingLabel).prop('disabled', true);

                const payload = {
                    invoice_number: $('#sap_invoice_number_display').val(),
                    supplier_sap_code: $('#sap_supplier_code').val(),
                    amount: $('#sap_invoice_amount').val(),
                    payment_amount: $('#sap_payment_amount').val(),
                    payment_date: $('#sap_payment_date').val(),
                    remarks: $('#sap_payment_remarks').val(),
                    payment_project: $('#sap_payment_project').val(),
                    sap_doc: $('#sap_invoice_sap_doc').val(),
                    close_invoice_in_dds: $('#sap_close_invoice_in_dds').val() === '1' ? 1 : 0,
                    close_dds_only: closeDdsOnly ? 1 : 0
                };

                if (!closeDdsOnly) {
                    payload.payment_means = $('#sap_payment_means').val();
                    payload.account_id = $('#sap_account_id').val();
                    payload.prepared_by = $('#sap_prepared_by').val();
                    payload.approved_by = $('#sap_approved_by').val();
                }

                $.ajax({
                    url: sapSubmitUrlTemplate.replace(':invoiceId', invoiceId),
                    method: 'POST',
                    data: payload,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        const icon = response.warning ? 'warning' : 'success';
                        const title = response.warning ? 'Posted with warning' : (closeDdsOnly ?
                            'Invoice closed' : (sapPaymentContext === 'waiting' ? 'Paid' :
                                'Posted to SAP'));

                        Swal.fire({
                            icon: icon,
                            title: title,
                            text: response.message,
                            timer: response.warning ? 5000 : 2500,
                            showConfirmButton: !!response.warning
                        });
                        $('#sapPaymentModal').modal('hide');
                        applyFilters();
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        Swal.fire({
                            icon: 'error',
                            title: response.error || 'SAP posting failed',
                            text: response.message || 'Failed to post outgoing payment to SAP B1.'
                        });
                    },
                    complete: function() {
                        submitBtn.html(originalHtml).prop('disabled', false);
                    }
                });
            }

            function showSapPreviewError(title, message) {
                $('#sapPaymentPreviewAlertTitle').text(title || 'Error');
                $('#sapPaymentPreviewAlertMessage').text(message || 'An unexpected error occurred.');
                $('#sapPaymentPreviewAlert').removeClass('d-none');
            }
        });
    </script>
@endpush
