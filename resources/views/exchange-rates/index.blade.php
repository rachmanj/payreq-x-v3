@extends('templates.main')

@section('title_page')
    Exchange Rates
@endsection

@section('breadcrumb_title')
    Exchange Rates
@endsection

@section('content')
    {{-- Import Validation Errors --}}
    @if (session()->has('failures'))
        <div class="row">
            <div class="col-12">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="icon fas fa-exclamation-triangle"></i>
                            Import Validation Errors
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 10%">Row</th>
                                        <th style="width: 25%">Column</th>
                                        <th style="width: 25%">Value</th>
                                        <th>Error Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (session()->get('failures') as $failure)
                                        <tr>
                                            <td class="text-center">{{ $failure['row'] }}</td>
                                            <td>
                                                <strong>{{ ucwords(str_replace('_', ' ', $failure['attribute'])) }}</strong>
                                            </td>
                                            <td>
                                                @if (isset($failure['value']) && $failure['value'] !== null)
                                                    <code>{{ $failure['value'] }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-danger">{{ $failure['errors'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Please correct these errors in your Excel file and try importing again.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Exchange Rates List</h3>
                    <div class="card-tools">
                        @can('create_exchange_rates')
                            <a href="{{ route('accounting.exchange-rates.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add New Exchange Rate
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body">
                    <form method="GET" action="{{ route('accounting.exchange-rates.index') }}" id="filterForm">
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label for="currency_from">Currency From</label>
                                <select name="currency_from" id="currency_from" class="form-control">
                                    <option value="">All Currencies</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->currency_code }}"
                                            {{ request('currency_from') == $currency->currency_code ? 'selected' : '' }}>
                                            {{ $currency->currency_code }} - {{ $currency->currency_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="currency_to">Currency To</label>
                                <select name="currency_to" id="currency_to" class="form-control">
                                    <option value="">All Currencies</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->currency_code }}"
                                            {{ request('currency_to') == $currency->currency_code ? 'selected' : '' }}>
                                            {{ $currency->currency_code }} - {{ $currency->currency_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from">Date From</label>
                                <input type="date" name="date_from" id="date_from" class="form-control"
                                    value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to">Date To</label>
                                <input type="date" name="date_to" id="date_to" class="form-control"
                                    value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="fas fa-search"></i> Apply
                                    </button>
                                    <a href="{{ route('accounting.exchange-rates.index') }}"
                                        class="btn btn-secondary btn-sm">
                                        <i class="fas fa-undo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Bulk Actions -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="btn-group" id="bulkActions" style="display: none;">
                                @can('edit_exchange_rates')
                                    <button type="button" class="btn btn-warning btn-sm" id="bulkUpdateBtn">
                                        <i class="fas fa-edit"></i> Bulk Update
                                    </button>
                                @endcan
                                @can('delete_exchange_rates')
                                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn">
                                        <i class="fas fa-trash"></i> Bulk Delete
                                    </button>
                                @endcan
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            @can('export_exchange_rates')
                                <button type="button" class="btn btn-success btn-sm" id="exportBtn">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                            @endcan
                            @can('import_exchange_rates')
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal"
                                    data-target="#importModal">
                                    <i class="fas fa-file-import"></i> Import Excel
                                </button>
                            @endcan
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="exchangeRatesTable">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center align-middle">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th class="align-middle">Foreign Currency</th>
                                    {{-- <th class="align-middle">Currency To</th> --}}
                                    <th class="align-middle">Exchange Rate</th>
                                    <th class="align-middle">Effective Date</th>
                                    <th class="align-middle">Created By</th>
                                    <th class="align-middle">Created At</th>
                                    <th class="align-middle">Updated At</th>
                                    <th width="120" class="text-center align-middle">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($exchangeRates as $rate)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="row-checkbox" value="{{ $rate->id }}">
                                        </td>
                                        <td>{{ $rate->currency_from }}</td>
                                        {{-- <td>{{ $rate->currency_to }}</td> --}}
                                        <td class="text-right">{{ number_format($rate->exchange_rate, 2) }}</td>
                                        <td>{{ $rate->effective_date->format('d-M-Y') }}</td>
                                        <td>{{ $rate->creator->name ?? 'N/A' }}</td>
                                        <td>{{ $rate->created_at->format('d-M-Y H:i') }}</td>
                                        <td>{{ $rate->updated_at->format('d-M-Y H:i') }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="{{ route('accounting.exchange-rates.show', $rate->id) }}"
                                                    class="btn btn-xs btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('edit_exchange_rates')
                                                    <a href="{{ route('accounting.exchange-rates.edit', $rate->id) }}"
                                                        class="btn btn-xs btn-warning ml-2" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete_exchange_rates')
                                                    <form action="{{ route('accounting.exchange-rates.destroy', $rate->id) }}"
                                                        method="POST" style="display: inline;" class="delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger ml-2"
                                                            title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No exchange rates found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="row">
                        <div class="col-md-12">
                            {{ $exchangeRates->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Import Exchange Rates from Excel</h4>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form action="{{ route('accounting.exchange-rates.import') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>1. Download Template:</label>
                            <div>
                                @can('import_exchange_rates')
                                    <a href="{{ route('accounting.exchange-rates.template') }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-download"></i> Download Template
                                    </a>
                                @else
                                    <span class="text-muted">You don't have permission to download template</span>
                                @endcan
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="excel_file">2. Upload File:</label>
                            <input type="file" name="excel_file" id="excel_file" class="form-control"
                                accept=".xlsx,.xls,.csv" required>
                            <small class="text-muted">Supported formats: .xlsx, .xls, .csv (Max: 10MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Bulk Update Exchange Rates</h4>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="bulkUpdateForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="bulk_exchange_rate">New Exchange Rate:</label>
                            <input type="number" name="exchange_rate" id="bulk_exchange_rate" class="form-control"
                                step="0.000001" min="0.000001" required>
                        </div>
                        <p class="text-muted">
                            This will update <span id="selectedCount">0</span> selected records.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Selected
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
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <style>
        .table-responsive {
            overflow-x: auto;
        }

        .bulk-actions {
            margin-bottom: 1rem;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }

        .btn-group .btn {
            margin-right: 0.25rem;
        }

        .row-checkbox {
            transform: scale(1.2);
        }

        #selectAll {
            transform: scale(1.2);
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
            color: #007bff;
        }

        .card-danger .card-header {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .card-danger .card-header .card-title {
            color: #fff;
        }

        .table-sm td {
            padding: 0.5rem;
        }

        .table-sm code {
            font-size: 0.85em;
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            // Initialize DataTable if needed for future server-side processing
            // Currently using static table with server-side pagination from Laravel

            // Select All functionality
            $('#selectAll').change(function() {
                $('.row-checkbox').prop('checked', this.checked);
                toggleBulkActions();
            });

            // Individual checkbox change
            $('.row-checkbox').change(function() {
                if (!this.checked) {
                    $('#selectAll').prop('checked', false);
                }

                // Check if all checkboxes are selected
                if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                }

                toggleBulkActions();
            });

            // Toggle bulk actions visibility
            function toggleBulkActions() {
                const selectedCount = $('.row-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('#bulkActions').show();
                    $('#selectedCount').text(selectedCount);
                } else {
                    $('#bulkActions').hide();
                }
            }

            // Bulk Update
            $('#bulkUpdateBtn').click(function() {
                const selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    alert('Please select at least one record to update.');
                    return;
                }

                $('#selectedCount').text(selectedIds.length);
                $('#bulkUpdateModal').modal('show');
            });

            // Bulk Update Form Submit
            $('#bulkUpdateForm').submit(function(e) {
                e.preventDefault();

                const selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                const formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('_method', 'PUT');
                formData.append('ids', JSON.stringify(selectedIds));
                formData.append('exchange_rate', $('#bulk_exchange_rate').val());

                showLoadingOverlay();

                fetch('{{ route('accounting.exchange-rates.bulk-update') }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoadingOverlay();
                        $('#bulkUpdateModal').modal('hide');

                        if (data.success) {
                            showAlert('success', data.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('danger', data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        hideLoadingOverlay();
                        showAlert('danger', 'An error occurred while updating records');
                        console.error('Error:', error);
                    });
            });

            // Bulk Delete
            $('#bulkDeleteBtn').click(function() {
                const selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    alert('Please select at least one record to delete.');
                    return;
                }

                if (confirm(`Are you sure you want to delete ${selectedIds.length} selected record(s)?`)) {
                    const formData = new FormData();
                    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                    formData.append('_method', 'DELETE');
                    formData.append('ids', JSON.stringify(selectedIds));

                    showLoadingOverlay();

                    fetch('{{ route('accounting.exchange-rates.bulk-delete') }}', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            hideLoadingOverlay();

                            if (data.success) {
                                showAlert('success', data.message);
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showAlert('danger', data.message || 'An error occurred');
                            }
                        })
                        .catch(error => {
                            hideLoadingOverlay();
                            showAlert('danger', 'An error occurred while deleting records');
                            console.error('Error:', error);
                        });
                }
            });

            // Delete single record
            $('.delete-form').submit(function(e) {
                e.preventDefault();

                if (confirm('Are you sure you want to delete this exchange rate?')) {
                    this.submit();
                }
            });

            // Clear failures when opening import modal
            $('#importModal').on('show.bs.modal', function() {
                $('#excel_file').val('');
                $('.alert').remove(); // Remove any existing alerts
            });

            // Excel file validation
            $('#excel_file').change(function() {
                const file = this.files[0];
                if (file) {
                    const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel', 'text/csv'
                    ];
                    const maxSize = 10 * 1024 * 1024; // 10MB

                    if (!validTypes.includes(file.type)) {
                        showAlert('danger', 'Please select a valid Excel file (.xlsx, .xls, .csv)');
                        this.value = '';
                        return;
                    }

                    if (file.size > maxSize) {
                        showAlert('danger', 'File size must be less than 10MB');
                        this.value = '';
                        return;
                    }
                }
            });

            // Export with filters
            $('#exportBtn').click(function() {
                const exportForm = $('<form>', {
                    'method': 'GET',
                    'action': '{{ route('accounting.exchange-rates.export') }}'
                });

                // Add current filter values to export form
                const currencyFrom = $('#currency_from').val();
                const currencyTo = $('#currency_to').val();
                const dateFrom = $('#date_from').val();
                const dateTo = $('#date_to').val();

                if (currencyFrom) {
                    exportForm.append($('<input>', {
                        'type': 'hidden',
                        'name': 'currency_from',
                        'value': currencyFrom
                    }));
                }

                if (currencyTo) {
                    exportForm.append($('<input>', {
                        'type': 'hidden',
                        'name': 'currency_to',
                        'value': currencyTo
                    }));
                }

                if (dateFrom) {
                    exportForm.append($('<input>', {
                        'type': 'hidden',
                        'name': 'date_from',
                        'value': dateFrom
                    }));
                }

                if (dateTo) {
                    exportForm.append($('<input>', {
                        'type': 'hidden',
                        'name': 'date_to',
                        'value': dateTo
                    }));
                }

                // Submit form to download file
                exportForm.appendTo('body').submit().remove();
            });

            // Helper functions
            function showLoadingOverlay() {
                if (!$('.loading-overlay').length) {
                    $('body').append(`
                        <div class="loading-overlay">
                            <div class="spinner-border loading-spinner" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    `);
                }
                $('.loading-overlay').show();
            }

            function hideLoadingOverlay() {
                $('.loading-overlay').hide();
            }

            function showAlert(type, message) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                `;

                // Remove existing alerts
                $('.alert').remove();

                // Add new alert at the top of content
                $('.content').prepend(alertHtml);

                // Auto hide after 5 seconds
                setTimeout(() => {
                    $('.alert').fadeOut();
                }, 5000);
            }
        });
    </script>
@endsection
