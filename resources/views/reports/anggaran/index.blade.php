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
                    <!-- Custom Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Search</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form id="custom-search-form">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="search-nomor">Nomor/RAB No</label>
                                                    <input type="text" class="form-control" id="search-nomor"
                                                        placeholder="Search by nomor">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="search-creator">Creator</label>
                                                    <input type="text" class="form-control" id="search-creator"
                                                        placeholder="Search by creator">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="search-project">RAB Project</label>
                                                    <input type="text" class="form-control" id="search-project"
                                                        placeholder="Search by project">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="search-description">Description</label>
                                                    <input type="text" class="form-control" id="search-description"
                                                        placeholder="Search by description">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 text-right">
                                                <button type="button" id="btn-search" class="btn btn-primary">
                                                    <i class="fas fa-search"></i> Search
                                                </button>
                                                <button type="button" id="btn-reset" class="btn btn-default">
                                                    <i class="fas fa-times"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

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
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
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
            // Initialize DataTable with optimized settings
            var table = $("#anggarans").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                ajax: {
                    url: '{{ route('reports.anggaran.data', ['status' => 'active']) }}',
                    type: 'GET',
                    data: function(d) {
                        // Add custom search parameters
                        d.custom_search = true;
                        d.search_nomor = $('#search-nomor').val();
                        d.search_creator = $('#search-creator').val();
                        d.search_project = $('#search-project').val();
                        d.search_description = $('#search-description').val();

                        // Clear DataTables search
                        d.search = {
                            value: "",
                            regex: false
                        };
                        return d;
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor',
                        name: 'nomor'
                    },
                    {
                        data: 'creator',
                        name: 'creator',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'rab_project',
                        name: 'rab_project'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'periode',
                        name: 'periode',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'progres',
                        name: 'progres',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
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
                dom: '<"row"<"col-md-6"l><"col-md-6">>rtip', // Remove default search box
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ]
            });

            // Search button click handler
            $('#btn-search').on('click', function() {
                table.ajax.reload();
            });

            // Reset button click handler
            $('#btn-reset').on('click', function() {
                $('#search-nomor').val('');
                $('#search-creator').val('');
                $('#search-project').val('');
                $('#search-description').val('');
                table.ajax.reload();
            });

            // Allow pressing Enter to search
            $('#custom-search-form input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#btn-search').click();
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

            // Use event delegation for better performance
            $(document).on('click', '#select-all', function() {
                $('input[type="checkbox"]', table.rows().nodes()).prop('checked', this.checked);
            });

            // Handle click on "Inactivate Many" button with confirmation
            $(document).on('click', '#inactivate-many', function(e) {
                e.preventDefault();
                if (!confirm('Apakah yakin akan merubah status anggaran terpilih?')) {
                    return false;
                }
                $('#form-inactivate-many').submit();
            });
        });
    </script>
@endsection
