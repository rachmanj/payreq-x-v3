@extends('templates.main')

@section('title_page')
    Summary Unit Expense Report via Payreq System
@endsection

@section('breadcrumb_title')
    reports / summary unit expense
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Summary Unit Expense Report via Payreq System</h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm mr-2" style="width: 120px;">
                            <select id="yearFilter" class="form-control">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" {{ $y == ($preselectedYear ?? date('Y')) ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group input-group-sm mr-2" style="width: 100px;">
                            <select id="monthFilter" class="form-control">
                                <option value="">Full Year</option>
                                @foreach (['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'] as $m => $label)
                                    <option value="{{ $m }}" {{ ($preselectedMonth ?? '') == $m ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="#" id="exportExcel" class="btn btn-sm btn-success mr-2" title="Export Yearly Summary">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </a>
                        <a href="#" id="exportExcelMonthly" class="btn btn-sm btn-outline-success mr-2" title="Export Monthly Breakdown">
                            <i class="fas fa-file-excel"></i> Export Monthly
                        </a>
                        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Index
                        </a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="viewTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="yearly-tab" data-toggle="tab" href="#yearly-pane" role="tab">Yearly Summary</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="monthly-tab" data-toggle="tab" href="#monthly-pane" role="tab">Monthly Breakdown</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="viewTabContent">
                        <div class="tab-pane fade show active" id="yearly-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table id="equipments" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Project</th>
                                    <th width="12%">Unit No</th>
                                    <th width="12%">Fuel</th>
                                    <th width="12%">Service</th>
                                    <th width="12%">Other</th>
                                    <th width="12%">Tax</th>
                                    <th width="12%">Total</th>
                                    {{-- FCPKM, Est. FCPL, Last KM - commented for later use
                                    <th width="15%">FCPKM</th>
                                    <th width="10%">Est. FCPL</th>
                                    <th width="8%">Last KM</th>
                                    --}}
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                        </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="monthly-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table id="equipmentsMonthly" class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th width="4%">#</th>
                                            <th width="8%">Project</th>
                                            <th width="8%">Unit No</th>
                                            <th width="6%">Jan</th>
                                            <th width="6%">Feb</th>
                                            <th width="6%">Mar</th>
                                            <th width="6%">Apr</th>
                                            <th width="6%">May</th>
                                            <th width="6%">Jun</th>
                                            <th width="6%">Jul</th>
                                            <th width="6%">Aug</th>
                                            <th width="6%">Sep</th>
                                            <th width="6%">Oct</th>
                                            <th width="6%">Nov</th>
                                            <th width="6%">Dec</th>
                                            <th width="8%">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
@endsection

@section('styles')
    <!-- Optimized CSS loading with media="all" for better performance -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}"
        media="all">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}"
        media="all">
    <style>
        /* Inline critical CSS for faster rendering */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .text-right {
            text-align: right !important;
        }

        #equipments_processing,
        #equipmentsMonthly_processing {
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -20px;
            margin-left: -80px;
            z-index: 1;
        }

        .select2-container--bootstrap4 .select2-selection {
            height: calc(1.8125rem + 2px) !important;
        }

        tr.dtrg-group td {
            font-weight: 600;
            background-color: #f4f6f9;
            border-top: 2px solid #dee2e6;
        }
    </style>
@endsection

