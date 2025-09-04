@extends('templates.main')

@section('title_page')
    Invoice Payment
@endsection

@section('breadcrumb_title')
    Invoice Payment
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="invoicePaymentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="dashboard-tab" data-toggle="tab" href="#dashboard" role="tab"
                                aria-controls="dashboard" aria-selected="true">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="waiting-tab" data-toggle="tab" href="#waiting" role="tab"
                                aria-controls="waiting" aria-selected="false">
                                <i class="fas fa-clock"></i> Waiting Payment
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="paid-tab" data-toggle="tab" href="#paid" role="tab"
                                aria-controls="paid" aria-selected="false">
                                <i class="fas fa-check-circle"></i> Paid Invoices
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="invoicePaymentTabContent">
                        <!-- Dashboard Tab -->
                        <div class="tab-pane fade show active" id="dashboard" role="tabpanel"
                            aria-labelledby="dashboard-tab">
                            <div class="row" id="dashboardCards">
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h3 id="totalInvoices">-</h3>
                                            <p>Total Invoices</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-file-invoice"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h3 id="waitingInvoices">-</h3>
                                            <p>Waiting Payment</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-success">
                                        <div class="inner">
                                            <h3 id="paidInvoices">-</h3>
                                            <p>Paid Invoices</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-6">
                                    <div class="small-box bg-danger">
                                        <div class="inner">
                                            <h3 id="overdueInvoices">-</h3>
                                            <p>Overdue Invoices</p>
                                        </div>
                                        <div class="icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-money-bill-wave"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Waiting Amount</span>
                                            <span class="info-box-number" id="totalWaitingAmount">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Paid Amount</span>
                                            <span class="info-box-number" id="totalPaidAmount">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Waiting Payment Tab -->
                        <div class="tab-pane fade" id="waiting" role="tabpanel" aria-labelledby="waiting-tab">
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-primary" id="refreshWaitingBtn">
                                    <i class="fas fa-sync-alt"></i> Refresh Table
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="waitingTable">
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

                        <!-- Paid Invoices Tab -->
                        <div class="tab-pane fade" id="paid" role="tabpanel" aria-labelledby="paid-tab">
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-primary" id="refreshPaidBtn">
                                    <i class="fas fa-sync-alt"></i> Refresh Table
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="paidTable">
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
    </section>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Mark Invoice as Paid</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="paymentForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_number_display">Invoice Number</label>
                                    <input type="text" class="form-control" id="invoice_number_display" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_display">Supplier</label>
                                    <input type="text" class="form-control" id="supplier_display" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_display">Amount</label>
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_project">Payment Project</label>
                                    <input type="text" class="form-control" id="payment_project"
                                        name="payment_project" placeholder="Enter project code">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sap_doc">SAP Document</label>
                                    <input type="text" class="form-control" id="sap_doc" name="sap_doc"
                                        placeholder="Enter SAP document reference">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter payment remarks"></textarea>
                        </div>
                        <input type="hidden" id="invoice_id" name="invoice_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Payment
                        </button>
                    </div>
                </form>
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

