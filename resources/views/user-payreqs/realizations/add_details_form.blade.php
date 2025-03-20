<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">Form</h4>
                <a href="{{ route('user-payreqs.realizations.index') }}" class="btn btn-sm btn-info float-right"><i
                        class="fas fa-arrow-left"></i> Back</a>
                <form id="submit-realization-form" action="{{ route('user-payreqs.realizations.submit_realization') }}"
                    method="POST">
                    @csrf
                    <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                    <button type="button" id="btn-submit-realization" class="btn btn-sm btn-warning float-right mx-2"
                        {{ $realization_details->count() == 0 ? 'disabled' : '' }}>
                        Submit Realization
                    </button>
                </form>
            </div>
            <form id="add-detail-form" method="POST" action="{{ route('user-payreqs.realizations.store_detail') }}">
                @csrf
                <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                <div class="card-body">
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
                </div>
                <div class="card-footer">
                    <button type="button" id="btn-add-detail" class="btn btn-sm btn-success btn-block">
                        <i class="fas fa-save"></i> ADD DETAIL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Add Detail Form Submission
        $('#btn-add-detail').click(function(e) {
            e.preventDefault();

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
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log("Success response:", response);
                    // Show success message
                    showAlert(response.message || 'Detail added successfully', 'success');

                    // Refresh the table
                    refreshDetailsTable();

                    // Reset form
                    $('#add-detail-form')[0].reset();
                    $('.select2bs4').val('').trigger('change');

                    // Enable submit button if we have details
                    $('#btn-submit-realization').prop('disabled', false);
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
                    } else {
                        showAlert('Error: ' + (xhr.responseJSON?.message ||
                            'An error occurred'), 'danger');
                    }
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
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire(
                                'Submitted!',
                                response.message ||
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
    });
</script>
