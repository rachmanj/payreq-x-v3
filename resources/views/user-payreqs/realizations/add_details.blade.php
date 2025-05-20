@extends('templates.main')

@section('title_page')
    Realization Payreq
@endsection

@section('breadcrumb_title')
    realization
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
@endsection

@section('content')
    <!-- Add CSRF meta tag for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="row">
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Realization No</h5>
                <span class="description-text">{{ $realization->nomor }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq No</h5>
                <span class="description-text">{{ $realization->payreq->nomor }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq Amount</h5>
                <span class="description-text">{{ number_format($realization->payreq->amount, 2) }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="description-block">
                <h5 class="description-header">Realization Amount</h5>
                <span id="total-realization-amount"
                    class="description-text">{{ $realization_details->count() > 0 ? number_format($realization_details->sum('amount'), 2) : '0' }}</span>
            </div>
        </div>
    </div>
    <!-- /.row -->

    {{-- DETAILS SECTION --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h4 class="card-title">Realization Details</h4>
                    <a href="{{ route('user-payreqs.realizations.index') }}" class="btn btn-sm btn-info float-right">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <form id="submit-realization-form" action="{{ route('user-payreqs.realizations.submit_realization') }}"
                        method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                        <button type="button" id="btn-submit-realization" class="btn btn-sm btn-warning float-right mx-2"
                            {{ $realization_details->count() == 0 ? 'disabled' : '' }}>
                            Submit Realization
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-success float-right mr-2" data-toggle="modal"
                        data-target="#add-detail-modal">
                        <i class="fas fa-plus"></i> Add Detail
                    </button>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap" id="details-table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Description</th>
                                <th class="text-right" width="20%">Amount</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($realization_details->count() > 0)
                                @foreach ($realization_details as $index => $detail)
                                    <tr id="detail-row-{{ $detail->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $detail->description }}
                                            @if ($detail->nopol || $detail->unit_no)
                                                <br />
                                                @if ($detail->type == 'fuel')
                                                    <small>{{ $detail->unit_no ?? '' }}, {{ $detail->nopol ?? '' }},
                                                        {{ $detail->type ?? '' }} {{ $detail->qty ?? '' }}
                                                        {{ $detail->uom ?? '' }}. HM:
                                                        {{ $detail->km_position ?? '' }}</small>
                                                @else
                                                    <small>{{ $detail->type ?? '' }}, HM:
                                                        {{ $detail->km_position ?? '' }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($detail->amount, 2) }}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-info btn-edit"
                                                data-id="{{ $detail->id }}">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="javascript:void(0)" onclick="confirmDelete({{ $detail->id }})"
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
                                    {{ number_format($realization_details->sum('amount'), 2) }}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Payreq Amount:</th>
                                <th class="text-right" id="payreq-amount">
                                    {{ number_format($realization->payreq->amount, 2) }}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Variance:</th>
                                <th class="text-right" id="variance-amount">
                                    {{ number_format($realization->payreq->amount - $realization_details->sum('amount'), 2) }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {{-- END DETAILS SECTION --}}

    <!-- Delete form for non-AJAX submission -->
    <form id="delete-detail-form" action="" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- Add Detail Modal -->
    <div class="modal fade" id="add-detail-modal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title" id="addModalLabel">Add Realization Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="add-detail-form" method="POST"
                        action="{{ route('user-payreqs.realizations.store_detail') }}">
                        @csrf
                        <input type="hidden" name="realization_id" value="{{ $realization->id }}">

                        @if (isset($lotc_detail) && $lotc_detail)
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_lotc" name="is_lotc">
                                    <label class="custom-control-label" for="is_lotc">
                                        LOT Claim Realization ({{ $lotc_detail->lot_no }})
                                    </label>
                                </div>
                            </div>
                        @elseif($realization->payreq->lot_no)
                            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-exclamation-triangle"></i> LOTC Not Available for LOT
                                    {{ $realization->payreq->lot_no }}.
                                </div>
                                <a href="{{ route('user-payreqs.lotclaims.create') }}" class="btn btn-primary btn-sm"
                                    style="text-decoration: none;" target="_blank">
                                    <i class="fas fa-plus"></i> New LOTC
                                </a>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" name="description" value="{{ old('description') }}"
                                        id="description" class="form-control @error('description') is-invalid @enderror">
                                    <div class="invalid-feedback" id="description-error"></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="amount">Amount</label>
                                    <input type="text" name="amount" id="amount" class="form-control"
                                        value="{{ old('amount') }}" onkeyup="formatNumber(this)">
                                    <div class="text-danger" id="amount-error"></div>
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" id="btn-add-detail" class="btn btn-success">Add Detail</button>
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
                    <h5 class="modal-title" id="editModalLabel">Edit Realization Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit-detail-form">
                        <input type="hidden" id="edit-id" name="id">

                        @if (isset($lotc_detail) && $lotc_detail)
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="edit-is_lotc"
                                        name="is_lotc">
                                    <label class="custom-control-label" for="edit-is_lotc">
                                        LOT Claim Realization ({{ $lotc_detail->lot_no }})
                                    </label>
                                </div>
                            </div>
                        @elseif($realization->payreq->lot_no)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> LOTC Not Available for LOT
                                {{ $realization->payreq->lot_no }}, please create new.
                                <a href="{{ route('user-payreqs.lotclaims.create') }}?lot_no={{ $realization->payreq->lot_no }}"
                                    class="btn btn-warning btn-sm ml-2">
                                    <i class="fas fa-plus"></i> Create LOTC
                                </a>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-8">
                                <div class="form-group">
                                    <label for="edit-description">Description</label>
                                    <input type="text" name="description" id="edit-description" class="form-control">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="edit-amount">Amount</label>
                                    <input type="text" name="amount" id="edit-amount" class="form-control"
                                        onkeyup="formatNumber(this)">
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
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="edit-nopol">No Polisi</label>
                                    <input type="text" name="nopol" id="edit-nopol" class="form-control">
                                </div>
                            </div>
                            <div class="col-1">
                                <div class="form-group">
                                    <label for="edit-qty">Qty</label>
                                    <input id="edit-qty" name="qty" class="form-control">
                                </div>
                            </div>
                            <div class="col-1">
                                <div class="form-group">
                                    <label for="edit-km_position">HM</label>
                                    <input id="edit-km_position" name="km_position" class="form-control">
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
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" id="btn-update-detail" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Edit Modal -->
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
                    // Use regular form submission instead of AJAX
                    const form = document.getElementById('delete-detail-form');
                    form.action = "{{ url('user-payreqs/realizations') }}/" + detailId +
                        "/delete_detail";
                    form.submit();
                }
            });
        }

        // Wait for document to be fully loaded before executing any jQuery code
        $(document).ready(function() {
            console.log("Document ready - jQuery is loaded");

            // Display notification using Toastr
            function showAlert(message, type) {
                console.log("Notification:", type, message);

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

            // Helper function to format numbers
            function numberFormat(number) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number);
            }

            // Function to refresh the details table
            function refreshDetailsTable() {
                console.log("Refreshing details table...");

                $.ajax({
                    url: "{{ route('user-payreqs.realizations.get_details', $realization->id) }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log("Refresh table response:", response);
                        // Update table body
                        let tableContent = '';
                        let totalAmount = 0;

                        if (response.details && response.details.length > 0) {
                            // Remove no data row if it exists
                            $('#no-data-row').remove();

                            // Add rows for each detail
                            $.each(response.details, function(index, item) {
                                totalAmount += parseFloat(item.amount);

                                let additionalInfo = '';
                                if (item.nopol !== null || item.unit_no !== null) {
                                    additionalInfo = '<br/>';
                                    if (item.type === 'fuel') {
                                        additionalInfo +=
                                            `<small>${item.unit_no || ''}, ${item.nopol || ''}, ${item.type || ''} ${item.qty || ''} ${item.uom || ''}. HM: ${item.km_position || ''}</small>`;
                                    } else {
                                        additionalInfo +=
                                            `<small>${item.type || ''}, HM: ${item.km_position || ''}</small>`;
                                    }
                                }

                                tableContent += `
                                    <tr id="detail-row-${item.id}">
                                        <td>${index + 1}</td>
                                        <td>${item.description} ${additionalInfo}</td>
                                        <td class="text-right">${numberFormat(item.amount)}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-info btn-edit" data-id="${item.id}">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="javascript:void(0)" onclick="confirmDelete(${item.id})" class="btn btn-xs btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                `;
                            });

                            // Update table body
                            $('#details-table tbody').html(tableContent);

                            console.log("Table updated with " + response.details.length + " rows");
                        } else {
                            // No data
                            $('#details-table tbody').html(`
                                <tr id="no-data-row">
                                    <td colspan="4" class="text-center">No Data Found</td>
                                </tr>
                            `);
                            console.log("No details found");
                        }

                        // Update total
                        $('#total-amount').text(numberFormat(totalAmount));
                        $('#total-realization-amount').text(numberFormat(totalAmount));

                        // Update variance
                        const payreqAmount = parseFloat($('#payreq-amount').text().replace(/,/g, ''));
                        const variance = payreqAmount - totalAmount;
                        $('#variance-amount').text(numberFormat(variance));

                        // Attach event handlers to new buttons
                        console.log("Attaching event handlers to buttons...");
                        attachEventHandlers();
                    },
                    error: function(xhr) {
                        console.log("Error refreshing table:", xhr);
                        showAlert('Error loading details: ' + (xhr.responseJSON?.message ||
                                'An error occurred'),
                            'error');
                    }
                });
            }

            // Function to attach event handlers to buttons
            function attachEventHandlers() {
                // Edit button click handler
                $('.btn-edit').off('click').on('click', function() {
                    const detailId = $(this).data('id');

                    // Get detail data
                    $.ajax({
                        url: "{{ url('user-payreqs/realizations/get_detail') }}/" + detailId,
                        type: "GET",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log("Edit detail response:", response);
                            // Fill the edit form
                            $('#edit-id').val(response.id);
                            $('#edit-description').val(response.description);

                            // Format amount with thousand separator and 2 decimal places
                            const amount = parseFloat(response.amount);
                            const formattedAmount = amount.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            $('#edit-amount').val(formattedAmount);

                            // Check if description contains LOT Claim
                            @if (isset($lotc_detail) && $lotc_detail)
                                if (response.description.includes(
                                        'LOT Claim - {{ $lotc_detail->lot_no }}')) {
                                    $('#edit-is_lotc').prop('checked', true);
                                    $('#edit-description, #edit-amount').prop('readonly', true);
                                } else {
                                    $('#edit-is_lotc').prop('checked', false);
                                    $('#edit-description, #edit-amount').prop('readonly',
                                        false);
                                }
                            @endif

                            // Set select values and trigger change for Select2
                            $('#edit-unit_no').val(response.unit_no).trigger('change');
                            $('#edit-nopol').val(response.nopol);
                            $('#edit-qty').val(response.qty);
                            $('#edit-km_position').val(response.km_position);
                            $('#edit-type').val(response.type).trigger('change');
                            $('#edit-uom').val(response.uom).trigger('change');

                            // Show modal
                            $('#edit-detail-modal').modal('show');
                        },
                        error: function(xhr) {
                            console.log("Error getting detail:", xhr);
                            showAlert('Error loading detail: ' + (xhr.responseJSON?.message ||
                                'An error occurred'), 'error');
                        }
                    });
                });
            }

            // Log an initialization message
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
                    if ($(this).attr('id') === 'unit_no') {
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

            // Check for CSRF token
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            console.log("CSRF token:", csrfToken);
            if (!csrfToken) {
                console.warn("CSRF token not found! AJAX requests may fail.");
            }

            // Add Detail Form Submission - Prevent double submission
            $('#btn-add-detail').off('click').on('click', function(e) {
                e.preventDefault();

                // Disable button to prevent double submission
                const $button = $(this);
                $button.prop('disabled', true);

                console.log("Add detail button clicked");

                // Clear previous error messages
                $('.invalid-feedback, .text-danger').empty();
                $('.is-invalid').removeClass('is-invalid');

                // Get form data
                let formData = $('#add-detail-form').serialize();

                // Convert amount to number format (remove commas)
                let amount = $('#amount').val().replace(/,/g, '');
                formData = formData.replace('amount=' + $('#amount').val(), 'amount=' + amount);

                $.ajax({
                    url: "{{ route('user-payreqs.realizations.store_detail') }}",
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        console.log("Success response:", response);
                        // Show success message
                        showAlert('Detail added successfully', 'success');

                        // Close the modal
                        $('#add-detail-modal').modal('hide');

                        // Refresh the table
                        refreshDetailsTable();

                        // Reset form
                        $('#add-detail-form')[0].reset();
                        $('.select2bs4').val('').trigger('change');

                        // Enable submit button if we have details
                        $('#btn-submit-realization').prop('disabled', false);

                        // Re-enable the add button
                        $button.prop('disabled', false);
                    },
                    error: function(xhr) {
                        console.log("Error response:", xhr);
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            // Display validation errors
                            $.each(errors, function(field, messages) {
                                $('#' + field + '-error').text(messages[0]);
                                $('#' + field).addClass('is-invalid');
                            });

                            // Show summary error with toastr
                            showAlert('Please check the form for errors', 'error');
                        } else {
                            showAlert('Error: ' + (xhr.responseJSON?.message ||
                                'An error occurred'), 'error');
                        }

                        // Re-enable the add button
                        $button.prop('disabled', false);
                    }
                });
            });

            // Submit Realization
            $('#btn-submit-realization').click(function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to submit this realization",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('user-payreqs.realizations.submit_realization') }}",
                            type: "POST",
                            data: $('#submit-realization-form').serialize(),
                            success: function(response) {
                                Swal.fire(
                                    'Submitted!',
                                    'Realization submitted successfully',
                                    'success'
                                ).then(() => {
                                    window.location.href =
                                        "{{ route('user-payreqs.realizations.index') }}";
                                });
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Error!',
                                    xhr.responseJSON?.message ||
                                    'An error occurred',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Update detail form submission
            $('#btn-update-detail').click(function() {
                const detailId = $('#edit-id').val();

                // Prepare form data
                let formData = {
                    "_token": "{{ csrf_token() }}",
                    "description": $('#edit-description').val(),
                    "amount": $('#edit-amount').val().replace(/,/g, ''),
                    "unit_no": $('#edit-unit_no').val(),
                    "nopol": $('#edit-nopol').val(),
                    "qty": $('#edit-qty').val(),
                    "km_position": $('#edit-km_position').val(),
                    "type": $('#edit-type').val(),
                    "uom": $('#edit-uom').val()
                };

                $.ajax({
                    url: "{{ url('user-payreqs/realizations/update_detail') }}/" + detailId,
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        console.log("Update response:", response);
                        // Close modal
                        $('#edit-detail-modal').modal('hide');

                        // Show success message
                        showAlert('Detail updated successfully', 'success');

                        // Refresh the table
                        refreshDetailsTable();
                    },
                    error: function(xhr) {
                        console.log("Error updating:", xhr);
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            // Display validation errors
                            let errorMessage = 'Please fix the following issues:<ul>';
                            $.each(errors, function(field, messages) {
                                errorMessage += `<li>${messages[0]}</li>`;
                            });
                            errorMessage += '</ul>';

                            showAlert(errorMessage, 'error');
                        } else {
                            showAlert('Error: ' + (xhr.responseJSON?.message ||
                                'An error occurred'), 'error');
                        }
                    }
                });
            });

            // Add LOTC checkbox handler for add modal
            @if (isset($lotc_detail) && $lotc_detail)
                $('#is_lotc').change(function() {
                    if ($(this).is(':checked')) {
                        // Auto fill description with LOT number
                        $('#description').val('LOT Claim - {{ $lotc_detail->lot_no }}');

                        // Auto fill amount with total claim
                        const totalClaim = {{ $lotc_detail->total_claim }};
                        const formattedAmount = totalClaim.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        $('#amount').val(formattedAmount);

                        // Disable the inputs
                        $('#description, #amount').prop('readonly', true);
                    } else {
                        // Clear and enable the inputs
                        $('#description, #amount').val('').prop('readonly', false);
                    }
                });
            @endif

            // Add LOTC checkbox handler for edit modal
            @if (isset($lotc_detail) && $lotc_detail)
                $('#edit-is_lotc').change(function() {
                    if ($(this).is(':checked')) {
                        // Auto fill description with LOT number
                        $('#edit-description').val('LOT Claim - {{ $lotc_detail->lot_no }}');

                        // Auto fill amount with total claim
                        const totalClaim = {{ $lotc_detail->total_claim }};
                        const formattedAmount = totalClaim.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        $('#edit-amount').val(formattedAmount);

                        // Disable the inputs
                        $('#edit-description, #edit-amount').prop('readonly', true);
                    } else {
                        // Clear and enable the inputs
                        $('#edit-description, #edit-amount').val('').prop('readonly', false);
                    }
                });
            @endif

            // Initial attachment of event handlers
            attachEventHandlers();

            // Load initial data
            refreshDetailsTable();

            console.log("Document ready completed");
        });
    </script>
@endsection
