@extends('templates.main')

@section('title_page')
    Anggaran
@endsection

@section('breadcrumb_title')
    reports / anggaran
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <b>ACTIVE</b> | <a href="{{ route('reports.anggaran.index', ['status' => 'inactive']) }}">In-active</a>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back to Index</a>
                    @can('recalculate_release')
                        <a href="{{ route('reports.anggaran.recalculate') }}" class="btn btn-xs btn-warning float-right mx-2"
                            onclick="return confirm('Are you sure you want to recalculate anggaran release?')">Recalc
                            Release</a>
                        <button id="inactivate-many" class="btn btn-warning btn-xs float-right">Inactivate Many</button>
                    @endcan
                </div>
                <div class="card-body">
                    <form id="form-inactivate-many" action="{{ route('reports.anggaran.update_many') }}" method="POST">
                        @csrf
                        <table id="anggarans" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>No</th>
                                    <th>Nomor</th>
                                    <th>Creator</th>
                                    <th>RAB Project</th>
                                    <th>Description</th>
                                    <th>Periode</th>
                                    <th>Budget</th>
                                    <th>Progres</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>

    <script>
        $(function() {
            // Debounce function to limit how often a function can be called
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

            // Initialize DataTable with optimized settings
            var table = $("#anggarans").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                stateSave: true, // Save user's state (pagination, filtering, etc.)
                ajax: {
                    url: '{{ route('reports.anggaran.data', ['status' => 'active']) }}',
                    type: 'GET',
                    cache: true,
                    error: function(xhr, error, thrown) {
                        console.log('DataTables error: ' + error + ' - ' + thrown);
                        console.log(xhr.responseText);
                        alert('An error occurred while loading data. Please try refreshing the page.');
                    }
                },
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'creator'
                    },
                    {
                        data: 'rab_project'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'periode'
                    },
                    {
                        data: 'budget'
                    },
                    {
                        data: 'progres'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [1, 'asc']
                ],
                responsive: true,
                language: {
                    processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><span class="sr-only">Loading...</span>'
                },
                drawCallback: function() {
                    // Lazy load images when table is drawn
                    lazyLoadImages();
                },
                initComplete: function() {
                    // Add debounced search for better performance
                    var searchInput = $('.dataTables_filter input');
                    searchInput.unbind();
                    searchInput.bind('input', debounce(function(e) {
                        table.search(this.value).draw();
                    }, 500));
                }
            });

            // Lazy load images function
            function lazyLoadImages() {
                $('img[data-src]').each(function() {
                    var img = $(this);
                    img.attr('src', img.data('src'));
                    img.removeAttr('data-src');
                });
            }

            // Handle click on "Select all" control with optimized event delegation
            $('#select-all').on('click', function() {
                $('input[type="checkbox"]', table.rows().nodes()).prop('checked', this.checked);
            });

            // Handle click on "Inactivate Many" button with confirmation
            $('#inactivate-many').on('click', function(e) {
                e.preventDefault();
                if (!confirm('Apakah yakin akan merubah status anggaran terpilih?')) {
                    return false;
                }
                $('#form-inactivate-many').submit();
            });
        });
    </script>
@endsection
