@extends('templates.main')

@section('title_page')
    Approval Request
@endsection

@section('breadcrumb_title')
    realization
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Advance Info</h3>
                    <a href="{{ route('approvals.request.realizations.index') }}"
                        class="btn btn-xs btn-primary float-right mx-2" id="back-button"><i class="fas fa-arrow-left"></i>
                        Back</a>
                    <button type="button" class="btn btn-xs btn-warning float-right" data-toggle="modal"
                        data-target="#approvals-update-{{ $document->id }}"><b>APPROVAL</b></button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-3 col-4">
            <div class="description-block border-right">
                <h5 class="description-header">Realization No</h5>
                <span class="description-text">{{ $document->realization->nomor }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-4">
            <div class="description-block border-right">
                <h5 class="description-header">Employee Name</h5>
                <span class="description-text">{{ $payreq->requestor->name }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-4">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq No</h5>
                <span class="description-text">{{ $payreq->nomor }}</span>
            </div>
        </div>
        <div class="col-sm-3 col-4">
            <div class="description-block border-right">
                <h5 class="description-header">Payreq Amount</h5>
                <span class="description-text">{{ number_format($payreq->amount, 2) }}</span>
            </div>
        </div>
    </div>
    {{-- <hr> --}}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="" cols="30" rows="2" class="form-control" readonly>{{ $payreq->remarks }}</textarea>
            </div>
        </div>
    </div>

    @if ($payreq->rab_id != null)
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="anggaran">RAB</label>
                    <input type="text" class="form-control"
                        value="No.{{ $payreq->anggaran->nomor }} {{ $payreq->anggaran->rab_no ? '| ' . $payreq->anggaran->rab_no : '' }} | {{ $payreq->anggaran->description }}"
                        readonly>
                </div>
            </div>
        </div>
    @endif

    @include('approvals-request.realizations.details_table')

    {{-- modal update --}}
    <div class="modal fade" id="approvals-update-{{ $document->id }}">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Approval for Realization No. {{ $document->realization->nomor }} |
                        {{ $payreq->requestor->name }}</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <form action="{{ route('approvals.plan.update', $document->id) }}" method="POST" class="approval-form">
                    @csrf @method('PUT')
                    <input type="hidden" name="document_type" value="realization">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="status">Approval Status</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">-- change status --</option>
                                        <option value="1">Approved</option>
                                        <option value="2">Revise</option>
                                        <option value="3">Reject</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div id="remarks-container" class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea name="remarks" id="approval-remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Store original data
            let originalTableHTML = '';
            let originalTotal = {{ $document_details->sum('amount') }};
            let isEditMode = false;

            // Departments data
            const departments = @json($departments);

            // Handle AJAX form submission for approval forms
            $('.approval-form').on('submit', function(e) {
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

                        // Redirect back to the index page after a short delay
                        setTimeout(function() {
                            window.location.href =
                                "{{ route('approvals.request.realizations.index') }}";
                        }, 1500);
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        var errorMessage = xhr.responseJSON ? xhr.responseJSON.message :
                            'An error occurred';
                        toastr.error(errorMessage);
                    }
                });
            });

            // Edit Details button click
            $('#btn-edit-details').on('click', function() {
                enterEditMode();
            });

            // Cancel button click
            $('#btn-cancel-edit').on('click', function() {
                if (confirm('Are you sure you want to discard all changes?')) {
                    exitEditMode();
                }
            });

            // Add Row button click
            $('#btn-add-row').on('click', function() {
                addNewRow();
            });

            // Save button click
            $('#btn-save-details').on('click', function() {
                saveDetails();
            });

            // Delete row button click (delegated)
            $(document).on('click', '.btn-delete-row', function() {
                const row = $(this).closest('tr');
                if ($('#details-tbody tr:visible').length <= 1) {
                    toastr.error('Cannot delete the last row');
                    return;
                }

                row.addClass('table-danger').find('td').css('text-decoration', 'line-through');
                row.data('deleted', true);
                $(this).prop('disabled', true);
                calculateTotal();
            });

            // Amount input change (delegated)
            $(document).on('input', '.amount-input', function() {
                calculateTotal();
            });

            function enterEditMode() {
                if (isEditMode) return;

                isEditMode = true;
                originalTableHTML = $('#details-table').html();

                // Toggle buttons
                $('#btn-edit-details').hide();
                $('#edit-mode-buttons').show();

                // Show action column
                $('.actions-column').show();

                // Transform each row
                $('#details-tbody tr').each(function() {
                    const row = $(this);
                    transformRowToEdit(row);
                });

                toastr.info('Edit mode activated');
            }

            function exitEditMode() {
                if (!isEditMode) return;

                isEditMode = false;

                // Restore original HTML
                $('#details-table').html(originalTableHTML);

                // Toggle buttons
                $('#btn-edit-details').show();
                $('#edit-mode-buttons').hide();

                toastr.info('Changes discarded');
            }

            function transformRowToEdit(row) {
                const detailId = row.data('detail-id');
                const description = row.data('description') || '';
                const amount = row.data('amount') || 0;
                const departmentId = row.data('department-id') || '';
                const unitNo = row.data('unit-no') || '';
                const type = row.data('type') || '';
                const qty = row.data('qty') || '';
                const uom = row.data('uom') || '';
                const kmPosition = row.data('km-position') || '';

                // Description cell with expandable unit info
                const descCell = row.find('.description-cell');
                descCell.html(`
                    <input type="text" class="form-control form-control-sm description-input" value="${description}" required>
                    <button type="button" class="btn btn-xs btn-link toggle-unit-info" style="padding: 2px 5px;">
                        <i class="fas fa-chevron-down"></i> Unit Info
                    </button>
                    <div class="unit-info-fields" style="display: none; margin-top: 5px;">
                        <input type="text" class="form-control form-control-sm mb-1 unit-no-input" placeholder="Unit No" value="${unitNo}">
                        <input type="text" class="form-control form-control-sm mb-1 type-input" placeholder="Type" value="${type}">
                        <div class="row">
                            <div class="col-4">
                                <input type="number" class="form-control form-control-sm qty-input" placeholder="Qty" value="${qty}">
                            </div>
                            <div class="col-4">
                                <input type="text" class="form-control form-control-sm uom-input" placeholder="UOM" value="${uom}">
                            </div>
                            <div class="col-4">
                                <input type="number" class="form-control form-control-sm km-input" placeholder="HM" value="${kmPosition}">
                            </div>
                        </div>
                    </div>
                `);

                // Department cell
                const deptCell = row.find('.department-cell');
                let deptOptions = '<option value="">-- Select --</option>';
                departments.forEach(dept => {
                    const selected = dept.id == departmentId ? 'selected' : '';
                    deptOptions +=
                        `<option value="${dept.id}" ${selected}>${dept.department_name}</option>`;
                });
                deptCell.html(
                    `<select class="form-control form-control-sm department-input">${deptOptions}</select>`);

                // Amount cell
                const amountCell = row.find('.amount-cell');
                amountCell.html(`
                    <input type="number" class="form-control form-control-sm amount-input text-right" 
                           value="${amount}" step="0.01" min="0" required>
                `);
            }

            // Toggle unit info fields
            $(document).on('click', '.toggle-unit-info', function() {
                const btn = $(this);
                const icon = btn.find('i');
                const fields = btn.siblings('.unit-info-fields');

                fields.slideToggle();
                icon.toggleClass('fa-chevron-down fa-chevron-up');
            });

            function addNewRow() {
                const rowCount = $('#details-tbody tr').length + 1;
                const newRow = $(`
                    <tr data-detail-id="" data-is-new="true">
                        <td class="row-number">${rowCount}</td>
                        <td class="description-cell">
                            <input type="text" class="form-control form-control-sm description-input" placeholder="Description" required>
                            <button type="button" class="btn btn-xs btn-link toggle-unit-info" style="padding: 2px 5px;">
                                <i class="fas fa-chevron-down"></i> Unit Info
                            </button>
                            <div class="unit-info-fields" style="display: none; margin-top: 5px;">
                                <input type="text" class="form-control form-control-sm mb-1 unit-no-input" placeholder="Unit No">
                                <input type="text" class="form-control form-control-sm mb-1 type-input" placeholder="Type">
                                <div class="row">
                                    <div class="col-4">
                                        <input type="number" class="form-control form-control-sm qty-input" placeholder="Qty">
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control form-control-sm uom-input" placeholder="UOM">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" class="form-control form-control-sm km-input" placeholder="HM">
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="department-cell">
                            <select class="form-control form-control-sm department-input">
                                <option value="">-- Select --</option>
                                ${departments.map(dept => `<option value="${dept.id}">${dept.department_name}</option>`).join('')}
                            </select>
                        </td>
                        <td class="amount-cell">
                            <input type="number" class="form-control form-control-sm amount-input text-right" 
                                   value="0" step="0.01" min="0" required>
                        </td>
                        <td class="text-center actions-column">
                            <button type="button" class="btn btn-xs btn-danger btn-delete-row">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);

                $('#details-tbody').append(newRow);
                newRow.find('.description-input').focus();
                calculateTotal();
            }

            function calculateTotal() {
                let total = 0;

                $('#details-tbody tr').each(function() {
                    const row = $(this);
                    if (row.data('deleted')) return;

                    const amount = parseFloat(row.find('.amount-input').val()) || 0;
                    total += amount;
                });

                const payreqAmount = {{ $payreq->amount }};
                const variance = payreqAmount - total;
                const diffFromOriginal = originalTotal - total;

                // Update display
                $('#total-amount-display').text(formatNumber(total));
                $('#variance-display').text(formatNumber(variance));

                // Show/hide warning (based on difference from original)
                if (Math.abs(diffFromOriginal) > 0.01) {
                    $('#amount-warning-row').show();
                    $('#variance-display').parent().addClass('text-danger');
                } else {
                    $('#amount-warning-row').hide();
                    $('#variance-display').parent().removeClass('text-danger');
                }
            }

            function formatNumber(num) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(num);
            }

            function collectDetailsData() {
                const details = [];
                const deletedIds = [];

                $('#details-tbody tr').each(function() {
                    const row = $(this);

                    if (row.data('deleted')) {
                        const id = row.data('detail-id');
                        if (id) deletedIds.push(id);
                        return;
                    }

                    const detail = {
                        id: row.data('detail-id') || null,
                        description: row.find('.description-input').val(),
                        amount: parseFloat(row.find('.amount-input').val()) || 0,
                        department_id: row.find('.department-input').val() || null,
                        unit_no: row.find('.unit-no-input').val() || null,
                        type: row.find('.type-input').val() || null,
                        qty: row.find('.qty-input').val() || null,
                        uom: row.find('.uom-input').val() || null,
                        km_position: row.find('.km-input').val() || null,
                    };

                    details.push(detail);
                });

                return {
                    details,
                    deletedIds
                };
            }

            function saveDetails() {
                const data = collectDetailsData();

                if (data.details.length === 0) {
                    toastr.error('Cannot save with no details');
                    return;
                }

                // Validate required fields
                let hasError = false;
                data.details.forEach((detail, index) => {
                    if (!detail.description || detail.description.trim() === '') {
                        toastr.error(`Row ${index + 1}: Description is required`);
                        hasError = true;
                    }
                    if (detail.amount < 0) {
                        toastr.error(`Row ${index + 1}: Amount must be positive`);
                        hasError = true;
                    }
                });

                if (hasError) return;

                // Show loading
                $('#btn-save-details').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: "{{ route('approvals.request.realizations.update-details', $document->id) }}",
                    method: 'PUT',
                    data: {
                        _token: "{{ csrf_token() }}",
                        details: data.details,
                        deleted_ids: data.deletedIds
                    },
                    success: function(response) {
                        toastr.success(response.message);

                        // Reload the page to show updated data
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        $('#btn-save-details').prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Save Changes');

                        const errorMessage = xhr.responseJSON?.message || 'Failed to save details';
                        toastr.error(errorMessage);

                        if (xhr.responseJSON?.errors) {
                            Object.values(xhr.responseJSON.errors).forEach(errors => {
                                errors.forEach(error => toastr.error(error));
                            });
                        }
                    }
                });
            }
        });
    </script>
@endsection
