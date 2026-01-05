@php
    $vj = $vj ?? $model->verificationJournal;
@endphp

@if ($vj && !$vj->sap_journal_no)
    <button type="button" class="btn btn-xs btn-warning edit-btn" data-toggle="modal"
        data-target="#vjdetail-edit-{{ $model->id }}">
        <i class="fas fa-edit"></i> Edit
    </button>
@elseif ($vj && $vj->sap_journal_no)
    <span class="badge badge-secondary" title="Cannot edit: Already posted to SAP">
        <i class="fas fa-lock"></i> Posted
    </span>
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
                <input type="hidden" name="debit_credit" value="{{ $model->debit_credit }}">

                <div class="modal-body">
                    @php
                        $vj = $vj ?? $model->verificationJournal;
                    @endphp
                    
                    @if ($model->debit_credit === 'credit' && $vj)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Credit Entry:</strong> Only cash or bank accounts from project <strong>{{ $vj->project }}</strong> can be selected.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="account_code">Account Number</label>
                        <select id="account_code-{{ $model->id }}" name="account_code"
                            class="form-control select2-modal">
                            @php
                                if ($model->debit_credit === 'credit' && $vj) {
                                    $accounts = \App\Models\Account::whereIn('type', ['cash', 'bank'])
                                        ->where('project', $vj->project)
                                        ->orderBy('account_number')
                                        ->get();
                                } else {
                                    $accounts = \App\Models\Account::orderBy('account_number')->get();
                                }
                            @endphp
                            @foreach ($accounts as $item)
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
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.showAlert(response.message || 'Record updated successfully!', 'success');
                        $('#vjdetail-edit-{{ $model->id }}').modal('hide');
                        $('#vj_details').DataTable().ajax.reload(null, false);
                    } else {
                        window.showAlert(response.message || 'Update failed', 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'An error occurred while updating';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error (500): The update request failed on the server';
                    } else if (xhr.status === 422) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else {
                            errorMessage = 'Validation error (422): Please check the form data';
                        }
                    } else if (xhr.status === 419) {
                        errorMessage = 'CSRF token mismatch (419): Please refresh the page and try again';
                    }

                    window.showAlert(errorMessage, 'danger');
                },
                complete: function() {
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                }
            });
        });
    });
</script>
