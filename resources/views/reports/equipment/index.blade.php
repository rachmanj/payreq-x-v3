@extends('templates.main')

@section('title_page')
    Reports
@endsection

@section('breadcrumb_title')
    reports / equipements
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Expense by Equipment</h3>
                    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back to Index</a>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="equipments" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Unit No</th>
                                    <th width="30%">FCPKM</th>
                                    <th width="15%">Project</th>
                                    <th width="15%">Last KM</th>
                                    <th width="20%">Expense</th>
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
                orderCellsTop: true,
                autoWidth: false, // Better performance by disabling auto-width
                scroller: true, // Enable virtual scrolling for better performance
                scrollY: '65vh', // Virtual scroll height
                scrollCollapse: true,
                ajax: {
                    url: '{{ route('reports.equipment.data') }}',
                    type: 'GET',
                    data: function(d) {
                        d.cache_key = '{{ auth()->user()->project }}_equipment_{{ auth()->user()->id }}';
                    },
                    cache: true,
                    timeout: 15000, // 15 second timeout
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
                    {
                        data: 'unit_no'
                    },
                    {
                        data: 'fuel_cost_per_km'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'last_km'
                    },
                    {
                        data: 'total_amount'
                    },
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
                    "targets": [2, 4, 5],
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

            // Optimize event listeners with event delegation
            document.addEventListener('click', function(e) {
                const target = e.target.closest('a[href*="equipment.detail"]');
                if (target) {
                    // Prefetch detail page data on hover
                    const unitNo = target.closest('tr').getAttribute('data-unit-no');
                    if (unitNo) {
                        const prefetchLink = document.createElement('link');
                        prefetchLink.rel = 'prefetch';
                        prefetchLink.href = '{{ route('reports.equipment.detail') }}?unit_no=' + unitNo;
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
