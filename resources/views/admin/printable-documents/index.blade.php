@extends('templates.main')

@section('title_page')
    Admin - Printable Documents
@endsection

@section('breadcrumb_title')
    admin / printable documents
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Manage Printable Documents Status</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="printable-documents" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Payreq</th>
                                    <th>Type</th>
                                    <th>Realization</th>
                                    <th>Requestor</th>
                                    <th>Status</th>
                                    <th>Days</th>
                                    <th>IDR</th>
                                    <th>Printable?</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
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
    <style>
        .dataTables_filter input {
            width: 300px !important;
        }

        .dataTables_info {
            color: #6c757d;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            // Initialize DataTable with enhanced search functionality
            var table = $("#printable-documents").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.printable-documents.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor',
                        name: 'nomor',
                        searchable: true
                    },
                    {
                        data: 'type',
                        name: 'type',
                        searchable: true
                    },
                    {
                        data: 'realization_no',
                        name: 'realization_no',
                        searchable: true
                    },
                    {
                        data: 'requestor_name',
                        name: 'requestor_name',
                        searchable: true
                    },
                    {
                        data: 'status',
                        name: 'status',
                        searchable: true
                    },
                    {
                        data: 'duration',
                        name: 'duration',
                        searchable: false,
                        orderable: false,
                        className: 'text-right'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        searchable: true,
                        className: 'text-right'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                ],
                fixedHeader: true,
                searching: true,
                search: {
                    caseInsensitive: true
                },
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                pageLength: 10,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                language: {
                    search: "Search in all data:",
                    searchPlaceholder: "Enter search term...",
                    processing: "Loading data...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No data available",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    zeroRecords: "No matching records found"
                }
            });

            // Add search event listener for debugging
            table.on('search.dt', function() {
                var searchValue = table.search();
                console.log('Search triggered:', {
                    searchValue: searchValue,
                    hasValue: searchValue.length > 0
                });
            });

            // Add draw event listener to see when data is reloaded
            table.on('draw.dt', function() {
                var info = table.page.info();
                console.log('Table drawn:', {
                    recordsTotal: info.recordsTotal,
                    recordsDisplay: info.recordsDisplay,
                    page: info.page + 1,
                    pages: info.pages
                });
            });

            // Handle toggle printable buttons
            $(document).on('click', '.toggle-printable', function() {
                var button = $(this);
                var id = button.data('id');
                var current = parseInt(button.data('current'));
                var printable = current === 1 ? 0 : 1; // Toggle the value (use 0/1 instead of boolean)

                // Debug log
                console.log('Toggle clicked:', {
                    id: id,
                    current: current,
                    printable: printable
                });

                $.ajax({
                    url: '{{ route('admin.printable-documents.update') }}',
                    method: 'POST',
                    data: {
                        id: id,
                        printable: printable,
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Reload table to reflect changes
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Failed to update printable status');
                    }
                });
            });


        });
    </script>
@endsection
