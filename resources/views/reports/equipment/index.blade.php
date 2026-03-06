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
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="#" id="exportExcel" class="btn btn-sm btn-success mr-2">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </a>
                        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Index
                        </a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="equipments" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
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

        #equipments_processing {
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
    </style>
@endsection

@section('scripts')
    <!-- Defer non-critical scripts for faster page load -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}" defer></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}" defer></script>

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
            // Debounce function with better performance
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

            // Improved performance DataTable initialization
            var table = $("#equipments").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                stateSave: true,
                retrieve: true,
                searching: true,
                order: [[1, 'asc']],
                orderCellsTop: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('reports.equipment.data') }}',
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
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
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
                    "targets": [2, 3, 4, 5, 6],
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

            $('#yearFilter').on('change', function() {
                table.ajax.reload();
            });

            $('#exportExcel').on('click', function(e) {
                e.preventDefault();
                const year = $('#yearFilter').val();
                window.location.href = '{{ route('reports.equipment.export') }}?year=' + year;
            });

            document.addEventListener('click', function(e) {
                const target = e.target.closest('a[href*="equipment.detail"]');
                if (target) {
                    const unitNo = target.closest('tr').getAttribute('data-unit-no');
                    if (unitNo) {
                        const year = $('#yearFilter').val();
                        const prefetchLink = document.createElement('link');
                        prefetchLink.rel = 'prefetch';
                        prefetchLink.href = '{{ route('reports.equipment.detail') }}?unit_no=' + unitNo + '&year=' + year;
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

            // Memory management
            window.addEventListener('beforeunload', function() {
                if (table && typeof table.destroy === 'function') {
                    table.destroy();
                }
            });
        }
    </script>
@endsection
