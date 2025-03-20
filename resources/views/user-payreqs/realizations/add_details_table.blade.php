<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
                <div id="summary-section" class="card-title float-right">
                    Payreq Amount: IDR <span
                        id="payreq-amount">{{ number_format($realization->payreq->amount, 2) }}</span> |
                    Variance: IDR <span
                        id="variance-amount">{{ number_format($realization->payreq->amount - $realization_details->sum('amount'), 2) }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="details-table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="50%">Description</th>
                                <th width="20%" class="text-right">Amount (IDR)</th>
                                <th width="25%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($realization_details->count() > 0)
                                @foreach ($realization_details as $item)
                                    <tr id="detail-row-{{ $item->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->description }}
                                            @if ($item->nopol !== null || $item->unit_no !== null)
                                                <br />
                                                @if ($item->type === 'fuel')
                                                    <small>{{ $item->unit_no }}, {{ $item->nopol }},
                                                        {{ $item->type }} {{ $item->qty }} {{ $item->uom }}. HM:
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
                                            <button type="button" class="btn btn-xs btn-danger btn-delete"
                                                data-id="{{ $item->id }}">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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
                                <td colspan="2" class="text-right">Total</td>
                                <td class="text-right"><b
                                        id="total-amount">{{ number_format($realization_details->sum('amount'), 2) }}</b>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to refresh the details table
    function refreshDetailsTable() {
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
                                    <button type="button" class="btn btn-xs btn-danger btn-delete" data-id="${item.id}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    // Update table body
                    $('#details-table tbody').html(tableContent);
                } else {
                    // No data
                    $('#details-table tbody').html(`
                        <tr id="no-data-row">
                            <td colspan="4" class="text-center">No Data Found</td>
                        </tr>
                    `);
                }

                // Update total
                $('#total-amount').text(numberFormat(totalAmount));
                $('#total-realization-amount').text(numberFormat(totalAmount));

                // Update variance
                const payreqAmount = parseFloat($('#payreq-amount').text().replace(/,/g, ''));
                const variance = payreqAmount - totalAmount;
                $('#variance-amount').text(numberFormat(variance));

                // Attach event handlers to new buttons
                attachEventHandlers();
            },
            error: function(xhr) {
                console.log("Error refreshing table:", xhr);
                showAlert('Error loading details: ' + (xhr.responseJSON?.message || 'An error occurred'),
                    'danger');
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

    // Function to attach event handlers to buttons
    function attachEventHandlers() {
        // Edit button click handler
        $('.btn-edit').click(function() {
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
                    $('#edit-amount').val(numberFormat(response.amount));

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
                        'An error occurred'), 'danger');
                }
            });
        });

        // Delete button click handler
        $('.btn-delete').click(function() {
            const detailId = $(this).data('id');

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
                    $.ajax({
                        url: "{{ url('user-payreqs/realizations') }}/" + detailId +
                            "/delete_detail",
                        type: "DELETE",
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log("Delete response:", response);
                            // Show success message
                            showAlert(response.message || 'Detail deleted successfully',
                                'success');

                            // Refresh the table
                            refreshDetailsTable();

                            // Disable submit button if no details
                            if ($('#details-table tbody tr').length <= 1) {
                                $('#btn-submit-realization').prop('disabled', true);
                            }
                        },
                        error: function(xhr) {
                            console.log("Error deleting:", xhr);
                            showAlert('Error: ' + (xhr.responseJSON?.message ||
                                'An error occurred'), 'danger');
                        }
                    });
                }
            });
        });
    }

    $(document).ready(function() {
        // Initial attachment of event handlers
        attachEventHandlers();

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
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log("Update response:", response);
                    // Close modal
                    $('#edit-detail-modal').modal('hide');

                    // Show success message
                    showAlert(response.message || 'Detail updated successfully', 'success');

                    // Refresh the table
                    refreshDetailsTable();
                },
                error: function(xhr) {
                    console.log("Error updating:", xhr);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        // Display validation errors
                        let errorMessage = '<ul>';
                        $.each(errors, function(field, messages) {
                            errorMessage += `<li>${messages[0]}</li>`;
                        });
                        errorMessage += '</ul>';

                        showAlert(errorMessage, 'danger');
                    } else {
                        showAlert('Error: ' + (xhr.responseJSON?.message ||
                            'An error occurred'), 'danger');
                    }
                }
            });
        });
    });
</script>
