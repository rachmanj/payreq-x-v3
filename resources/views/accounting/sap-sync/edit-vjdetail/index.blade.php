@extends('templates.main')

@section('title_page')
    Edit Detail VJ
@endsection

@section('breadcrumb_title')
    accounting / sap-sync
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice"></i> VJ Details
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('accounting.sap-sync.show', $vj->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text text-muted">VJ Number</span>
                                    <span class="info-box-number">{{ $vj->nomor }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text text-muted">Project</span>
                                    <span class="info-box-number">{{ $vj->project }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text text-muted">Creator</span>
                                    <span class="info-box-number">{{ $vj->createdBy->name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="vj_details" class="table table-bordered table-striped table-hover">
                            <thead class="bg-primary">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Account / Acc Name</th>
                                    <th width="25%">Description</th>
                                    <th width="10%">Project</th>
                                    <th width="10%">Cost Center</th>
                                    <th width="15%">Amount</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will fill this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    <div id="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;"></div>

    <!-- Store CSRF token for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .table thead th {
            vertical-align: middle;
            text-align: center;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }

        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
        }

        /* Fix for modal select2 */
        .select2-container {
            z-index: 9999 !important;
        }

        /* Style for alerts */
        #alert-container .alert {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        /* Amount styling */
        .amount-debit {
            color: #28a745;
            font-weight: bold;
        }

        .amount-credit {
            color: #dc3545;
            font-weight: bold;
        }

        /* Description additional info styling */
        .additional-info {
            display: block;
            color: #6c757d;
            font-style: italic;
            border-top: 1px dashed #dee2e6;
            padding-top: 3px;
            margin-top: 3px;
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
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Format number function
            function formatNumber(number) {
                return new Intl.NumberFormat('id-ID').format(number);
            }

            // Initialize DataTable
            let table = $("#vj_details").DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounting.sap-sync.edit_vjdetail_data', ['vj_id' => $vj->id]) }}',
                    error: function(xhr, error, thrown) {
                        // Handle AJAX errors in DataTable
                        window.showAlert('Error loading data. Please refresh the page.', 'danger');
                        console.error("DataTable error:", error, thrown);
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'akun'
                    },
                    {
                        data: 'description',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                // Check if description contains additional info (indicated by \n)
                                if (data && data.includes('\n')) {
                                    let parts = data.split('\n');
                                    let mainDesc = parts[0];
                                    let additionalInfo = parts[1].replace(/\[|\]/g,
                                        ''); // Remove brackets

                                    return mainDesc + '<small class="additional-info">' +
                                        additionalInfo + '</small>';
                                }
                                return data;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'cost_center',
                        render: function(data, type, row) {
                            return data; // Already formatted with HTML in controller
                        }
                    },
                    {
                        data: 'amount',
                        render: function(data, type, row) {
                            // Format the amount with thousand separators and add class
                            if (type === 'display') {
                                let formattedAmount = formatNumber(Math.abs(parseFloat(data) || 0));
                                let cssClass = row.debit_credit === 'debit' ? 'amount-debit' :
                                    'amount-credit';
                                let prefix = row.debit_credit === 'debit' ? '' : '(';
                                let suffix = row.debit_credit === 'debit' ? '' : ')';
                                return `<span class="${cssClass}">${prefix}${formattedAmount}${suffix}</span>`;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                responsive: true,
                autoWidth: false,
                columnDefs: [{
                        "targets": [0, 3, 4, 5, 6],
                        "className": "text-center"
                    },
                    {
                        "targets": [2],
                        "className": "text-wrap"
                    }
                ],
                language: {
                    processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
                }
            });

            // Function to show alerts
            window.showAlert = function(message, type) {
                const alertDiv = $(`<div class="alert alert-${type} alert-dismissible fade show">
                              <button type="button" class="close" data-dismiss="alert">&times;</button>
                              ${message}
                            </div>`);

                $("#alert-container").append(alertDiv);

                // Auto dismiss after 5 seconds
                setTimeout(function() {
                    alertDiv.alert('close');
                }, 5000);
            }

            // Handle errors for AJAX requests globally
            $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
                // Only handle errors not already handled in specific error callbacks
                if (settings.error === undefined && jqXHR.status !== 0) {
                    console.error("Global AJAX error:", thrownError);
                    console.error("Response text:", jqXHR.responseText);

                    let errorMessage = 'A server error occurred';

                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }

                    // Don't show error if it's a DataTable ajax request (handled separately)
                    if (!settings.url.includes(
                            '{{ route('accounting.sap-sync.edit_vjdetail_data', ['vj_id' => $vj->id]) }}'
                        )) {
                        window.showAlert(errorMessage, 'danger');
                    }
                }
            });
        });
    </script>
@endsection