@push('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Load dashboard data on page load
            loadDashboardData();

            // Initialize DataTables
            let waitingTable = null;
            let paidTable = null;

            // Initialize tables immediately for better refresh handling
            initializeWaitingTable();
            initializePaidTable();

            // Tab change event (keep for future reference)
            $('#invoicePaymentTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr("href");
                // Tables are already initialized, just ensure they're visible
            });

            // Refresh button handlers
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

            function loadDashboardData() {
                $.ajax({
                    url: '{{ route('cashier.invoice-payment.dashboard') }}',
                    method: 'GET',
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
                        console.error('Dashboard data loading failed:', xhr);
                        showError('Failed to load dashboard data');
                    }
                });
            }

            function initializeWaitingTable() {
                waitingTable = $("#waitingTable").DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '{{ route('cashier.invoice-payment.waiting') }}',
                        dataSrc: 'invoices'
                    },
                    columns: [{
                            data: null,
                            orderable: true,
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
                            render: function(data, type, row) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'receive_date',
                            render: function(data, type, row) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'days_diff',
                            className: 'text-right',
                            render: function(data, type, row) {
                                const daysClass = data > 30 ? 'text-danger' : data > 15 ?
                                    'text-warning' : 'text-success';
                                return '<span class="' + daysClass + ' font-weight-bold">' + data +
                                    '</span>';
                            }
                        },
                        {
                            data: 'status',
                            render: function(data, type, row) {
                                return '<span class="badge badge-warning">' + data + '</span>';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return '<button class="btn btn-sm btn-success mark-paid-btn" data-invoice-id="' +
                                    row.id + '" data-invoice-number="' + row.invoice_number +
                                    '" data-supplier="' + row.supplier_name + '" data-amount="' +
                                    row.amount +
                                    '"><i class="fas fa-check"></i> Mark Paid</button>';
                            }
                        }
                    ],
                    order: [
                        [6, 'desc']
                    ], // Sort by receive date descending
                    pageLength: 25,
                    responsive: true,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        zeroRecords: "<div class='text-center'>No matching records found</div>"
                    }
                });
            }

            function initializePaidTable() {
                paidTable = $("#paidTable").DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '{{ route('cashier.invoice-payment.paid') }}',
                        dataSrc: 'invoices'
                    },
                    columns: [{
                            data: null,
                            orderable: true,
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
                            render: function(data, type, row) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'receive_date',
                            render: function(data, type, row) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'payment_date',
                            render: function(data, type, row) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'days_diff',
                            className: 'text-right',
                            render: function(data, type, row) {
                                return '<span class="text-success">' + data + '</span>';
                            }
                        },
                        {
                            data: 'status',
                            render: function(data, type, row) {
                                return '<span class="badge badge-success">' + data + '</span>';
                            }
                        }
                    ],
                    order: [
                        [7, 'desc']
                    ], // Sort by payment date descending
                    pageLength: 25,
                    responsive: true,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        zeroRecords: "<div class='text-center'>No matching records found</div>"
                    }
                });
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
                if (!dateString) return '-';
                const date = new Date(dateString);
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const day = date.getDate().toString().padStart(2, '0');
                const month = months[date.getMonth()];
                const year = date.getFullYear();
                return `${day}-${month}-${year}`;
            }

            function showError(message) {
                console.error(message);
            }

            // Payment Modal Handlers
            $(document).on('click', '.mark-paid-btn', function() {
                const invoiceId = $(this).data('invoice-id');
                const invoiceNumber = $(this).data('invoice-number');
                const supplier = $(this).data('supplier');
                const amount = $(this).data('amount');

                // Populate modal fields
                $('#invoice_id').val(invoiceId);
                $('#invoice_number_display').val(invoiceNumber);
                $('#supplier_display').val(supplier);
                $('#amount_display').val(formatCurrency(amount));
                $('#payment_date').val(new Date().toISOString().split('T')[0]); // Set today's date

                // Clear other fields
                $('#payment_project').val('');
                $('#sap_doc').val('');
                $('#remarks').val('');

                // Show modal
                $('#paymentModal').modal('show');
            });

            // Handle payment form submission
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                const invoiceId = $('#invoice_id').val();
                const formData = {
                    payment_date: $('#payment_date').val(),
                    status: 'paid',
                    remarks: $('#remarks').val(),
                    payment_project: $('#payment_project').val(),
                    sap_doc: $('#sap_doc').val()
                };

                // Remove empty values
                Object.keys(formData).forEach(key => {
                    if (formData[key] === '') {
                        delete formData[key];
                    }
                });

                // Show loading state
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
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Close modal
                            $('#paymentModal').modal('hide');

                            // Add small delay to ensure DDS API has processed the update
                            setTimeout(function() {
                                // Refresh tables
                                console.log('Refreshing tables...');
                                if (waitingTable) {
                                    console.log('Refreshing waiting table...');
                                    waitingTable.ajax.reload(null,
                                        false); // false = stay on current page
                                } else {
                                    console.log('Waiting table not initialized');
                                }
                                if (paidTable) {
                                    console.log('Refreshing paid table...');
                                    paidTable.ajax.reload(null,
                                        false); // false = stay on current page
                                } else {
                                    console.log('Paid table not initialized');
                                }

                                // Refresh dashboard
                                loadDashboardData();
                            }, 1000); // 1 second delay
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
                        // Restore button state
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
