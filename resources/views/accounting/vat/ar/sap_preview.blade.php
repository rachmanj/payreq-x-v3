@extends('templates.main')

@section('title_page')
    SAP B1 Submission Preview
@endsection

@section('breadcrumb_title')
    accounting / vat / sap preview
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice"></i> SAP B1 Submission Preview
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('accounting.vat.index', ['page' => 'sales', 'status' => 'incomplete']) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="sap-preview-form">
                        @csrf
                        
                        <!-- AR Invoice Section -->
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-file-invoice-dollar"></i> AR Invoice Details
                                </h3>
                                <div class="card-tools">
                                    <button type="button" id="edit-faktur-btn" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" id="update-faktur-btn" class="btn btn-sm btn-success" style="display: none;">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                    <button type="button" id="cancel-edit-btn" class="btn btn-sm btn-secondary" style="display: none;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="faktur-update-alert" class="alert" style="display: none;"></div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Customer</label>
                                            <input type="text" class="form-control" value="{{ $arPreview['customer']['name'] }} ({{ $arPreview['customer']['code'] }})" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Invoice No <span class="text-danger">*</span></label>
                                            <input type="text" id="invoice_no" name="invoice_no" class="form-control editable-field" value="{{ $arPreview['invoice_no'] }}" readonly required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Faktur No <span class="text-danger">*</span></label>
                                            <input type="text" id="faktur_no" name="faktur_no" class="form-control editable-field" value="{{ $arPreview['faktur_no'] }}" readonly required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Faktur Date <span class="text-danger">*</span></label>
                                            <input type="date" id="faktur_date" name="faktur_date" class="form-control editable-field" value="{{ $arPreview['faktur_date'] ? \Carbon\Carbon::parse($arPreview['faktur_date'])->format('Y-m-d') : '' }}" readonly required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Currency</label>
                                            <input type="text" class="form-control" value="{{ $arPreview['currency'] }}" readonly>
                                        </div>
                                        @if($faktur->attachment)
                                        <div class="form-group">
                                            <label>Attachment</label>
                                            <div>
                                                <a href="{{ $faktur->attachment }}" target="_blank" class="btn btn-info btn-sm">
                                                    <i class="fas fa-file-pdf"></i> Preview Faktur
                                                </a>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Posting Date</label>
                                            <input type="date" class="form-control" value="{{ $arPreview['dates']['posting_date'] }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Due Date</label>
                                            <input type="date" class="form-control" value="{{ $arPreview['dates']['due_date'] }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Tax Date</label>
                                            <input type="date" class="form-control" value="{{ $arPreview['dates']['tax_date'] }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Kurs</label>
                                            <input type="number" step="0.01" class="form-control" value="{{ $arPreview['kurs'] }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>AR Account (GL Account)</label>
                                            <input type="text" class="form-control" value="{{ $arPreview['accounts']['ar_account'] }}" readonly>
                                            <small class="text-muted">Used in AR Invoice line</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Revenue Account <span class="text-danger">*</span></label>
                                            <select name="revenue_account_code" class="form-control" required>
                                                <option value="41101" {{ $arPreview['accounts']['revenue_account'] == '41101' ? 'selected' : '' }}>41101</option>
                                                <option value="41201" {{ $arPreview['accounts']['revenue_account'] == '41201' ? 'selected' : '' }}>41201</option>
                                            </select>
                                            <small class="text-muted">Used in Journal Entry</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Item Code</label>
                                            <input type="text" class="form-control" value="{{ $itemCode }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Project</label>
                                            <input type="text" name="project" class="form-control" value="{{ $arPreview['project'] ?? '' }}" placeholder="Enter project code">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Department</label>
                                            <input type="text" class="form-control" value="{{ $arPreview['department'] }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>DPP</label>
                                            <input type="text" class="form-control text-right" value="{{ number_format($arPreview['amounts']['dpp'], 2, ',', '.') }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>PPN</label>
                                            <input type="text" class="form-control text-right" value="{{ number_format($arPreview['amounts']['ppn'], 2, ',', '.') }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>WTax Amount ({{ $arPreview['amounts']['wtax_code'] ?: '2%' }})</label>
                                            <input type="text" class="form-control text-right" value="{{ number_format($arPreview['amounts']['wtax_amount'], 2, ',', '.') }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Total</label>
                                            <input type="text" class="form-control text-right font-weight-bold" value="{{ number_format($arPreview['amounts']['total'], 2, ',', '.') }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Journal Entry Section -->
                        <div class="card card-info card-outline mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-book"></i> Journal Entry Details
                                </h3>
                                <div class="card-tools">
                                    <button type="button" id="edit-je-btn" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" id="update-je-btn" class="btn btn-sm btn-success" style="display: none;">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                    <button type="button" id="cancel-je-btn" class="btn btn-sm btn-secondary" style="display: none;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="je-update-alert" class="alert" style="display: none;"></div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Posting Date (Reference Date) <span class="text-danger">*</span></label>
                                            <input type="date" id="je_posting_date" name="je_posting_date" class="form-control je-editable-field" value="{{ $jePreview['dates']['posting_date'] }}" readonly required>
                                            <small class="text-muted">Default: Previous end of month</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tax Date <span class="text-danger">*</span></label>
                                            <input type="date" id="je_tax_date" name="je_tax_date" class="form-control je-editable-field" value="{{ $jePreview['dates']['tax_date'] }}" readonly required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Due Date <span class="text-danger">*</span></label>
                                            <input type="date" id="je_due_date" name="je_due_date" class="form-control je-editable-field" value="{{ $jePreview['dates']['due_date'] }}" readonly required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Revenue Account</label>
                                            <input type="text" class="form-control" value="{{ $jePreview['accounts']['revenue_account'] }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>AR Account</label>
                                            <input type="text" class="form-control" value="{{ $jePreview['accounts']['ar_account'] }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>DPP Amount</label>
                                            <input type="text" class="form-control text-right font-weight-bold" value="{{ number_format($jePreview['amounts']['dpp'], 2, ',', '.') }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Project</label>
                                            <input type="text" class="form-control" value="{{ $jePreview['project'] ?? '-' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Department</label>
                                            <input type="text" class="form-control" value="{{ $jePreview['department'] ?? '-' }}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Memo</label>
                                    <textarea class="form-control" rows="2" readonly>{{ $jePreview['memo'] }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div class="card card-success card-outline mt-3">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-check-circle"></i> Summary
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="mb-2">
                                            <strong>This submission will create:</strong>
                                        </p>
                                        <ul>
                                            <li><strong>AR Invoice</strong> in SAP B1 with DocDate: {{ \Carbon\Carbon::parse($arPreview['dates']['posting_date'])->format('d.m.Y') }}</li>
                                            <li><strong>Journal Entry</strong> with ReferenceDate: {{ \Carbon\Carbon::parse($jePreview['dates']['posting_date'])->format('d.m.Y') }}</li>
                                        </ul>
                                        <p class="text-warning mb-0">
                                            <i class="fas fa-exclamation-triangle"></i> Please review all details before submitting. This action cannot be undone.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="button" id="submit-btn" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane"></i> Confirm & Submit to SAP B1
                                </button>
                                <a href="{{ route('accounting.vat.index', ['page' => 'sales', 'status' => 'incomplete']) }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function() {
            const fakturId = {{ $faktur->id }};
            const submitUrl = `{{ route('accounting.vat.submit-to-sap', $faktur->id) }}`;
            const updateUrl = `{{ route('accounting.vat.update-sap-preview', $faktur->id) }}`;
            
            let isEditMode = false;
            let isJeEditMode = false;
            let originalValues = {
                invoice_no: $('#invoice_no').val(),
                faktur_no: $('#faktur_no').val(),
                faktur_date: $('#faktur_date').val()
            };
            let originalJeValues = {
                je_posting_date: $('#je_posting_date').val(),
                je_tax_date: $('#je_tax_date').val(),
                je_due_date: $('#je_due_date').val()
            };
            
            // AR Invoice Edit button click
            $('#edit-faktur-btn').on('click', function() {
                enableEditMode();
            });
            
            // AR Invoice Cancel button click
            $('#cancel-edit-btn').on('click', function() {
                cancelEditMode();
            });
            
            // AR Invoice Update button click
            $('#update-faktur-btn').on('click', function() {
                updateFakturDetails();
            });
            
            // Journal Entry Edit button click
            $('#edit-je-btn').on('click', function() {
                enableJeEditMode();
            });
            
            // Journal Entry Cancel button click
            $('#cancel-je-btn').on('click', function() {
                cancelJeEditMode();
            });
            
            // Journal Entry Update button click
            $('#update-je-btn').on('click', function() {
                updateJeDetails();
            });
            
            function enableEditMode() {
                isEditMode = true;
                $('.editable-field').prop('readonly', false).removeClass('bg-light');
                $('#edit-faktur-btn').hide();
                $('#update-faktur-btn').show();
                $('#cancel-edit-btn').show();
                $('#submit-btn').prop('disabled', true);
                $('#faktur-update-alert').hide();
            }
            
            function cancelEditMode() {
                isEditMode = false;
                // Restore original values
                $('#invoice_no').val(originalValues.invoice_no);
                $('#faktur_no').val(originalValues.faktur_no);
                $('#faktur_date').val(originalValues.faktur_date);
                
                $('.editable-field').prop('readonly', true).addClass('bg-light');
                $('#edit-faktur-btn').show();
                $('#update-faktur-btn').hide();
                $('#cancel-edit-btn').hide();
                $('#submit-btn').prop('disabled', false);
                $('#faktur-update-alert').hide();
            }
            
            function updateFakturDetails() {
                const btn = $('#update-faktur-btn');
                const formData = {
                    update_type: 'ar_invoice',
                    invoice_no: $('#invoice_no').val(),
                    faktur_no: $('#faktur_no').val(),
                    faktur_date: $('#faktur_date').val(),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                };
                
                // Validate
                if (!formData.invoice_no || !formData.faktur_no || !formData.faktur_date) {
                    showAlert('Please fill in all required fields.', 'danger');
                    return;
                }
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                
                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        // Update original values
                        originalValues = {
                            invoice_no: response.data.invoice_no,
                            faktur_no: response.data.faktur_no,
                            faktur_date: response.data.faktur_date
                        };
                        
                        // Exit edit mode
                        isEditMode = false;
                        $('.editable-field').prop('readonly', true).addClass('bg-light');
                        $('#edit-faktur-btn').show();
                        $('#update-faktur-btn').hide();
                        $('#cancel-edit-btn').hide();
                        $('#submit-btn').prop('disabled', false);
                        
                        showAlert('Faktur details updated successfully.', 'success');
                        
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while updating faktur details.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            errorMessage = errors.join('<br>');
                        }
                        
                        showAlert(errorMessage, 'danger');
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                    }
                });
            }
            
            function showAlert(message, type) {
                const alertDiv = $('#faktur-update-alert');
                alertDiv.removeClass('alert-success alert-danger alert-warning alert-info')
                         .addClass('alert-' + type)
                         .html(message)
                         .show();
                
                // Auto-hide after 5 seconds
                setTimeout(function() {
                    alertDiv.fadeOut();
                }, 5000);
            }
            
            function enableJeEditMode() {
                isJeEditMode = true;
                $('.je-editable-field').prop('readonly', false).removeClass('bg-light');
                $('#edit-je-btn').hide();
                $('#update-je-btn').show();
                $('#cancel-je-btn').show();
                $('#submit-btn').prop('disabled', true);
                $('#je-update-alert').hide();
            }
            
            function cancelJeEditMode() {
                isJeEditMode = false;
                // Restore original values
                $('#je_posting_date').val(originalJeValues.je_posting_date);
                $('#je_tax_date').val(originalJeValues.je_tax_date);
                $('#je_due_date').val(originalJeValues.je_due_date);
                
                $('.je-editable-field').prop('readonly', true).addClass('bg-light');
                $('#edit-je-btn').show();
                $('#update-je-btn').hide();
                $('#cancel-je-btn').hide();
                $('#submit-btn').prop('disabled', false);
                $('#je-update-alert').hide();
            }
            
            function updateJeDetails() {
                const btn = $('#update-je-btn');
                const formData = {
                    update_type: 'journal_entry',
                    je_posting_date: $('#je_posting_date').val(),
                    je_tax_date: $('#je_tax_date').val(),
                    je_due_date: $('#je_due_date').val(),
                    revenue_account_code: $('select[name="revenue_account_code"]').val(),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                };
                
                // Validate
                if (!formData.je_posting_date || !formData.je_tax_date || !formData.je_due_date) {
                    showJeAlert('Please fill in all required date fields.', 'danger');
                    return;
                }
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
                
                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        // Update original values
                        originalJeValues = {
                            je_posting_date: response.data.je_posting_date,
                            je_tax_date: response.data.je_tax_date,
                            je_due_date: response.data.je_due_date
                        };
                        
                        // Exit edit mode
                        isJeEditMode = false;
                        $('.je-editable-field').prop('readonly', true).addClass('bg-light');
                        $('#edit-je-btn').show();
                        $('#update-je-btn').hide();
                        $('#cancel-je-btn').hide();
                        $('#submit-btn').prop('disabled', false);
                        
                        showJeAlert('Journal Entry details updated successfully.', 'success');
                        
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while updating Journal Entry details.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            errorMessage = errors.join('<br>');
                        }
                        
                        showJeAlert(errorMessage, 'danger');
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                    }
                });
            }
            
            function showJeAlert(message, type) {
                const alertDiv = $('#je-update-alert');
                alertDiv.removeClass('alert-success alert-danger alert-warning alert-info')
                         .addClass('alert-' + type)
                         .html(message)
                         .show();
                
                // Auto-hide after 5 seconds
                setTimeout(function() {
                    alertDiv.fadeOut();
                }, 5000);
            }
            
            // Initialize: make fields readonly
            $('.editable-field').addClass('bg-light');
            $('.je-editable-field').addClass('bg-light');
            
            $('#submit-btn').on('click', function() {
                if (isEditMode || isJeEditMode) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Please Save Changes First',
                        text: 'You have unsaved changes. Please click "Update" to save your changes before submitting to SAP.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                const btn = $(this);
                const formData = $('#sap-preview-form').serialize();
                
                // Disable button
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
                
                Swal.fire({
                    title: 'Submitting to SAP B1...',
                    html: 'Please wait while we create the AR Invoice and Journal Entry.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: submitUrl,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            html: `
                                <p>AR Invoice and Journal Entry created successfully!</p>
                                <p><strong>AR Doc Num:</strong> ${response.ar_doc_num || 'N/A'}</p>
                                <p><strong>JE Num:</strong> ${response.je_num || 'N/A'}</p>
                            `,
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = `{{ route('accounting.vat.index', ['page' => 'sales', 'status' => 'incomplete']) }}`;
                            }
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while submitting to SAP B1.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            } catch (e) {
                                errorMessage = xhr.responseText.substring(0, 200);
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Failed',
                            html: `<p>${errorMessage}</p>`,
                            confirmButtonText: 'OK'
                        });
                        
                        // Re-enable button
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Confirm & Submit to SAP B1');
                    }
                });
            });
        });
    </script>
@endsection
