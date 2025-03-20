@if ($model->debit_credit === 'debit')
    <button type="button" class="btn btn-xs btn-warning edit-btn" data-toggle="modal"
        data-target="#vjdetail-edit-{{ $model->id }}">
        <i class="fas fa-edit"></i> Edit
    </button>
@endif

{{-- modal update --}}
<div class="modal fade" id="vjdetail-edit-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit VJ Detail
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="vjdetail-form-{{ $model->id }}" class="vjdetail-form">
                @csrf
                <input type="hidden" name="vj_id" value="{{ $model->verification_journal_id }}">
                <input type="hidden" name="vj_detail_id" value="{{ $model->id }}">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="account_code">Account Number</label>
                        <select id="account_code-{{ $model->id }}" name="account_code"
                            class="form-control select2-modal">
                            @foreach (\App\Models\Account::orderBy('account_number')->get() as $item)
                                <option value="{{ $item->account_number }}"
                                    {{ old('account_code', $model->account_code) == $item->account_number ? 'selected' : '' }}>
                                    {{ $item->account_number . ' - ' . $item->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="project">Project</label>
                        <select id="project-{{ $model->id }}" name="project" class="form-control select2-modal">
                            @foreach (\App\Models\Project::orderBy('code')->get() as $item)
                                <option value="{{ $item->code }}"
                                    {{ old('project', $model->project) == $item->code ? 'selected' : '' }}>
                                    {{ $item->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cost_center">Cost Center</label>
                        <select id="cost_center-{{ $model->id }}" name="cost_center"
                            class="form-control select2-modal">
                            @foreach (\App\Models\Department::orderBy('department_name')->get() as $item)
                                <option value="{{ $item->sap_code }}"
                                    {{ old('cost_center', $model->cost_center) == $item->sap_code ? 'selected' : '' }}>
                                    {{ $item->department_name . ' - ' . $item->sap_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description', $model->description) }}</textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary submit-btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize modal when it's shown
        $('#vjdetail-edit-{{ $model->id }}').on('shown.bs.modal', function() {
            // Initialize Select2 elements
            $('#account_code-{{ $model->id }}').select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $('#vjdetail-edit-{{ $model->id }}')
            });

            $('#project-{{ $model->id }}').select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $('#vjdetail-edit-{{ $model->id }}')
            });

            $('#cost_center-{{ $model->id }}').select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $('#vjdetail-edit-{{ $model->id }}')
            });
        });

        // Form submission via AJAX
        $('#vjdetail-form-{{ $model->id }}').on('submit', function(e) {
            e.preventDefault();

            // Get form data for debugging
            const formData = $(this).serialize();
            console.log("Form data being submitted:", formData);

            // Show loading state
            let submitBtn = $(this).find('.submit-btn');
            let originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            submitBtn.prop('disabled', true);

            $.ajax({
                url: "{{ route('accounting.sap-sync.update_detail') }}",
                method: 'POST',
                data: formData,
                // Don't set dataType to allow jQuery to auto-detect
                success: function(response, status, xhr) {
                    console.log("Response type:", xhr.getResponseHeader('content-type'));

                    // Check if we got JSON or HTML
                    if (xhr.getResponseHeader('content-type').indexOf('json') !== -1) {
                        // JSON response
                        console.log("Success JSON response:", response);

                        // Show success message
                        window.showAlert(response.message || 'Record updated successfully!',
                            'success');
                    } else {
                        // HTML response - check for success indicators in the HTML
                        console.log("Success HTML response received");

                        // Success message based on HTML response
                        window.showAlert('Record updated successfully!', 'success');
                    }

                    // Hide modal
                    $('#vjdetail-edit-{{ $model->id }}').modal('hide');

                    // Refresh the datatable
                    $('#vj_details').DataTable().ajax.reload(null, false);
                },
                error: function(xhr, status, error) {
                    console.error("Error status:", status);
                    console.error("Error thrown:", error);

                    // Check content type
                    let contentType = xhr.getResponseHeader('content-type') || '';
                    console.log("Response content type:", contentType);

                    if (contentType.indexOf('html') !== -1) {
                        console.log("Received HTML error response");

                        // Check if we can see successful update indicators in the HTML
                        if (xhr.responseText.indexOf("toastr.success") !== -1 &&
                            xhr.responseText.indexOf("Detail Updated") !== -1) {

                            console.log("Found success message in HTML response");

                            // This was actually a success, so treat it as such
                            $('#vjdetail-edit-{{ $model->id }}').modal('hide');
                            window.showAlert('Record updated successfully!', 'success');
                            $('#vj_details').DataTable().ajax.reload(null, false);

                            // Reset button and return early
                            submitBtn.html(originalText);
                            submitBtn.prop('disabled', false);
                            return;
                        }
                    }

                    // Handle real errors
                    let errorMessage = 'An error occurred while updating';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMessage =
                            'Server error (500): The update request failed on the server';
                    } else if (xhr.status === 422) {
                        errorMessage = 'Validation error (422): Please check the form data';
                    } else if (xhr.status === 419) {
                        errorMessage =
                            'CSRF token mismatch (419): Please refresh the page and try again';
                    }

                    window.showAlert(errorMessage, 'danger');
                },
                complete: function() {
                    // Reset button state (except for the case where we returned early in the error handler)
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                }
            });
        });
    });
</script>