@section('scripts')
    <!-- Defer non-critical scripts for faster page load -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-rowgroup/js/dataTables.rowGroup.min.js') }}" defer></script>

    <script>
        // Initialize table after all resources are loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for deferred scripts to load
            function checkScriptsLoaded() {
                if (typeof $.fn.DataTable === 'function') {
                    initializeTable();
                } else {
                    setTimeout(checkScriptsLoaded, 100);
                }
            }

            checkScriptsLoaded();
        });

        function initializeTable() {
            function debounce(func, wait) {
                let timeout;
                return function() {
                    const context = this;
                    const args = arguments;
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        func.apply(context, args);
                    }, wait);
                };
            }

            var table = $("#equipments").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                stateSave: true,
                retrieve: true,
                searching: true,
                order: [[1, 'asc'], [2, 'asc']],
                orderCellsTop: true,
                autoWidth: false,
                rowGroup: { dataSrc: 1, emptyDataGroup: '-' },
                ajax: {
                    url: '{{ route('reports.equipment.data') }}',
                    type: 'GET',
                    data: function(d) {
                        d.year = $('#yearFilter').val();
                        const month = $('#monthFilter').val();
                        if (month) d.month = month;
                    },
                    cache: true,
                    timeout: 15000,
                    error: function(xhr, error, thrown) {
                        console.log('DataTables error: ' + error + ' - ' + thrown);
                        alert('An error occurred while loading data. Please try refreshing the page.');
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    { data: 'project' },
                    { data: 'unit_no' },
                    { data: 'fuel_amount', className: 'text-right' },
                    { data: 'service_amount', className: 'text-right' },
                    { data: 'other_amount', className: 'text-right' },
                    { data: 'tax_amount', className: 'text-right' },
                    { data: 'total_amount', className: 'text-right' }
                    // FCPKM, Est. FCPL, Last KM - commented for later use
                    // , { data: 'fuel_cost_per_km' },
                    // { data: 'estimated_fcpl', className: 'text-right' },
                    // { data: 'last_km', className: 'text-right' }
                ],
                fixedHeader: false, // Disable for better performance
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function(row) {
                                return 'Equipment Details';
                            }
                        }),
                        renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                    }
                },
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>', // Optimized DOM structure
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                columnDefs: [{
                    "targets": [3, 4, 5, 6, 7],
                    "className": "text-right"
                }],
                createdRow: function(row, data, dataIndex) {
                    // Add data attributes for better performance with event delegation
                    $(row).attr('data-unit-no', data.unit_no);
                },
                drawCallback: function(settings) {
                    // Lazy load images
                    lazyLoadImages();

                    // Batch DOM operations for better performance
                    window.requestAnimationFrame(function() {
                        const rows = document.querySelectorAll('#equipments tbody tr');
                        for (let i = 0; i < rows.length; i++) {
                            rows[i].classList.add('loaded');
                        }
                    });
                },
                initComplete: function() {
                    // Use more efficient event delegation
                    const searchInput = document.querySelector('.dataTables_filter input');
                    if (searchInput) {
                        searchInput.removeEventListener('input', null);
                        searchInput.addEventListener('input', debounce(function(e) {
                            table.search(this.value).draw();
                        }, 500));
                    }

                    // Add performance hint to browser
                    if ('prerender' in document) {
                        document.prerender = true;
                    }
                }
            });

            var tableMonthly = $("#equipmentsMonthly").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                stateSave: false,
                searching: true,
                order: [[1, 'asc'], [2, 'asc']],
                autoWidth: false,
                rowGroup: { dataSrc: 1, emptyDataGroup: '-' },
                ajax: {
                    url: '{{ route('reports.equipment.data-monthly') }}',
                    type: 'GET',
                    data: function(d) {
                        d.year = $('#yearFilter').val();
                    },
                    cache: true,
                    timeout: 15000,
                    error: function(xhr, error, thrown) {
                        console.log('DataTables error: ' + error + ' - ' + thrown);
                        alert('An error occurred while loading data. Please try refreshing the page.');
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'project' },
                    { data: 'unit_no' },
                    { data: 'jan', className: 'text-right' },
                    { data: 'feb', className: 'text-right' },
                    { data: 'mar', className: 'text-right' },
                    { data: 'apr', className: 'text-right' },
                    { data: 'may', className: 'text-right' },
                    { data: 'jun', className: 'text-right' },
                    { data: 'jul', className: 'text-right' },
                    { data: 'aug', className: 'text-right' },
                    { data: 'sep', className: 'text-right' },
                    { data: 'oct', className: 'text-right' },
                    { data: 'nov', className: 'text-right' },
                    { data: 'dec', className: 'text-right' },
                    { data: 'total_amount', className: 'text-right' }
                ],
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                columnDefs: [{ targets: [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], className: 'text-right' }],
                createdRow: function(row, data) {
                    $(row).attr('data-unit-no', data.unit_no);
                }
            });

            $('#yearFilter, #monthFilter').on('change', function() {
                table.ajax.reload();
            });

            $('#yearFilter').on('change', function() {
                if ($('#monthly-tab').hasClass('active')) {
                    tableMonthly.ajax.reload();
                }
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                if (e.target.getAttribute('href') === '#monthly-pane') {
                    $('#monthFilter').closest('.input-group').hide();
                    tableMonthly.ajax.reload();
                    tableMonthly.columns.adjust();
                } else {
                    $('#monthFilter').closest('.input-group').show();
                }
            });

            $('#exportExcel').on('click', function(e) {
                e.preventDefault();
                const year = $('#yearFilter').val();
                let url = '{{ route('reports.equipment.export') }}?year=' + year;
                const month = $('#monthFilter').val();
                if (month) url += '&month=' + month;
                window.location.href = url;
            });

            $('#exportExcelMonthly').on('click', function(e) {
                e.preventDefault();
                const year = $('#yearFilter').val();
                window.location.href = '{{ route('reports.equipment.export-monthly') }}?year=' + year;
            });

            document.addEventListener('click', function(e) {
                const target = e.target.closest('a[href*="equipment.detail"]');
                if (target) {
                    const row = target.closest('tr');
                    const unitNo = row ? row.getAttribute('data-unit-no') : null;
                    if (unitNo) {
                        const year = $('#yearFilter').val();
                        let href = '{{ route('reports.equipment.detail') }}?unit_no=' + unitNo + '&year=' + year;
                        if ($('#yearly-tab').hasClass('active')) {
                            const month = $('#monthFilter').val();
                            if (month) href += '&month=' + month;
                        }
                        const prefetchLink = document.createElement('link');
                        prefetchLink.rel = 'prefetch';
                        prefetchLink.href = href;
                        document.head.appendChild(prefetchLink);
                    }
                }
            });

            // Optimize lazy loading with Intersection Observer API
            function lazyLoadImages() {
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver(function(entries, observer) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                imageObserver.unobserve(img);
                            }
                        });
                    });

                    document.querySelectorAll('img[data-src]').forEach(function(img) {
                        imageObserver.observe(img);
                    });
                } else {
                    // Fallback for browsers without Intersection Observer
                    document.querySelectorAll('img[data-src]').forEach(function(img) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    });
                }
            }

            window.addEventListener('beforeunload', function() {
                if (table && typeof table.destroy === 'function') table.destroy();
                if (tableMonthly && typeof tableMonthly.destroy === 'function') tableMonthly.destroy();
            });
        }
    </script>
@endsection
