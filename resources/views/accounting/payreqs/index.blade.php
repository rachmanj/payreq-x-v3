@extends('templates.main')

@section('title_page')
    Project Payreqs
@endsection

@section('breadcrumb_title')
    Payreqs / Ongoing
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Project Payreqs</h3>
                    <div class="loading-indicator d-none">
                        <div class="spinner-border text-primary spinner-border-sm" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div id="error-message" class="alert alert-danger d-none mb-3">
                        An error occurred while loading data. <a href="#" id="retry-button">Retry</a>
                    </div>

                    <div class="table-responsive">
                        <table id="all-payreqs" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Employee</th>
                                    <th width="10%">Project</th>
                                    <th width="10%">PayreqNo</th>
                                    <th width="10%">RealzNo</th>
                                    <th width="10%">CreatedD</th>
                                    <th width="10%">Type</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">IDR</th>
                                    <th width="10%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="10" class="text-center">Loading data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- DataTables - Minimal CSS -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <style>
        .loading-indicator {
            margin-left: 10px;
        }

        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #ddd !important;
            border-radius: 3px !important;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1) !important;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables - Core JS Only -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadingIndicator = document.querySelector('.loading-indicator');
            const errorMessage = document.getElementById('error-message');
            const retryButton = document.getElementById('retry-button');
            let dataTable;

            // Initialize DataTable
            function initDataTable() {
                // Show loading indicator
                loadingIndicator.classList.remove('d-none');
                errorMessage.classList.add('d-none');

                // If table was already initialized, destroy it first
                if (dataTable) {
                    dataTable.destroy();
                }

                // Initialize DataTable with optimized settings
                dataTable = $("#all-payreqs").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('accounting.payreqs.data') }}',
                        type: 'GET',
                        // Add cache busting parameter to prevent browser caching
                        data: function(d) {
                            d.timestamp = new Date().getTime();
                            return d;
                        },
                        // Handle errors
                        error: function(xhr, error, thrown) {
                            console.error('DataTables error:', error);
                            errorMessage.classList.remove('d-none');
                            loadingIndicator.classList.add('d-none');
                        },
                        // Handle completion
                        complete: function() {
                            loadingIndicator.classList.add('d-none');
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'employee'
                        },
                        {
                            data: 'project'
                        },
                        {
                            data: 'nomor'
                        },
                        {
                            data: 'realization_no'
                        },
                        {
                            data: 'created_at'
                        },
                        {
                            data: 'type'
                        },
                        {
                            data: 'status'
                        },
                        {
                            data: 'amount'
                        },
                        {
                            data: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    columnDefs: [{
                            "targets": [2, 3, 4],
                            "className": "text-center"
                        },
                        {
                            "targets": [8],
                            "className": "text-right"
                        }
                    ],
                    // Performance optimizations
                    deferRender: true,
                    orderCellsTop: true,
                    paging: true,
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    // Disable initial sorting for faster initial load
                    order: [],
                    // Disable features we don't need
                    autoWidth: false,
                    searching: true,
                    stateSave: false, // Disable state saving to prevent issues
                    // Optimize rendering
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                        lengthMenu: "Show _MENU_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        },
                        emptyTable: "No data available"
                    }
                });
            }

            // Initialize DataTable
            initDataTable();

            // Add retry functionality
            retryButton.addEventListener('click', function(e) {
                e.preventDefault();
                initDataTable();
            });

            // Add responsive behavior for window resize
            window.addEventListener('resize', function() {
                if (dataTable) {
                    dataTable.columns.adjust();
                }
            });
        });
    </script>
@endsection
