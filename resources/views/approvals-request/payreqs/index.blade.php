@extends('templates.main')

@section('title_page')
    Approvals Request
@endsection

@section('breadcrumb_title')
    approvals
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <div class="h3 card-title">
                        <b>Payment Request</b>
                        @if ($document_count['payreq'] > 0)
                            <span class="badge badge-danger payreq-badge">{{ $document_count['payreq'] }}</span>
                        @endif |
                        <a href="{{ route('approvals.request.realizations.index') }}">Realization @if ($document_count['realization'] > 0)
                                <span
                                    class="badge badge-danger realization-badge">{{ $document_count['realization'] }}</span>
                            @endif
                        </a>
                        |
                        <a href="{{ route('approvals.request.anggarans.index') }}">RABs @if ($document_count['rab'] > 0)
                                <span class="badge badge-danger rab-badge">{{ $document_count['rab'] }}</span>
                            @endif
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <button id="bulk-approve-btn" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-check"></i> Approve Selected
                        </button>
                        <button id="select-all-btn" class="btn btn-primary btn-sm ml-2">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button id="deselect-all-btn" class="btn btn-secondary btn-sm ml-2" disabled>
                            <i class="fas fa-square"></i> Deselect All
                        </button>
                    </div>
                    <table id="mypayreqs" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select-all-checkbox">
                                </th>
                                <th>#</th>
                                <th>Payreq No</th>
                                <th>Requestor</th>
                                <th>Submit at</th>
                                <th>Type</th>
                                <th>IDR</th>
                                <th>Days</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- /.card-body -->
            </div>
            <!-- /.card -->


        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->

    <!-- Bulk Approval Modal -->
    <div class="modal fade" id="bulk-approval-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Bulk Approval</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve the selected payment requests?</p>
                    <div class="form-group">
                        <label for="bulk-remarks">Remarks (optional)</label>
                        <textarea id="bulk-remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" id="confirm-bulk-approve" class="btn btn-success">Approve</button>
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
            // Initialize DataTable
            var table = $("#mypayreqs").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('approvals.request.payreqs.data') }}',
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return '<input type="checkbox" class="row-checkbox" data-id="' + row
                                .id + '">';
                        }
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
                        data: 'requestor'
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'days'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [6, 7],
                    "className": "text-right"
                }, ]
            });

            // Handle select all checkbox
            $('#select-all-checkbox').on('change', function() {
                var isChecked = this.checked;
                $('.row-checkbox').prop('checked', isChecked);
                updateBulkActionButtons();
            });

            // Handle select all button
            $('#select-all-btn').on('click', function() {
                $('.row-checkbox').prop('checked', true);
                $('#select-all-checkbox').prop('checked', true);
                updateBulkActionButtons();
            });

            // Handle deselect all button
            $('#deselect-all-btn').on('click', function() {
                $('.row-checkbox').prop('checked', false);
                $('#select-all-checkbox').prop('checked', false);
                updateBulkActionButtons();
            });

            // Handle individual checkbox changes
            $(document).on('change', '.row-checkbox', function() {
                updateBulkActionButtons();

                // Update select all checkbox
                var allChecked = $('.row-checkbox:checked').length === $('.row-checkbox').length;
                $('#select-all-checkbox').prop('checked', allChecked);
            });

            // Update bulk action buttons state
            function updateBulkActionButtons() {
                var checkedCount = $('.row-checkbox:checked').length;
                $('#bulk-approve-btn').prop('disabled', checkedCount === 0);
                $('#deselect-all-btn').prop('disabled', checkedCount === 0);
            }

            // Handle bulk approve button click
            $('#bulk-approve-btn').on('click', function() {
                $('#bulk-approval-modal').modal('show');
            });

            // Handle confirm bulk approve
            $('#confirm-bulk-approve').on('click', function() {
                var selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).data('id'));
                });

                var remarks = $('#bulk-remarks').val();

                $.ajax({
                    url: '{{ route('approvals.plan.bulk-approve') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selectedIds,
                        remarks: remarks,
                        document_type: 'payreq'
                    },
                    success: function(response) {
                        // Close the modal
                        $('#bulk-approval-modal').modal('hide');

                        // Show success message
                        toastr.success(response.message);

                        // Refresh the table
                        table.ajax.reload();

                        // Reset checkboxes
                        $('#select-all-checkbox').prop('checked', false);
                        updateBulkActionButtons();

                        // Update document count badges
                        updateDocumentCountBadges();
                    },
                    error: function(xhr) {
                        // Show error message
                        var errorMessage = xhr.responseJSON ? xhr.responseJSON.message :
                            'An error occurred';
                        toastr.error(errorMessage);
                    }
                });
            });

            // Handle AJAX form submission for approval forms
            $(document).on('submit', '.approval-form', function(e) {
                e.preventDefault();

                var form = $(this);
                var url = form.attr('action');
                var modal = form.closest('.modal');

                $.ajax({
                    type: "POST",
                    url: url,
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        // Close the modal
                        modal.modal('hide');

                        // Show success message with Toastr
                        toastr.success(response.message);

                        // Refresh the DataTable
                        table.ajax.reload();

                        // Update document count badges
                        updateDocumentCountBadges();
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        var errorMessage = xhr.responseJSON ? xhr.responseJSON.message :
                            'An error occurred';
                        toastr.error(errorMessage);
                    }
                });
            });

            // Function to update document count badges
            function updateDocumentCountBadges() {
                $.ajax({
                    url: '{{ route('approvals.request.document-count') }}',
                    type: 'GET',
                    success: function(response) {
                        // Update payreq badge
                        if (response.payreq > 0) {
                            $('.payreq-badge').text(response.payreq).show();
                        } else {
                            $('.payreq-badge').hide();
                        }

                        // Update realization badge
                        if (response.realization > 0) {
                            $('.realization-badge').text(response.realization).show();
                        } else {
                            $('.realization-badge').hide();
                        }

                        // Update rab badge
                        if (response.rab > 0) {
                            $('.rab-badge').text(response.rab).show();
                        } else {
                            $('.rab-badge').hide();
                        }
                    }
                });
            }
        });
    </script>
    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
@endsection
