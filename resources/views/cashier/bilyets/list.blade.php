@extends('templates.main')

@section('title_page')
    BILYET
@endsection

@section('breadcrumb_title')
    bilyet
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Navigation Links -->
            <x-bilyet-links page="list" />

            <!-- Filter Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-search"></i> Filter Data Bilyet
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="filter-form">
                        <div class="row">
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label for="status-filter">Status</label>
                                    <select class="form-control form-control-sm" id="status-filter">
                                        <option value="">Semua Status</option>
                                        <option value="onhand">Onhand</option>
                                        <option value="release">Release</option>
                                        <option value="cair">Cair</option>
                                        <option value="void">Void</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label for="giro-filter">Bank Account</label>
                                    <select class="form-control form-control-sm" id="giro-filter">
                                        <option value="">Semua Giro</option>
                                        @foreach ($giros as $giro)
                                            <option value="{{ $giro->id }}">
                                                {{ $giro->bank->name ?? 'N/A' }} - {{ $giro->acc_no }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label for="nomor-filter">Nomor Bilyet</label>
                                    <input type="text" class="form-control form-control-sm" id="nomor-filter"
                                        placeholder="Cari nomor bilyet...">
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label for="date-from">Tanggal Dari</label>
                                    <input type="date" class="form-control form-control-sm" id="date-from">
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                                <div class="form-group">
                                    <label for="date-to">Tanggal Sampai</label>
                                    <input type="date" class="form-control form-control-sm" id="date-to">
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label for="amount-from">Amount From</label>
                                    <input type="number" class="form-control form-control-sm" id="amount-from"
                                        placeholder="Min amount">
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label for="amount-to">Amount To</label>
                                    <input type="number" class="form-control form-control-sm" id="amount-to"
                                        placeholder="Max amount">
                                </div>
                            </div>
                            <div class="col-xl-1 col-lg-12 col-md-8 col-sm-12">
                                <div class="form-group text-right">
                                    <label class="d-none d-sm-block">&nbsp;</label>
                                    <div class="btn-group-vertical btn-group-sm d-block d-sm-none mb-2">
                                        <button type="button" class="btn btn-primary btn-block btn-apply-filter">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-block btn-reset-filter">
                                            <i class="fas fa-undo"></i> Reset
                                        </button>
                                    </div>
                                    <div class="btn-group btn-group-sm d-none d-sm-block">
                                        <button type="button" class="btn btn-primary btn-apply-filter">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-reset-filter">
                                            <i class="fas fa-undo"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Auto-Sum Summary Panel -->
            <div class="card card-info mt-3" id="sum-panel" style="display: none;">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator"></i> Selected Summary
                            </h5>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-light" id="clear-selection" title="Clear Selection">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body py-3">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <small class="text-muted d-block">Selected Items</small>
                                <div class="h4 mb-0 text-primary" id="selected-count">0</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <small class="text-muted d-block">Total Amount</small>
                                <div class="h4 mb-0 text-success" id="selected-sum">Rp 0,-</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <small class="text-muted d-block">Average</small>
                                <div class="h4 mb-0 text-info" id="selected-average">Rp 0,-</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="text-center">
                                <small class="text-muted d-block">Status Mix</small>
                                <div class="h6 mb-0" id="status-mix">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Keyboard Shortcuts Info -->
                <div class="card-footer py-2">
                    <div class="text-muted small text-center">
                        <i class="fas fa-keyboard"></i>
                        <strong>Shortcuts:</strong>
                        Ctrl+A (Select All) • Esc (Clear Selection)
                    </div>
                </div>
            </div>

            <!-- Data Table Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Bilyet List</h3>
                    @can('add_bilyet')
                        <button href="#" class="btn btn-sm btn-warning float-right" data-toggle="modal"
                            data-target="#modal-update-many"> Update Many</button>
                        <button href="#" class="btn btn-sm btn-success float-right mr-2" data-toggle="modal"
                            data-target="#modal-create"><i class="fas fa-plus"></i> Bilyet</button>
                        {{-- <a href="{{ route('cashier.bilyets.export') }}" class="btn btn-xs btn-primary float-right"> download template</a> --}}
                    @endcan
                </div> <!-- /.card-header -->
                <div class="card-body">
                    <!-- Info Message for Empty Data -->
                    <div id="empty-data-message" class="alert alert-info text-center" style="display: block;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Gunakan Filter</strong> di atas untuk menampilkan data bilyet.
                        <br><small class="text-white">Pilih status, nomor bilyet, atau rentang tanggal untuk memulai
                            pencarian.</small>
                    </div>

                    <div class="table-responsive">
                        <table id="bilyets-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nomor</th>
                                    <th>Bank | Account</th>
                                    <th class="text-center" style="width: 5%;">Type</th>
                                    <th>BilyetD</th>
                                    <th>CairD</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right" style="width: 13%;">IDR</th>
                                    <th class="text-center" style="width: 5%;">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="select-all-checkbox">
                                                <label class="custom-control-label" for="select-all-checkbox"></label>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->

    {{-- Modal create --}}
    <div class="modal fade" id="modal-create">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> New Bilyet</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('cashier.bilyets.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="create_prefix">Prefix</label>
                                    <input type="hidden" name="project" value="{{ auth()->user()->project }}">
                                    <input name="prefix" id="create_prefix"
                                        class="form-control @error('prefix') is-invalid @enderror">
                                    @error('prefix')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="create_nomor">Bilyet No</label>
                                    <input name="nomor" id="create_nomor"
                                        class="form-control @error('nomor') is-invalid @enderror">
                                    @error('nomor')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="create_type">Bilyet Type</label>
                                    <select name="type" id="create_type" class="form-control select2bs4">
                                        <option value="cek">Cek</option>
                                        <option value="bilyet">BG</option>
                                        <option value="loa">LOA</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="create_giro_id">Giro</label>
                            <select name="giro_id" id="create_giro_id" class="form-control select2bs4">
                                @foreach ($giros as $giro)
                                    <option value="{{ $giro->id }}">{{ $giro->acc_no . ' - ' . $giro->acc_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="create_bilyet_date">Bilyet Date</label>
                                    <input type="date" name="bilyet_date" id="create_bilyet_date"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="create_cair_date">Cair Date</label>
                                    <input type="date" name="cair_date" id="create_cair_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="create_remarks">Remarks</label>
                            <textarea name="remarks" id="create_remarks" class="form-control"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="create_amount">Amount</label>
                                    <input type="number" name="amount" id="create_amount" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="create_file_upload">Upload bilyet <small>(optional)</small></label>
                                    <input type="file" name="file_upload" id="create_file_upload"
                                        class="form-control">
                                </div>
                            </div>
                        </div>

                    </div> <!-- /.modal-body -->
                    <div class="modal-footer float-left">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            </div> <!-- /.modal-content -->
        </div> <!-- /.modal-dialog -->
    </div>

    {{-- Modal Update Many --}}
    <div class="modal fade" id="modal-update-many">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> Update Many</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="{{ route('cashier.bilyets.update_many') }}" method="POST">
                    @csrf

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="update_bilyet_date">Bilyet Date</label>
                                    <input type="hidden" name="from_page" value="list">
                                    <input type="date" name="bilyet_date" id="update_bilyet_date"
                                        class="form-control" value="{{ old('bilyet_date') }}">
                                </div>
                                <div class="form-group">
                                    <label for="update_remarks">Purpose</label>
                                    <textarea name="remarks" id="update_remarks" class="form-control">{{ old('remarks') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="update_amount">Amount</label>
                                    <input type="text" name="amount" id="update_amount" class="form-control"
                                        value="{{ old('amount') }}">
                                </div>
                                <div class="form-group">
                                    <label>Select Bilyets (Onhand only)</label>
                                    <div class="select2-purple">
                                        <select name="bilyet_ids[]" id="update_bilyet_ids" class="form-control select2"
                                            multiple="multiple" data-placeholder="Select Bilyets" style="width: 100%;">
                                            @if (isset($onhands))
                                                @foreach ($onhands as $item)
                                                    <option value="{{ $item->id }}">
                                                        {{ $item->prefix . $item->nomor }} -
                                                        {{ $item->giro->bank->name ?? 'N/A' }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>

            </div> <!-- /.modal-content -->
        </div> <!-- /.modal-dialog -->
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .card-header .active {
            color: black;
            text-transform: uppercase;
        }

        /* Modal Select2 styling */
        .modal .select2-container {
            width: 100% !important;
        }

        .modal .select2-dropdown {
            z-index: 9999;
        }

        .modal .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        .modal .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        /* Filter button transitions */
        .btn-group .btn,
        .btn-group-vertical .btn {
            transition: all 0.3s ease;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1rem;
            }

            .btn-group-vertical .btn {
                margin-bottom: 5px;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .btn-group-sm .btn {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 0.75rem;
            }

            .form-group {
                margin-bottom: 0.75rem;
            }

            .table th,
            .table td {
                padding: 0.5rem;
                vertical-align: middle;
            }
        }

        /* DataTables responsive improvements */
        table.dataTable tbody td {
            word-wrap: break-word;
            word-break: break-word;
        }

        .btn-group-sm .btn {
            margin-right: 2px;
        }

        .btn-group-sm .btn:last-child {
            margin-right: 0;
        }

        /* Loading indicator */
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
        }

        /* ===== CHECKBOX SELECTION STYLING ===== */

        /* Checkbox Styling */
        .bilyet-checkbox {
            transform: scale(1.2);
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .bilyet-checkbox:hover {
            transform: scale(1.3);
        }

        /* Selected Row Highlighting */
        #bilyets-table tbody tr:has(.bilyet-checkbox:checked) {
            background-color: #f8f9fa !important;
            border-left: 3px solid #007bff;
        }

        /* Summary Panel Styling */
        #sum-panel {
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Indeterminate Checkbox Styling */
        #select-all-checkbox:indeterminate {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        /* Breakdown Badges */
        .badge {
            font-size: 0.75em;
            margin-bottom: 2px;
        }

        /* Mobile Responsive Adjustments */
        @media (max-width: 768px) {
            .bilyet-checkbox {
                transform: scale(1.5);
                /* Larger touch targets */
            }

            #sum-panel .card-body .row>div {
                margin-bottom: 15px;
                text-align: center;
            }

            #sum-panel .btn-group {
                width: 100%;
            }

            #sum-panel .btn-group .btn {
                flex: 1;
            }

            /* Simplify breakdown on mobile */
            #status-breakdown .badge,
            #type-breakdown .badge {
                display: block;
                margin: 2px 0;
            }
        }

        @media (max-width: 576px) {

            /* Stack summary items vertically on small screens */
            #sum-panel .card-body .row {
                text-align: center;
            }

            /* Hide detailed statistics on very small screens */
            #sum-panel .card-body .row.mt-3 {
                display: none;
            }
        }

        /* Loading Animation */
        .selection-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Accessibility Improvements */
        .bilyet-checkbox:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
        }

        /* Custom scrollbar for summary panel */
        #sum-panel .card-body {
            max-height: 300px;
            overflow-y: auto;
        }

        #sum-panel .card-body::-webkit-scrollbar {
            width: 6px;
        }

        #sum-panel .card-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #sum-panel .card-body::-webkit-scrollbar-thumb {
            background: #17a2b8;
            border-radius: 3px;
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
            // Selection state management
            let selectedBilyets = new Set();
            let bilyetDataCache = {}; // Cache bilyet data for statistics

            // Initialize DataTable
            var table = $('#bilyets-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('cashier.bilyets.data') }}",
                    data: function(d) {
                        d.status = $('#status-filter').val();
                        d.giro_id = $('#giro-filter').val();
                        d.nomor = $('#nomor-filter').val();
                        d.date_from = $('#date-from').val();
                        d.date_to = $('#date-to').val();
                        d.amount_from = $('#amount-from').val();
                        d.amount_to = $('#amount-to').val();
                    },
                    dataSrc: function(json) {
                        // Cache data for statistics calculation
                        json.data.forEach(function(item, index) {
                            if (item.checkbox && item.checkbox.includes('data-id')) {
                                const match = item.checkbox.match(/data-id="(\d+)"/);
                                if (match) {
                                    bilyetDataCache[match[1]] = {
                                        amount: parseFloat(item.checkbox.match(
                                            /data-amount="([^"]+)"/)?.[1] || 0),
                                        status: item.checkbox.match(/data-status="([^"]+)"/)
                                            ?.[1] || '',
                                        type: item.checkbox.match(/data-type="([^"]+)"/)?.[
                                            1
                                        ] || ''
                                    };
                                }
                            }
                        });
                        return json.data;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX error:', xhr, error, thrown);
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'account'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'bilyet_date'
                    },
                    {
                        data: 'cair_date'
                    },
                    {
                        data: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'amount',
                        className: 'text-right'
                    },
                    {
                        data: 'checkbox',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                drawCallback: function() {
                    // Restore checkbox states after redraw
                    restoreCheckboxStates();
                    updateSummary();
                    updateSelectAllState();

                    // Handle empty data message
                    const data = this.api().data();
                    if (data.length === 0) {
                        $('#empty-data-message').show();
                    } else {
                        $('#empty-data-message').hide();
                    }
                },
                fixedHeader: true,
                columnDefs: [{
                    "targets": [7],
                    "className": "text-right"
                }, {
                    "targets": [8],
                    "className": "text-center"
                }]
            });

            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            // Initialize Select2 for modals
            $('#modal-create').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#create_giro_id, #create_type').select2({
                        theme: 'bootstrap4',
                        dropdownParent: $('#modal-create')
                    });
                }, 100);
            });

            $('#modal-update-many').on('shown.bs.modal', function() {
                setTimeout(function() {
                    $('#update_bilyet_ids').select2({
                        theme: 'bootstrap4',
                        dropdownParent: $('#modal-update-many'),
                        placeholder: 'Select Bilyets',
                        allowClear: true,
                        width: '100%',
                        closeOnSelect: false
                    });
                }, 100);
            });

            // Destroy Select2 when modal is hidden to prevent conflicts
            $('#modal-create').on('hidden.bs.modal', function() {
                $('#create_giro_id, #create_type').select2('destroy');
            });

            $('#modal-update-many').on('hidden.bs.modal', function() {
                $('#update_bilyet_ids').select2('destroy');
            });

            // Apply filter
            $('.btn-apply-filter').on('click', function() {
                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Filtering...');
                $btn.prop('disabled', true);

                // Check if any filter is applied
                const hasFilter = $('#status-filter').val() || $('#giro-filter').val() || $('#nomor-filter')
                    .val() ||
                    $('#date-from').val() || $('#date-to').val() || $('#amount-from').val() || $(
                        '#amount-to').val();

                if (!hasFilter) {
                    $('#empty-data-message').show();
                } else {
                    $('#empty-data-message').hide();
                }

                table.ajax.reload(function() {
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }, false);
            });

            // Event delegation fallback for apply filter
            $(document).on('click', '.btn-apply-filter', function() {
                if (!$(this).hasClass('event-bound')) {
                    $(this).addClass('event-bound');

                    const $btn = $(this);
                    const originalText = $btn.html();
                    $btn.html('<i class="fas fa-spinner fa-spin"></i> Filtering...');
                    $btn.prop('disabled', true);

                    table.ajax.reload(function() {
                        $btn.html(originalText);
                        $btn.prop('disabled', false);
                    }, false);
                }
            });

            // Reset filter
            $('.btn-reset-filter').on('click', function() {
                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Resetting...');
                $btn.prop('disabled', true);

                // Clear all filter inputs
                $('#status-filter').val('');
                $('#giro-filter').val('');
                $('#nomor-filter').val('');
                $('#date-from').val('');
                $('#date-to').val('');
                $('#amount-from').val('');
                $('#amount-to').val('');

                // Show empty message since no filter applied
                $('#empty-data-message').show();

                table.ajax.reload(function() {
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }, false);
            });

            // Event delegation fallback for reset filter
            $(document).on('click', '.btn-reset-filter', function() {
                if (!$(this).hasClass('event-bound')) {
                    $(this).addClass('event-bound');

                    const $btn = $(this);
                    const originalText = $btn.html();
                    $btn.html('<i class="fas fa-spinner fa-spin"></i> Resetting...');
                    $btn.prop('disabled', true);

                    // Clear all filter inputs
                    $('#status-filter').val('');
                    $('#giro-filter').val('');
                    $('#nomor-filter').val('');
                    $('#date-from').val('');
                    $('#date-to').val('');
                    $('#amount-from').val('');
                    $('#amount-to').val('');

                    table.ajax.reload(function() {
                        $btn.html(originalText);
                        $btn.prop('disabled', false);
                    }, false);
                }
            });

            // Search on Enter key
            $('#nomor-filter').on('keypress', function(e) {
                if (e.which == 13) {
                    $('.btn-apply-filter').first().click();
                }
            });

            // ===== CHECKBOX SELECTION & AUTO-SUM FUNCTIONALITY =====

            // Select All Checkbox Handler
            $('#select-all-checkbox').on('change', function() {
                const isChecked = $(this).is(':checked');
                const visibleCheckboxes = $('.bilyet-checkbox:visible');

                visibleCheckboxes.each(function() {
                    const checkbox = $(this);
                    const bilyetId = checkbox.data('id');

                    if (isChecked) {
                        selectedBilyets.add(bilyetId.toString());
                        checkbox.prop('checked', true);
                    } else {
                        selectedBilyets.delete(bilyetId.toString());
                        checkbox.prop('checked', false);
                    }
                });

                updateSummary();
            });

            // Individual Checkbox Handler
            $(document).on('click', '.bilyet-checkbox', function(e) {
                const checkbox = $(this);
                const bilyetId = checkbox.data('id').toString();

                // Normal single selection
                if (checkbox.is(':checked')) {
                    selectedBilyets.add(bilyetId);
                } else {
                    selectedBilyets.delete(bilyetId);
                }

                updateSelectAllState();
                updateSummary();
            });



            // Restore Checkbox States (after pagination/filtering)
            function restoreCheckboxStates() {
                $('.bilyet-checkbox').each(function() {
                    const checkbox = $(this);
                    const bilyetId = checkbox.data('id').toString();

                    if (selectedBilyets.has(bilyetId)) {
                        checkbox.prop('checked', true);
                    }
                });
            }

            // Update Select All State
            function updateSelectAllState() {
                const visibleCheckboxes = $('.bilyet-checkbox:visible');
                const checkedCheckboxes = $('.bilyet-checkbox:visible:checked');

                const selectAllCheckbox = $('#select-all-checkbox');

                if (checkedCheckboxes.length === 0) {
                    selectAllCheckbox.prop('indeterminate', false);
                    selectAllCheckbox.prop('checked', false);
                } else if (checkedCheckboxes.length === visibleCheckboxes.length) {
                    selectAllCheckbox.prop('indeterminate', false);
                    selectAllCheckbox.prop('checked', true);
                } else {
                    selectAllCheckbox.prop('indeterminate', true);
                    selectAllCheckbox.prop('checked', false);
                }
            }

            // Enhanced Auto-Sum Calculation & Statistics
            function updateSummary() {
                let totalAmount = 0;
                let count = 0;
                let statusCount = {};
                let typeCount = {};
                let amounts = [];

                selectedBilyets.forEach(function(bilyetId) {
                    if (bilyetDataCache[bilyetId]) {
                        const data = bilyetDataCache[bilyetId];
                        totalAmount += data.amount;
                        amounts.push(data.amount);
                        count++;

                        // Count by status
                        statusCount[data.status] = (statusCount[data.status] || 0) + 1;

                        // Count by type
                        typeCount[data.type] = (typeCount[data.type] || 0) + 1;
                    }
                });

                // Update basic summary
                $('#selected-count').text(count);
                $('#selected-sum').text(formatCurrency(totalAmount));
                $('#selected-average').text(count > 0 ? formatCurrency(totalAmount / count) : 'Rp 0,-');

                // Update status mix
                const statusMix = Object.keys(statusCount).map(status =>
                    `${status}: ${statusCount[status]}`
                ).join(' • ');
                $('#status-mix').text(statusMix || '-');

                // Update detailed breakdown
                updateBreakdown('#status-breakdown', statusCount);
                updateBreakdown('#type-breakdown', typeCount);
                updateAmountRange('#amount-range', amounts);

                // Show/hide summary panel with animation
                if (count > 0) {
                    $('#sum-panel').slideDown(300);
                } else {
                    $('#sum-panel').slideUp(300);
                }
            }

            // Helper: Update breakdown display
            function updateBreakdown(selector, countObj) {
                const breakdown = Object.entries(countObj)
                    .map(([key, value]) => `<span class="badge badge-secondary mr-1">${key}: ${value}</span>`)
                    .join('');
                $(selector).html(breakdown || '<span class="text-muted">-</span>');
            }

            // Helper: Update amount range display
            function updateAmountRange(selector, amounts) {
                if (amounts.length === 0) {
                    $(selector).html('<span class="text-muted">-</span>');
                    return;
                }

                const min = Math.min(...amounts);
                const max = Math.max(...amounts);

                if (min === max) {
                    $(selector).html(`<span class="badge badge-info">${formatCurrency(min)}</span>`);
                } else {
                    $(selector).html(`
                        <span class="badge badge-light">Min: ${formatCurrency(min)}</span><br>
                        <span class="badge badge-light">Max: ${formatCurrency(max)}</span>
                    `);
                }
            }

            // Currency Formatter
            function formatCurrency(amount) {
                return 'Rp ' + Math.round(amount).toLocaleString('id-ID') + ',-';
            }

            // Clear Selection Handler
            $('#clear-selection').on('click', function() {
                selectedBilyets.clear();
                $('.bilyet-checkbox').prop('checked', false);
                $('#select-all-checkbox').prop('checked', false).prop('indeterminate', false);
                updateSummary();
            });

            // Export Selected Handler
            $('#export-selected').on('click', function() {
                if (selectedBilyets.size === 0) {
                    alert('Please select bilyets to export');
                    return;
                }

                const selectedIds = Array.from(selectedBilyets);
                const exportUrl = "{{ route('cashier.bilyets.export') }}";

                // Create form and submit
                const form = $('<form method="POST" action="' + exportUrl + '">')
                    .append('<input type="hidden" name="_token" value="{{ csrf_token() }}">')
                    .append('<input type="hidden" name="selected_ids" value="' + selectedIds.join(',') +
                        '">');

                $('body').append(form);
                form.submit();
                form.remove();
            });

            // Keyboard Shortcuts Handler
            $(document).on('keydown', function(e) {
                // Only handle if not typing in input fields
                if ($(e.target).is('input, textarea, select')) return;

                // Ctrl+A to select all visible
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    $('#select-all-checkbox').prop('checked', true).trigger('change');
                }

                // Escape to clear selection
                if (e.key === 'Escape') {
                    $('#clear-selection').click();
                }
            });

            // Integration with existing Update Many functionality
            $('#modal-update-many').on('show.bs.modal', function() {
                if (selectedBilyets.size > 0) {
                    // Pre-populate with selected bilyets (only onhand status)
                    const onhandSelected = Array.from(selectedBilyets).filter(id => {
                        return bilyetDataCache[id] && bilyetDataCache[id].status === 'onhand';
                    });

                    if (onhandSelected.length > 0) {
                        $('#update_bilyet_ids').val(onhandSelected).trigger('change');
                    }
                }
            });
        });
    </script>
    @if (session()->has('bilyet_failed_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var bilyetId = "{{ session('bilyet_failed_id') }}";
                var status = "{{ session('bilyet_failed_status') }}";
                var modalId = null;

                if (status === 'onhand') {
                    modalId = '#bilyet-release-' + bilyetId;
                } else if (status === 'release') {
                    modalId = '#bilyet-cair-' + bilyetId;
                }

                if (modalId) {
                    var modal = $(modalId);
                    if (modal.length) {
                        modal.modal('show');
                    }
                }
            });
        </script>
    @endif
@endsection
