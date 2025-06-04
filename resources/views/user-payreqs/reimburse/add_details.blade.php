@extends('templates.main')

@section('title_page')
    My Payreqs
@endsection

@section('breadcrumb_title')
    payreqs
@endsection

@section('styles')
    <!-- jQuery -->
    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .overlay {
            position: relative;
        }

        .overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 10;
        }

        .overlay-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 11;
            font-size: 20px;
            color: #17a2b8;
        }
    </style>
@endsection

@section('content')
    <!-- Add CSRF meta tag for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="row">
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq No</h5>
                <span class="description-text">{{ $payreq->nomor }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq Type</h5>
                <span class="description-text">Reimbursement</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Project</h5>
                <span class="description-text">{{ auth()->user()->project }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Department</h5>
                <span class="description-text">{{ auth()->user()->department->department_name }}</span>
            </div>
        </div>
    </div>

    {{-- PAYREQ REMARKS SECTION --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h4 class="card-title">Payreq Remarks</h4>
                    <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-info float-right">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="remarks">Remarks</label>
                                <input type="text" name="remarks" id="remarks"
                                    value="{{ old('remarks', $payreq->remarks) }}" class="form-control">
                            </div>
                        </div>
                    </div>
                    @can('rab_select')
                        <div class="row">
                            <div class="col-12">
                                <div class="input-group input-group-xs">
                                    {{-- <label for="anggaran">RAB</label> --}}
                                    <select name="rab_id" id="rab_id" class="form-control select2bs4">
                                        <option value="">-- Select RAB --</option>
                                        @foreach ($rabs as $rab)
                                            <option value="{{ $rab->id }}"
                                                {{ $payreq->rab_id == $rab->id ? 'selected' : '' }}>
                                                {{ $rab->rab_no ? $rab->rab_no : $rab->nomor }} | {{ $rab->rab_project }} |
                                                {{ $rab->description }}</option>
                                        @endforeach
                                    </select>
                                    <span class="input-group-append">
                                        <button type="button" id="update_rab"
                                            class="btn btn-info btn-xs btn-flat">update</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- DETAILS SECTION --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Reimbursement Details</h3>
                    <form id="submit-payreq-form" action="{{ route('user-payreqs.reimburse.submit_payreq') }}"
                        method="POST" class="d-inline">
                        @csrf
                        @if ($realization->realizationDetails->count() > 0)
                            <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                            <button type="button" id="btn-submit-payreq" class="btn btn-sm btn-warning float-right mx-2">
                                <i class="fas fa-paper-plane"></i> <b>Submit Payreq</b>
                            </button>
                        @endif
                    </form>
                    <button type="button" class="btn btn-sm btn-success float-right mr-2" data-toggle="modal"
                        data-target="#add-detail-modal">
                        <i class="fas fa-plus"></i> Add Detail
                    </button>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped" id="details-table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th>Description</th>
                                <th class="text-right" width="20%">Amount (IDR)</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($realization->realizationDetails->count() > 0)
                                @foreach ($realization->realizationDetails as $index => $item)
                                    <tr id="detail-row-{{ $item->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->description }}
                                            @if ($item->nopol !== null || $item->unit_no !== null)
                                                <br />
                                                @if ($item->type === 'fuel')
                                                    <small>{{ $item->unit_no }}, {{ $item->nopol }}, {{ $item->type }}
                                                        {{ $item->qty }} {{ $item->uom }}. HM:
                                                        {{ $item->km_position }}</small>
                                                @else
                                                    <small>{{ $item->type }}, HM: {{ $item->km_position }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-info btn-edit"
                                                data-id="{{ $item->id }}">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="javascript:void(0)" onclick="confirmDelete({{ $item->id }})"
                                                class="btn btn-xs btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="no-data-row">
                                    <td colspan="4" class="text-center">No Data Found</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">Total:</th>
                                <th class="text-right" id="total-amount">
                                    {{ number_format($realization->realizationDetails->sum('amount'), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete form for non-AJAX submission -->
    <form id="delete-detail-form" action="{{ route('user-payreqs.reimburse.delete_detail') }}" method="POST"
        style="display: none;">
        @csrf
        <input type="hidden" name="realization_detail_id" id="delete_detail_id">
        <input type="hidden" name="realization_id" value="{{ $realization->id }}">
    </form>

    <!-- Add Detail Modal -->
    <div class="modal fade" id="add-detail-modal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="addModalLabel">Add Reimbursement Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="add-detail-form" method="POST"
                        action="{{ route('user-payreqs.reimburse.store_detail') }}">
                        @csrf
                        <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                        <div class="row">
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" name="description" value="{{ old('description') }}"
                                        id="description" class="form-control @error('description') is-invalid @enderror">
                                    <div class="invalid-feedback" id="description-error">
                                        @error('description')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="amount">Amount</label>
                                    <input type="text" name="amount" id="amount" class="form-control"
                                        value="{{ old('amount') }}" onkeyup="formatNumber(this)">
                                    <div class="text-danger" id="amount-error">
                                        @error('amount')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="unit_no">Unit No</label>
                                    <select id="unit_no" name="unit_no" class="form-control select2bs4">
                                        <option value="">-- select unit no --</option>
                                        @foreach ($equipments as $item)
                                            <option value="{{ $item->unit_code }}">{{ $item->unit_code }} -
                                                {{ $item->project }} - {{ $item->plant_group }} -
                                                {{ $item->nomor_polisi }}</option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger" id="unit_no-error"></div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="nopol">No Polisi <small>(optional)</small></label>
                                    <input type="text" name="nopol" value="{{ old('nopol') }}" id="nopol"
                                        class="form-control">
                                    <div class="text-danger" id="nopol-error"></div>
                                </div>
                            </div>
                            <div class="col-1">
                                <div class="form-group">
                                    <label for="qty">Qty</label>
                                    <input id="qty" name="qty" class="form-control">
                                    <div class="text-danger" id="qty-error"></div>
                                </div>
                            </div>
                            <div class="col-1">
                                <div class="form-group">
                                    <label for="km_position">HM</label>
                                    <input id="km_position" name="km_position" class="form-control">
                                    <div class="text-danger" id="km_position-error"></div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="type">Type</label>
                                    <select id="type" name="type" class="form-control select2bs4">
                                        <option value="">-- type --</option>
                                        <option value="fuel">Fuel</option>
                                        <option value="service">Service</option>
                                        <option value="tax">STNK / Tax</option>
                                        <option value="other">Others</option>
                                    </select>
                                    <div class="text-danger" id="type-error"></div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="uom">UOM</label>
                                    <select id="uom" name="uom" class="form-control select2bs4">
                                        <option value="">-- uom --</option>
                                        <option value="liter">liter</option>
                                        <option value="each">Each</option>
                                    </select>
                                    <div class="text-danger" id="uom-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success" id="btn-submit-detail">Add Detail</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Add Detail Modal -->

    <!-- Edit Modal -->
    <div class="modal fade" id="edit-detail-modal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="editModalLabel">Edit Reimbursement Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-detail-form" method="POST"
                        action="{{ route('user-payreqs.reimburse.update_detail') }}">
                        @csrf
                        <input type="hidden" id="edit-id" name="realization_detail_id">
                        <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                        <div class="row">
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="edit-description">Description</label>
                                    <input type="text" name="description" id="edit-description" class="form-control">
                                    <div class="invalid-feedback" id="edit-description-error"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="edit-amount">Amount</label>
                                    <input type="text" name="amount" id="edit-amount" class="form-control"
                                        onkeyup="formatNumber(this)">
                                    <div class="text-danger" id="edit-amount-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="edit-unit_no">Unit No</label>
                                    <select id="edit-unit_no" name="unit_no" class="form-control select2bs4">
                                        <option value="">-- select unit no --</option>
                                        @foreach ($equipments as $item)
                                            <option value="{{ $item->unit_code }}">{{ $item->unit_code }} -
                                                {{ $item->project }} - {{ $item->plant_group }} -
                                                {{ $item->nomor_polisi }}</option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger" id="edit-unit_no-error"></div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="edit-nopol">No Polisi</label>
                                    <input type="text" name="nopol" id="edit-nopol" class="form-control">
                                    <div class="text-danger" id="edit-nopol-error"></div>
                                </div>
                            </div>
                            <div class="col-1">
                                <div class="form-group">
                                    <label for="edit-qty">Qty</label>
                                    <input id="edit-qty" name="qty" class="form-control">
                                    <div class="text-danger" id="edit-qty-error"></div>
                                </div>
                            </div>
                            <div class="col-1">
                                <div class="form-group">
                                    <label for="edit-km_position">HM</label>
                                    <input id="edit-km_position" name="km_position" class="form-control">
                                    <div class="text-danger" id="edit-km_position-error"></div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="edit-type">Type</label>
                                    <select id="edit-type" name="type" class="form-control select2bs4">
                                        <option value="">-- type --</option>
                                        <option value="fuel">Fuel</option>
                                        <option value="service">Service</option>
                                        <option value="tax">STNK / Tax</option>
                                        <option value="other">Others</option>
                                    </select>
                                    <div class="text-danger" id="edit-type-error"></div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="edit-uom">UOM</label>
                                    <select id="edit-uom" name="uom" class="form-control select2bs4">
                                        <option value="">-- uom --</option>
                                        <option value="liter">liter</option>
                                        <option value="each">Each</option>
                                    </select>
                                    <div class="text-danger" id="edit-uom-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" id="btn-update-detail" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->

    <!-- Hidden data for edit form -->
    <div id="realization-details-data" style="display: none;">
        @if ($realization->realizationDetails->count() > 0)
            @foreach ($realization->realizationDetails as $detail)
                <div data-id="{{ $detail->id }}" data-description="{{ $detail->description }}"
                    data-amount="{{ $detail->amount }}" data-unit-no="{{ $detail->unit_no }}"
                    data-nopol="{{ $detail->nopol }}" data-qty="{{ $detail->qty }}"
                    data-km-position="{{ $detail->km_position }}" data-type="{{ $detail->type }}"
                    data-uom="{{ $detail->uom }}">
                </div>
            @endforeach
        @endif
    </div>
@endsection

@section('scripts')
    <!-- Bootstrap 4 -->
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        // Make formatNumber globally available for use in inline events
        function formatNumber(input) {
            // Remove any non-digit characters except dots
            let value = input.value.replace(/[^\d.]/g, '');

            // Ensure only one decimal point
            let parts = value.split('.');
            if (parts.length > 2) {
                parts = [parts[0], parts.slice(1).join('')];
            }

            // Add thousand separators
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            // Join with decimal part if exists
            input.value = parts.join('.');
        }

        // Function to confirm delete - must be globally accessible
        window.confirmDelete = function(detailId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteDetail(detailId);
                }
            });
        }

        // Delete detail function using AJAX
        function deleteDetail(detailId) {
            $.ajax({
                url: '{{ route('user-payreqs.reimburse.delete_detail') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    realization_detail_id: detailId,
                    realization_id: '{{ $realization->id }}'
                },
                beforeSend: function() {
                    // Show loading overlay
                    $('#details-table').addClass('overlay');
                    $('#details-table').append(
                        '<div class="overlay-content"><i class="fas fa-spinner fa-spin"></i> Deleting...</div>'
                    );
                },
                success: function(response) {
                    if (response.status === 'success') {
                        // Remove the row from the table
                        $('#detail-row-' + detailId).fadeOut('slow', function() {
                            $(this).remove();

                            // Check if there are any rows left
                            if ($('#details-table tbody tr').length === 0) {
                                $('#details-table tbody').append(
                                    '<tr id="no-data-row"><td colspan="4" class="text-center">No Data Found</td></tr>'
                                );
                            }

                            // Update the total amount
                            $('#total-amount').text(numberFormat(response.total));
                        });

                        // Show success message
                        showAlert('Detail deleted successfully', 'success');
                    } else {
                        showAlert('Error deleting detail', 'error');
                    }
                },
                error: function(xhr) {
                    showAlert('Error: ' + (xhr.responseJSON?.message || 'Failed to delete detail'), 'error');
                },
                complete: function() {
                    // Remove loading overlay
                    $('#details-table').removeClass('overlay');
                    $('.overlay-content').remove();
                }
            });
        }

        // Helper function to format numbers
        function numberFormat(number) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        }

        // Function to display notifications
        function showAlert(message, type) {
            // Configure Toastr options
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Call appropriate Toastr method based on type
            switch (type) {
                case 'success':
                    toastr.success(message, 'Success');
                    break;
                case 'info':
                    toastr.info(message, 'Information');
                    break;
                case 'warning':
                    toastr.warning(message, 'Warning');
                    break;
                case 'danger':
                case 'error':
                    toastr.error(message, 'Error');
                    break;
                default:
                    toastr.info(message, 'Information');
            }
        }

        // Function to add a new row to the table
        function addDetailRow(detail, index) {
            let newRow = `
                <tr id="detail-row-${detail.id}">
                    <td>${index}</td>
                    <td>${detail.description}
                        ${(detail.nopol || detail.unit_no) ?
                            `<br/>${detail.type === 'fuel' ?
                                                                                                            `<small>${detail.unit_no}, ${detail.nopol}, ${detail.type} ${detail.qty} ${detail.uom}. HM: ${detail.km_position}</small>` :
                                                                                                            `<small>${detail.type}, HM: ${detail.km_position}</small>`
                                                                                                        }` :
                            ''
                        }
                    </td>
                    <td class="text-right">${numberFormat(detail.amount)}</td>
                    <td>
                        <button type="button" class="btn btn-xs btn-info btn-edit" data-id="${detail.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <a href="javascript:void(0)" onclick="confirmDelete(${detail.id})" class="btn btn-xs btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
            `;

            // Remove the "No Data Found" row if it exists
            $('#no-data-row').remove();

            // Add the new row to the table
            $('#details-table tbody').append(newRow);

            // Add the detail data to the hidden data div
            $('#realization-details-data').append(`
                <div data-id="${detail.id}" data-description="${detail.description}"
                    data-amount="${detail.amount}" data-unit-no="${detail.unit_no || ''}"
                    data-nopol="${detail.nopol || ''}" data-qty="${detail.qty || ''}"
                    data-km-position="${detail.km_position || ''}" data-type="${detail.type || ''}"
                    data-uom="${detail.uom || ''}">
                </div>
            `);

            // Reattach event handlers
            attachEventHandlers();
        }

        // Function to update a row in the table
        function updateDetailRow(detail) {
            let rowHtml = `
                <td>${$('#detail-row-' + detail.id).index() + 1}</td>
                <td>${detail.description}
                    ${(detail.nopol || detail.unit_no) ?
                        `<br/>${detail.type === 'fuel' ?
                                                                                                        `<small>${detail.unit_no}, ${detail.nopol}, ${detail.type} ${detail.qty} ${detail.uom}. HM: ${detail.km_position}</small>` :
                                                                                                        `<small>${detail.type}, HM: ${detail.km_position}</small>`
                                                                                                    }` :
                        ''
                    }
                </td>
                <td class="text-right">${numberFormat(detail.amount)}</td>
                <td>
                    <button type="button" class="btn btn-xs btn-info btn-edit" data-id="${detail.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <a href="javascript:void(0)" onclick="confirmDelete(${detail.id})" class="btn btn-xs btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </td>
            `;

            // Update the row
            $('#detail-row-' + detail.id).html(rowHtml);

            // Update the hidden data div
            $(`#realization-details-data div[data-id="${detail.id}"]`).attr({
                'data-description': detail.description,
                'data-amount': detail.amount,
                'data-unit-no': detail.unit_no || '',
                'data-nopol': detail.nopol || '',
                'data-qty': detail.qty || '',
                'data-km-position': detail.km_position || '',
                'data-type': detail.type || '',
                'data-uom': detail.uom || ''
            });

            // Reattach event handlers
            attachEventHandlers();
        }

        // Function to attach event handlers to buttons
        function attachEventHandlers() {
            // Edit button click handler
            $('.btn-edit').off('click').on('click', function() {
                const detailId = $(this).data('id');
                console.log('Edit button clicked for detail ID:', detailId);

                // Find the correct detail data in our hidden divs
                const detailData = $(`#realization-details-data div[data-id="${detailId}"]`);

                if (detailData.length === 0) {
                    showAlert('Error: Detail data not found', 'error');
                    return;
                }

                // Fill the edit form with the data from attributes
                $('#edit-id').val(detailId);
                $('#edit-description').val(detailData.attr('data-description'));
                $('#edit-amount').val(numberFormat(detailData.attr('data-amount')));

                // Set select values and trigger change for Select2
                $('#edit-unit_no').val(detailData.attr('data-unit-no')).trigger('change');
                $('#edit-nopol').val(detailData.attr('data-nopol'));
                $('#edit-qty').val(detailData.attr('data-qty'));
                $('#edit-km_position').val(detailData.attr('data-km-position'));
                $('#edit-type').val(detailData.attr('data-type')).trigger('change');
                $('#edit-uom').val(detailData.attr('data-uom')).trigger('change');

                // Show the modal
                $('#edit-detail-modal').modal('show');
            });

            // Attach handler to submit payreq button
            attachSubmitPayreqHandler();
        }

        // Function to attach handler to submit payreq button
        function attachSubmitPayreqHandler() {
            $('#btn-submit-payreq').off('click').on('click', function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to submit this payreq. This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            html: 'Please wait while we submit your payreq.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Change the button to a submit type and trigger native form submission
                        $('#btn-submit-payreq').attr('type', 'submit').click();
                    }
                });
            });
        }

        $(document).ready(function() {
            // Log initialization
            console.log("Initializing page...");

            // Initialize Select2 with better search functionality
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder') || '-- select an option --';
                },
                // Enable searching
                minimumResultsForSearch: 1
            });

            // Specifically enhance unit_no dropdown with advanced search
            $('#unit_no, #edit-unit_no').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: '-- select unit no --',
                minimumInputLength: 1, // Require at least 1 character to start searching
                // Enable searching by any part of the text
                matcher: function(params, data) {
                    // If there are no search terms, return all of the data
                    if ($.trim(params.term) === '') {
                        return data;
                    }

                    // Search term in lowercase
                    var term = params.term.toLowerCase();

                    // `data.text` should be the text that is displayed for the data object
                    var dataText = data.text.toLowerCase();

                    // Check if the text contains the term
                    if (dataText.indexOf(term) > -1) {
                        return data;
                    }

                    // Return null if the term should not be displayed
                    return null;
                }
            });

            // Fix Select2 in Bootstrap modal
            $(document).on('shown.bs.modal', '.modal', function() {
                $(this).find('.select2bs4').each(function() {
                    var dropdownParent = $(this).closest('.modal');

                    // Special handling for unit_no in modal
                    if ($(this).attr('id') === 'unit_no' || $(this).attr('id') === 'edit-unit_no') {
                        $(this).select2({
                            theme: 'bootstrap4',
                            dropdownParent: dropdownParent,
                            width: '100%',
                            allowClear: true,
                            placeholder: '-- select unit no --',
                            minimumInputLength: 1,
                            // Enable searching by any part of the text
                            matcher: function(params, data) {
                                // If there are no search terms, return all of the data
                                if ($.trim(params.term) === '') {
                                    return data;
                                }

                                // Search term in lowercase
                                var term = params.term.toLowerCase();

                                // `data.text` should be the text that is displayed for the data object
                                var dataText = data.text.toLowerCase();

                                // Check if the text contains the term
                                if (dataText.indexOf(term) > -1) {
                                    return data;
                                }

                                // Return null if the term should not be displayed
                                return null;
                            }
                        });
                    } else {
                        $(this).select2({
                            theme: 'bootstrap4',
                            dropdownParent: dropdownParent,
                            width: '100%',
                            allowClear: true,
                            minimumResultsForSearch: 1
                        });
                    }
                });
            });

            // CSRF token for ajax requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Format all amount inputs on page load
            const amountInputs = document.querySelectorAll('input[name="amount"]');
            amountInputs.forEach(input => formatNumber(input));

            // Handle RAB update
            $('#update_rab').click(function() {
                var $button = $(this);
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                var rab_id = $('#rab_id').val();
                var remarks = $('#remarks').val();
                var payreq_id = '{{ $payreq->id }}';

                $.ajax({
                    url: '{{ route('user-payreqs.reimburse.update_rab') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        rab_id: rab_id,
                        remarks: remarks,
                        payreq_id: payreq_id,
                    },
                    success: function(response) {
                        $button.prop('disabled', false).html('update');
                        if (response.status == 'success') {
                            showAlert('RAB and remarks updated successfully', 'success');
                        } else {
                            showAlert('Error updating RAB: ' + response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $button.prop('disabled', false).html('update');
                        showAlert('Error updating RAB: ' + (xhr.responseJSON?.message ||
                            'An error occurred'), 'error');
                    }
                });
            });

            // Form submission handling for add-detail-form
            $('#add-detail-form').on('submit', function(e) {
                e.preventDefault(); // Prevent normal form submission

                // Clear previous error messages
                $('.invalid-feedback, .text-danger').empty();

                // Get the data
                let formData = $(this).serialize();

                // Get the amount value and clean it
                let amountInput = $('#amount');
                let amount = amountInput.val();
                let cleanAmount = amount.replace(/,/g, '');

                // Update the form data with the cleaned amount
                formData += '&amount=' + cleanAmount;

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        // Show loading overlay
                        $('#add-detail-modal .modal-content').addClass('overlay');
                        $('#add-detail-modal .modal-content').append(
                            '<div class="overlay-content"><i class="fas fa-spinner fa-spin"></i> Saving...</div>'
                        );
                        $('#btn-submit-detail').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            // Add the new row to the table
                            const rowIndex = $('#details-table tbody tr').length + 1;
                            addDetailRow(response.detail, rowIndex);

                            // Update the total amount
                            $('#total-amount').text(numberFormat(response.total));

                            // Reset the form
                            $('#add-detail-form')[0].reset();
                            $('#unit_no, #type, #uom').val('').trigger('change');

                            // Close the modal
                            $('#add-detail-modal').modal('hide');

                            // Show success message
                            showAlert('Detail added successfully', 'success');

                            // Enable the submit button if it was disabled
                            if ($('#btn-submit-payreq').length === 0) {
                                // Create the submit button if it doesn't exist
                                let submitBtn = `
                                    @csrf
                                    <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                                    <button type="button" id="btn-submit-payreq" class="btn btn-sm btn-warning float-right mx-2">
                                        <i class="fas fa-paper-plane"></i> <b>Submit Payreq</b>
                                    </button>
                                `;
                                $('#submit-payreq-form').html(submitBtn);

                                // Attach the click event to the new button
                                attachSubmitPayreqHandler();

                                // Ensure the form is properly initialized with CSRF token
                                if ($('#submit-payreq-form input[name="_token"]').length ===
                                    0) {
                                    $('#submit-payreq-form').prepend(
                                        '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                                    );
                                }
                            }
                        } else {
                            showAlert('Error adding detail', 'error');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) { // Validation error
                            const errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#' + key + '-error').text(value[0]).show();
                            });
                            showAlert('Please correct the errors in the form', 'error');
                        } else {
                            showAlert('Error: ' + (xhr.responseJSON?.message ||
                                'Failed to add detail'), 'error');
                        }
                    },
                    complete: function() {
                        // Remove loading overlay
                        $('#add-detail-modal .modal-content').removeClass('overlay');
                        $('.overlay-content').remove();
                        $('#btn-submit-detail').prop('disabled', false);
                    }
                });
            });

            // Form submission handling for edit-detail-form
            $('#edit-detail-form').on('submit', function(e) {
                e.preventDefault(); // Prevent normal form submission

                // Clear previous error messages
                $('.invalid-feedback, .text-danger').empty();

                // Validate required fields
                let description = $('#edit-description').val();
                let amount = $('#edit-amount').val();

                if (!description) {
                    $('#edit-description-error').text('Description is required').show();
                    return false;
                }

                if (!amount) {
                    $('#edit-amount-error').text('Amount is required').show();
                    return false;
                }

                // Get the data
                let formData = $(this).serialize();

                // Get the amount value and clean it
                let cleanAmount = amount.replace(/,/g, '');

                // Update the form data with the cleaned amount
                formData += '&amount=' + cleanAmount;

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    beforeSend: function() {
                        // Show loading overlay
                        $('#edit-detail-modal .modal-content').addClass('overlay');
                        $('#edit-detail-modal .modal-content').append(
                            '<div class="overlay-content"><i class="fas fa-spinner fa-spin"></i> Updating...</div>'
                        );
                        $('#btn-update-detail').prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            // Update the row in the table
                            updateDetailRow(response.detail);

                            // Update the total amount
                            $('#total-amount').text(numberFormat(response.total));

                            // Close the modal
                            $('#edit-detail-modal').modal('hide');

                            // Show success message
                            showAlert('Detail updated successfully', 'success');
                        } else {
                            showAlert('Error updating detail', 'error');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) { // Validation error
                            const errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#edit-' + key + '-error').text(value[0]).show();
                            });
                            showAlert('Please correct the errors in the form', 'error');
                        } else {
                            showAlert('Error: ' + (xhr.responseJSON?.message ||
                                'Failed to update detail'), 'error');
                        }
                    },
                    complete: function() {
                        // Remove loading overlay
                        $('#edit-detail-modal .modal-content').removeClass('overlay');
                        $('.overlay-content').remove();
                        $('#btn-update-detail').prop('disabled', false);
                    }
                });
            });

            // Handle Add Detail button click
            $('#btn-submit-detail').click(function() {
                // Basic form validation
                let description = $('#description').val();
                let amount = $('#amount').val();

                if (!description) {
                    $('#description-error').text('Description is required').show();
                    return;
                }

                if (!amount) {
                    $('#amount-error').text('Amount is required').show();
                    return;
                }

                // Submit the form
                $('#add-detail-form').submit();
            });

            // Initial attachment of event handlers
            attachEventHandlers();
        });
    </script>
@endsection
