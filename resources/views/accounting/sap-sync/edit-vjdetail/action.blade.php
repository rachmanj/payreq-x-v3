@php
    $vj = $vj ?? $model->verificationJournal;
    $equipments = $equipments ?? collect();
    $realizationDetail = $realizationDetail ?? \App\Support\VerificationJournalDetailDescriptionEnricher::matchedRealizationDetail($model);
@endphp

@if ($vj && !$vj->sap_journal_no)
    <div class="vj-inline-actions">
        <button type="button" class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-edit edit-btn"
            data-toggle="modal" data-target="#vjdetail-edit-{{ $model->id }}">
            <i class="fas fa-edit"></i>
            <span>Edit</span>
        </button>
    </div>
@elseif ($vj && $vj->sap_journal_no)
    <span class="vj-chip vj-chip-neutral" title="Cannot edit: Already posted to SAP">
        <i class="fas fa-lock"></i> Posted
    </span>
@endif

<div class="modal fade" id="vjdetail-edit-{{ $model->id }}">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
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
                    @if ($model->debit_credit === 'credit' && $vj)
                        <div class="vj-alert vj-alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Credit Entry:</strong> Only cash or bank accounts from project
                            <strong>{{ $vj->project }}</strong> can be selected.
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="account_code">Account Number</label>
                        <select id="account_code-{{ $model->id }}" name="account_code"
                            class="form-control select2-modal">
                            @foreach (\App\Models\Account::forVjDetailSelection($model->debit_credit, $vj?->project) as $item)
                                <option value="{{ $item->account_number }}"
                                    {{ old('account_code', $model->account_code) == $item->account_number ? 'selected' : '' }}>
                                    {{ $item->account_number . ' - ' . $item->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="project">Project</label>
                        @if (auth()->user()->hasAnyRole(['superadmin', 'admin', 'cashier', 'cashier_bo']))
                            <select id="project-{{ $model->id }}" name="project" class="form-control select2-modal">
                                @foreach (\App\Models\Project::orderBy('code')->get() as $item)
                                    <option value="{{ $item->code }}"
                                        {{ old('project', $model->project) == $item->code ? 'selected' : '' }}>
                                        {{ $item->code }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control" value="{{ $model->project }}" readonly>
                            <input type="hidden" name="project" value="{{ $model->project }}">
                        @endif
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
                        <textarea class="form-control" name="description" rows="3">{{ old('description', \App\Support\VerificationJournalDetailDescriptionEnricher::baseDescription($model->description)) }}</textarea>
                    </div>

                    @if ($model->debit_credit === 'debit' && $model->realization_no && $realizationDetail)
                        <div class="vj-form-panel">
                            <h6 class="mb-3">
                                <i class="fas fa-truck"></i> Unit / Expense Details
                            </h6>
                            <input type="hidden" name="realization_detail_id" value="{{ $realizationDetail->id }}">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unit_no-{{ $model->id }}">Unit No</label>
                                        <select id="unit_no-{{ $model->id }}" name="unit_no"
                                            class="form-control select2-modal">
                                            <option value="">-- select unit no --</option>
                                            @foreach ($equipments as $item)
                                                <option value="{{ $item->unit_code }}"
                                                    data-nopol="{{ $item->nomor_polisi }}"
                                                    {{ old('unit_no', $realizationDetail->unit_no) == $item->unit_code ? 'selected' : '' }}>
                                                    {{ $item->unit_code }} - {{ $item->project }} -
                                                    {{ $item->plant_group }} - {{ $item->nomor_polisi }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nopol-{{ $model->id }}">No Polisi</label>
                                        <input type="text" id="nopol-{{ $model->id }}" name="nopol"
                                            class="form-control"
                                            value="{{ old('nopol', $realizationDetail->nopol) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="expense_date-{{ $model->id }}">Expense Date</label>
                                        <input type="date" id="expense_date-{{ $model->id }}" name="expense_date"
                                            class="form-control"
                                            value="{{ old('expense_date', $realizationDetail->expense_date ? \Illuminate\Support\Carbon::parse($realizationDetail->expense_date)->format('Y-m-d') : $model->realization_date) }}">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type-{{ $model->id }}">Type</label>
                                        <select id="type-{{ $model->id }}" name="type" class="form-control select2-modal">
                                            <option value="">-- type --</option>
                                            @foreach (['fuel' => 'Fuel', 'service' => 'Service', 'tax' => 'STNK / Tax', 'other' => 'Others'] as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('type', $realizationDetail->type) == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="qty-{{ $model->id }}">Qty</label>
                                        <input type="number" id="qty-{{ $model->id }}" name="qty" class="form-control"
                                            value="{{ old('qty', $realizationDetail->qty) }}">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="uom-{{ $model->id }}">UOM</label>
                                        <select id="uom-{{ $model->id }}" name="uom" class="form-control select2-modal">
                                            <option value="">-- uom --</option>
                                            @foreach (['liter' => 'liter', 'each' => 'Each'] as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('uom', $realizationDetail->uom) == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-0">
                                        <label for="km_position-{{ $model->id }}">HM</label>
                                        <input type="number" id="km_position-{{ $model->id }}" name="km_position"
                                            class="form-control"
                                            value="{{ old('km_position', $realizationDetail->km_position) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button type="submit" class="vj-btn vj-btn-primary submit-btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#vjdetail-edit-{{ $model->id }}').on('shown.bs.modal', function() {
            $('#account_code-{{ $model->id }}').select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $('#vjdetail-edit-{{ $model->id }}')
            });

            @if (auth()->user()->hasAnyRole(['superadmin', 'admin', 'cashier', 'cashier_bo']))
            $('#project-{{ $model->id }}').select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $('#vjdetail-edit-{{ $model->id }}')
            });
            @endif

            $('#cost_center-{{ $model->id }}').select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $('#vjdetail-edit-{{ $model->id }}')
            });

            $('#unit_no-{{ $model->id }}, #type-{{ $model->id }}, #uom-{{ $model->id }}').select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $('#vjdetail-edit-{{ $model->id }}')
            });

            $('#unit_no-{{ $model->id }}').on('change', function() {
                const nopol = $(this).find(':selected').data('nopol');
                if (nopol) {
                    $('#nopol-{{ $model->id }}').val(nopol);
                }
            });
        });

        $('#vjdetail-form-{{ $model->id }}').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();

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
                error: function(xhr) {
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
