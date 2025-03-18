@extends('templates.main')

@section('title_page')
    Document Overdue
@endsection

@section('breadcrumb_title')
    payreqs / overdue
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <a href="#" class="text-bold" style="color: black;">PAYMENT REQUEST</a> |
                    <a href="{{ route('document-overdue.realization.index') }}">Realizations</a>
                </div>

                <div class="card-body">
                    <form id="bulk-update-form" method="POST" action="{{ route('document-overdue.payreq.bulk-extend') }}">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <button id="select-all" class="btn btn-sm btn-outline-primary mr-2">Select All</button>
                                <button id="deselect-all" class="btn btn-sm btn-outline-secondary">Deselect All</button>
                            </div>
                            <div id="bulk-actions" class="col-md-10" style="display: none;">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">New Due Date</span>
                                            </div>
                                            <input type="date" name="new_due_date" id="new_due_date" class="form-control"
                                                required>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary">Update Selected
                                                    Records</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="payreq-overdue" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="check-all"></th>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Project</th>
                                    <th>Payreq No</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>IDR</th>
                                    <th>DFP</th>
                                    <th>DFD</th>
                                    <th></th>
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
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            var table = $("#payreq-overdue").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('document-overdue.payreq.data') }}',
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
                        data: 'employee'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'nomor'
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
                        data: 'dfp'
                    },
                    {
                        data: 'dfd'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                        "targets": [3, 4],
                        "className": "text-center"
                    },
                    {
                        "targets": [7, 8, 9],
                        "className": "text-right"
                    },
                ]
            });

            // Toggle checkbox behavior
            $('#check-all').on('click', function() {
                $('.payreq-checkbox').prop('checked', $(this).prop('checked'));
                toggleBulkActions();
            });

            // Select all button
            $('#select-all').on('click', function(e) {
                e.preventDefault();
                $('.payreq-checkbox').prop('checked', true);
                $('#check-all').prop('checked', true);
                toggleBulkActions();
            });

            // Deselect all button
            $('#deselect-all').on('click', function(e) {
                e.preventDefault();
                $('.payreq-checkbox').prop('checked', false);
                $('#check-all').prop('checked', false);
                toggleBulkActions();
            });

            // Handle individual checkbox changes
            $(document).on('change', '.payreq-checkbox', function() {
                toggleBulkActions();
            });

            // Toggle bulk actions visibility based on selections
            function toggleBulkActions() {
                if ($('.payreq-checkbox:checked').length > 0) {
                    $('#bulk-actions').show();
                } else {
                    $('#bulk-actions').hide();
                }
            }
        });
    </script>
@endsection
