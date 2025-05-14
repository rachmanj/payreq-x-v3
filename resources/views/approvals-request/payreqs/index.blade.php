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

    <!-- LOT Detail Modal -->
    <div class="modal fade" id="lotDetailModal" tabindex="-1" role="dialog" aria-labelledby="lotDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary">
                    <h5 class="modal-title" id="lotDetailModalLabel">
                        <i class="fas fa-plane-departure mr-2"></i>LOT Detail
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <!-- Header Info -->
                    <div class="bg-light p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1" id="modal_travel_number"></h5>
                                <p class="text-muted mb-0 small" id="modal_travel_date"></p>
                            </div>
                            <div>
                                <span class="badge badge-lg px-3 py-2" id="modal_status_badge"></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="row">
                            <!-- Travel Info -->
                            <div class="col-md-6">
                                <div class="card card-outline card-primary mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-info-circle mr-1"></i>Travel Information
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Purpose</small>
                                                <span id="modal_purpose" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Destination</small>
                                                <span id="modal_destination" class="d-block mb-2"></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Duration</small>
                                                <span id="modal_duration" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Departure From</small>
                                                <span id="modal_departure_from" class="d-block mb-2"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Traveler Info -->
                            <div class="col-md-6">
                                <div class="card card-outline card-info mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-user mr-1"></i>Traveler Information
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Name</small>
                                                <span id="modal_traveler_name" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Department</small>
                                                <span id="modal_traveler_department" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Position</small>
                                                <span id="modal_traveler_position" class="d-block mb-2"></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Project</small>
                                                <span id="modal_traveler_project" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">NIK</small>
                                                <span id="modal_traveler_nik" class="d-block mb-2"></span>

                                                <small class="text-muted d-block">Class</small>
                                                <span id="modal_traveler_class" class="d-block mb-2"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approval & Transport -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-success mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-check-circle mr-1"></i>Approval Status
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Recommendation</small>
                                                <span id="modal_recommender_name" class="d-block"></span>
                                                <small class="text-muted d-block"
                                                    id="modal_recommendation_remark"></small>
                                                <small class="text-muted d-block mb-2"
                                                    id="modal_recommendation_date"></small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Approval</small>
                                                <span id="modal_approver_name" class="d-block"></span>
                                                <small class="text-muted d-block" id="modal_approval_remark"></small>
                                                <small class="text-muted d-block mb-2" id="modal_approval_date"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-outline card-warning mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-car mr-1"></i>Transportation & Accommodation
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Transportation</small>
                                                <span id="modal_transportation" class="d-block mb-2"></span>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Accommodation</small>
                                                <span id="modal_accommodation" class="d-block mb-2"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Followers -->
                        <div class="card card-outline card-secondary mb-0">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-users mr-1"></i>Travel Followers
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Position</th>
                                                <th>Project</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal_followers">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        onclick="backToApprovalsModal()">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Approvals
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Close
                    </button>
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
        /* Base modal styles */
        .modal-open {
            overflow: hidden;
            padding-right: 0 !important;
        }

        /* Specific styles for both modals */
        #lotDetailModal,
        [id^="approvals-update-"] {
            overflow-x: hidden;
            overflow-y: auto;
            padding-right: 0 !important;
        }

        #lotDetailModal .modal-dialog,
        [id^="approvals-update-"] .modal-dialog {
            margin: 1.75rem auto;
            max-height: calc(100vh - 3.5rem);
            display: flex;
            align-items: center;
        }

        #lotDetailModal .modal-content,
        [id^="approvals-update-"] .modal-content {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        #lotDetailModal .modal-header,
        [id^="approvals-update-"] .modal-header {
            flex-shrink: 0;
        }

        #lotDetailModal .modal-body,
        [id^="approvals-update-"] .modal-body {
            overflow-y: auto;
            max-height: calc(100vh - 200px);
            padding: 1rem;
        }

        /* Backdrop styles */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-backdrop.show {
            opacity: 0.5;
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
    <!-- daterangepicker -->
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>

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

            // Handle LOT detail view
            $(document).on('click', '#view_lot_detail', function() {
                const lotNo = $(this).closest('.input-group').find('input').val();
                const $button = $(this);
                const $modal = $(this).closest('.modal');
                const approvalId = $modal.attr('id').split('-').pop(); // Get approval ID from modal ID

                // Disable button and add spinner
                $button.prop('disabled', true);
                $button.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
                );

                // Fetch LOT data
                $.ajax({
                    url: '{{ route('user-payreqs.advance.search-lot') }}',
                    method: 'POST',
                    data: {
                        travel_number: lotNo
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Reset button state
                        $button.prop('disabled', false);
                        $button.html('<strong>LOT Detail</strong>');

                        if (response.success && response.data && response.data.length > 0) {
                            const lot = response.data[0];

                            // Set status badge
                            const status = lot.official_travel_status || 'N/A';
                            let badgeClass = 'badge-secondary';
                            if (status === 'open') badgeClass = 'badge-success';
                            if (status === 'closed') badgeClass = 'badge-danger';
                            if (status === 'pending') badgeClass = 'badge-warning';

                            $('#modal_status_badge')
                                .removeClass(
                                    'badge-secondary badge-success badge-danger badge-warning')
                                .addClass(badgeClass)
                                .text(status.toUpperCase());

                            // Travel Information
                            $('#modal_travel_number').text(lot.official_travel_number || 'N/A');
                            $('#modal_travel_date').text(lot.official_travel_date ? moment(lot
                                .official_travel_date).format('DD MMMM YYYY') : 'N/A');
                            $('#modal_purpose').text(lot.purpose || 'N/A');
                            $('#modal_destination').text(lot.destination || 'N/A');
                            $('#modal_duration').text(lot.duration || 'N/A');
                            $('#modal_departure_from').text(lot.departure_from ? moment(lot
                                .departure_from).format('DD MMMM YYYY') : 'N/A');

                            // Traveler Information
                            $('#modal_traveler_name').text(lot.traveler?.employee?.fullname ||
                                'N/A');
                            $('#modal_traveler_department').text(lot.traveler?.position
                                ?.department?.department_name || 'N/A');
                            $('#modal_traveler_position').text(lot.traveler?.position
                                ?.position_name || 'N/A');
                            $('#modal_traveler_project').text(lot.traveler?.project
                                ?.project_name || 'N/A');
                            $('#modal_traveler_nik').text(lot.traveler?.nik || 'N/A');
                            $('#modal_traveler_class').text(lot.traveler?.class || 'N/A');

                            // Approval Information
                            $('#modal_recommender_name').text(lot.recommender?.name || 'N/A');
                            $('#modal_recommendation_remark').text(lot.recommendation_remark ||
                                '');
                            $('#modal_recommendation_date').text(lot.recommendation_date || '');
                            $('#modal_approver_name').text(lot.approver?.name || 'N/A');
                            $('#modal_approval_remark').text(lot.approval_remark || '');
                            $('#modal_approval_date').text(lot.approval_date || '');

                            // Transportation & Accommodation
                            $('#modal_transportation').text(lot.transportation
                                ?.transportation_name || 'N/A');
                            $('#modal_accommodation').text(lot.accommodation
                                ?.accommodation_name || 'N/A');

                            // Travel Followers
                            const followersHtml = lot.details?.map(detail => `
                                <tr>
                                    <td>${detail.follower?.employee?.fullname || 'N/A'}</td>
                                    <td>${detail.follower?.position?.department?.department_name || 'N/A'}</td>
                                    <td>${detail.follower?.position?.position_name || 'N/A'}</td>
                                    <td>${detail.follower?.project?.project_name || 'N/A'}</td>
                                </tr>
                            `).join('') || '<tr><td colspan="4" class="text-center">No followers</td></tr>';

                            $('#modal_followers').html(followersHtml);

                            // Hide approvals modal and show LOT detail modal
                            $modal.modal('hide');

                            // Configure and show LOT detail modal
                            $('#lotDetailModal').modal({
                                backdrop: 'static',
                                keyboard: false,
                                focus: true
                            }).modal('show');

                            // Store approval ID in data attribute
                            $('#lotDetailModal').data('approval-id', approvalId);

                            // Ensure modal is focused and scrollable
                            $('#lotDetailModal').on('shown.bs.modal', function() {
                                $(this).find('.modal-body').scrollTop(0);
                            });
                        } else {
                            alert('Failed to fetch LOT data');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Reset button state
                        $button.prop('disabled', false);
                        $button.html('<strong>LOT Detail</strong>');
                        alert('Error fetching LOT data');
                    }
                });
            });

            // Function to go back to approvals modal
            window.backToApprovalsModal = function() {
                const approvalId = $('#lotDetailModal').data('approval-id');
                $('#lotDetailModal').modal('hide');

                // Show approvals modal with proper configuration
                $(`#approvals-update-${approvalId}`).modal('show');

                // Ensure modal is focused and scrollable
                $(`#approvals-update-${approvalId}`).on('shown.bs.modal', function() {
                    $(this).find('.modal-body').scrollTop(0);
                });
            }
        });
    </script>
    {{-- <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script> --}}
@endsection
