@hasanyrole('superadmin|admin|cashier')
    @if (!$model->paid_date)
        <button type="button" class="btn btn-xs btn-primary" data-toggle="modal"
            data-target="#payment-method-{{ $model->id }}" title="Set Payment Method">
            <i class="fas fa-credit-card"></i> Payment Method
        </button>
    @endif

    @if (!$model->paid_date && in_array($model->payment_method, ['bilyet', 'auto_debit']) && !$model->sap_ap_doc_num)
        <button type="button" class="btn btn-xs btn-primary" data-toggle="modal"
            data-target="#create-sap-ap-{{ $model->id }}" title="Create SAP AP Invoice">
            <i class="fas fa-file-invoice-dollar"></i> Create AP
        </button>
    @endif

    @if (!$model->paid_date && in_array($model->payment_method, ['bilyet', 'auto_debit']))
        <button type="button" class="btn btn-xs btn-secondary" data-toggle="modal"
            data-target="#link-sap-ap-{{ $model->id }}" title="Link SAP AP Invoice">
            <i class="fas fa-link"></i> Link AP
        </button>
    @endif

    <button type="button" class="btn btn-xs btn-warning" data-toggle="modal"
        data-target="#installment-edit-{{ $model->id }}">
        <i class="fas fa-edit"></i>
    </button>
@endhasanyrole

@hasanyrole('superadmin')
    <form action="{{ route('accounting.loans.installments.destroy', $model->id) }}" method="POST" style="display: inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-xs btn-danger"
            onclick="return confirm('Are You sure You want to delete this record?')">
            <i class="fas fa-trash"></i>
        </button>
    </form>
@endhasanyrole

{{-- Modal edit --}}
<div class="modal fade" id="installment-edit-{{ $model->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title"> Edit Installment</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('accounting.loans.installments.update') }}" method="POST">
                @csrf

                <div class="modal-body">

                    <input type="hidden" name="installment_id" value="{{ $model->id }}">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="due_date">Due Date</label>
                                <input type="date" name="due_date" id="due_date" value="{{ $model->due_date }}"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label for="paid_date">Paid Date</label>
                                <input type="date" name="paid_date" id="paid_date" value="{{ $model->paid_date }}"
                                    class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="bilyet_no">Bilyet No</label>
                                <input type="text" name="bilyet_no" id="bilyet_no" value="{{ $model->bilyet_no }}"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group">
                                <label for="bilyet_amount">Bilyet Amount</label>
                                <input type="text" name="bilyet_amount" id="bilyet_amount"
                                    value="{{ $model->bilyet_amount }}" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="account_id">Account No</label>
                                <select name="account_id" id="account_id" class="form-control select2bs4">
                                    <option value="">-- select account --</option>
                                    @foreach (\App\Models\Account::where('type', 'bank')->get() as $account)
                                        <option value="{{ $account->id }}"
                                            {{ $account->id == $model->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-6">
                            {{--  --}}
                        </div>
                    </div>

                </div> <!-- /.modal-body -->

                <div class="modal-footer float-left">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>

            </form>

        </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>

{{-- Modal Payment Method --}}
<div class="modal fade" id="payment-method-{{ $model->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Set Payment Method for Installment #{{ $model->angsuran_ke }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Installment Details:</strong><br>
                    <strong>Amount:</strong> IDR {{ number_format($model->bilyet_amount, 2) }}<br>
                    <strong>Due Date:</strong> {{ date('d-M-Y', strtotime($model->due_date)) }}
                </div>

                <div class="form-group">
                    <label>Select Payment Method <span class="text-danger">*</span></label>
                    <div class="form-check">
                        <input class="form-check-input payment-method-radio" type="radio" name="payment_method_{{ $model->id }}" 
                            id="link_existing_{{ $model->id }}" value="link_existing" checked>
                        <label class="form-check-label" for="link_existing_{{ $model->id }}">
                            <i class="fas fa-link text-success"></i> Link Existing Bilyet Giro
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input payment-method-radio" type="radio" name="payment_method_{{ $model->id }}" 
                            id="create_new_{{ $model->id }}" value="create_new">
                        <label class="form-check-label" for="create_new_{{ $model->id }}">
                            <i class="fas fa-file-invoice text-success"></i> Create New Bilyet Giro
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input payment-method-radio" type="radio" name="payment_method_{{ $model->id }}" 
                            id="auto_debit_{{ $model->id }}" value="auto_debit">
                        <label class="form-check-label" for="auto_debit_{{ $model->id }}">
                            <i class="fas fa-university text-info"></i> Auto-Debit
                        </label>
                    </div>
                </div>

                {{-- Link Existing Bilyet Form --}}
                <div id="form-link-existing-{{ $model->id }}" class="payment-form">
                    <form action="{{ route('accounting.loans.installments.link_existing_bilyet', $model->id) }}" method="POST" id="link-existing-form-{{ $model->id }}">
                        @csrf
                        <div class="form-group">
                            <label for="bilyet_id_{{ $model->id }}">Select Existing Bilyet <span class="text-danger">*</span></label>
                            <select name="bilyet_id" id="bilyet_id_{{ $model->id }}" class="form-control select2bs4" required style="width: 100%;">
                                <option value="">-- select bilyet --</option>
                                @foreach (\App\Models\Bilyet::with('giro')->where('status', 'onhand')->where('project', auth()->user()->project)->where('purpose', 'loan_payment')->orderBy('bilyet_date', 'desc')->orderBy('nomor', 'asc')->get() as $bilyet)
                                    <option value="{{ $bilyet->id }}" data-giro="{{ $bilyet->giro->acc_no ?? '' }}"
                                        data-type="{{ $bilyet->type }}"
                                        data-nomor="{{ $bilyet->prefix . $bilyet->nomor }}"
                                        data-date="{{ $bilyet->bilyet_date ? date('d-M-Y', strtotime($bilyet->bilyet_date)) : '' }}">
                                        {{ $bilyet->prefix . $bilyet->nomor }}
                                        ({{ strtoupper($bilyet->type) }})
                                        - {{ $bilyet->giro->acc_no ?? 'N/A' }}
                                        @if ($bilyet->bilyet_date)
                                            - {{ date('d-M-Y', strtotime($bilyet->bilyet_date)) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only bilyets with status "On Hand" are shown.</small>
                        </div>

                        <div id="bilyet-details-{{ $model->id }}" style="display: none;">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h5 class="card-title">Selected Bilyet Details</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">Bilyet Number:</dt>
                                        <dd class="col-sm-8" id="selected-nomor-{{ $model->id }}">-</dd>
                                        <dt class="col-sm-4">Type:</dt>
                                        <dd class="col-sm-8" id="selected-type-{{ $model->id }}">-</dd>
                                        <dt class="col-sm-4">Account:</dt>
                                        <dd class="col-sm-8" id="selected-account-{{ $model->id }}">-</dd>
                                        <dt class="col-sm-4">Bilyet Date:</dt>
                                        <dd class="col-sm-8" id="selected-date-{{ $model->id }}">-</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Create New Bilyet Form --}}
                <div id="form-create-new-{{ $model->id }}" class="payment-form" style="display: none;">
                    <form action="{{ route('accounting.loans.installments.create_bilyet', $model->id) }}" method="POST" id="create-new-form-{{ $model->id }}">
                        @csrf
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="giro_id_new_{{ $model->id }}">Bank Account <span class="text-danger">*</span></label>
                                    <select name="giro_id" id="giro_id_new_{{ $model->id }}" class="form-control select2bs4" required>
                                        <option value="">-- select bank account --</option>
                                        @foreach (\App\Models\Giro::where('project', auth()->user()->project)->get() as $giro)
                                            <option value="{{ $giro->id }}"
                                                {{ $giro->id == $model->account_id ? 'selected' : '' }}>
                                                {{ $giro->acc_no }} - {{ $giro->acc_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-3">
                                <div class="form-group">
                                    <label for="type_new_{{ $model->id }}">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="type_new_{{ $model->id }}" class="form-control" required>
                                        <option value="cek">Cek</option>
                                        <option value="bg">BG</option>
                                        <option value="loa">LOA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-3">
                                <div class="form-group">
                                    <label for="bilyet_date_new_{{ $model->id }}">Bilyet Date <span class="text-danger">*</span></label>
                                    <input type="date" name="bilyet_date" id="bilyet_date_new_{{ $model->id }}" class="form-control"
                                        value="{{ $model->due_date }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-3">
                                <div class="form-group">
                                    <label for="prefix_new_{{ $model->id }}">Prefix</label>
                                    <input type="text" name="prefix" id="prefix_new_{{ $model->id }}" class="form-control" maxlength="10">
                                </div>
                            </div>

                            <div class="col-5">
                                <div class="form-group">
                                    <label for="nomor_new_{{ $model->id }}">Bilyet Number <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor" id="nomor_new_{{ $model->id }}" class="form-control" maxlength="30" required>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="form-group">
                                    <label for="amount_new_{{ $model->id }}">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount_new_{{ $model->id }}" class="form-control" step="0.01"
                                        value="{{ $model->bilyet_amount }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remarks_new_{{ $model->id }}">Remarks</label>
                            <textarea name="remarks" id="remarks_new_{{ $model->id }}" class="form-control" rows="2">Loan payment for installment #{{ $model->angsuran_ke }}</textarea>
                        </div>
                    </form>
                </div>

                {{-- Auto-Debit Form --}}
                <div id="form-auto-debit-{{ $model->id }}" class="payment-form" style="display: none;">
                    <form action="{{ route('accounting.loans.installments.mark_auto_debit', $model->id) }}" method="POST" id="auto-debit-form-{{ $model->id }}">
                        @csrf
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Auto-Debit Payment</strong><br>
                            This will mark the installment as paid via automatic bank debit. No bilyet will be created.
                        </div>

                        <div class="form-group">
                            <label for="account_id_auto_{{ $model->id }}">Bank Account <span class="text-danger">*</span></label>
                            <select name="account_id" id="account_id_auto_{{ $model->id }}" class="form-control select2bs4" required>
                                <option value="">-- select bank account --</option>
                                @foreach (\App\Models\Account::where('type', 'bank')->get() as $account)
                                    <option value="{{ $account->id }}"
                                        {{ $account->id == $model->account_id ? 'selected' : '' }}>
                                        {{ $account->account_number }} - {{ $account->account_name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select the bank account from which the auto-debit will be processed.</small>
                        </div>

                        <div class="form-group">
                            <label for="paid_date_auto_{{ $model->id }}">Paid Date</label>
                            <input type="date" name="paid_date" id="paid_date_auto_{{ $model->id }}" class="form-control">
                            <small class="text-muted">Leave empty to set later when payment is realized. You can update the payment date later.</small>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-footer float-left">
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-primary" id="submit-payment-{{ $model->id }}">
                    <i class="fas fa-save"></i> Save Payment Method
                </button>
            </div>
        </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>

<script>
    $(document).ready(function() {
        const installmentId = {{ $model->id }};
        
        // Handle payment method selection
        $('input[name="payment_method_' + installmentId + '"]').on('change', function() {
            const selectedMethod = $(this).val();
            
            // Hide all forms
            $('.payment-form').hide();
            
            // Show selected form
            if (selectedMethod === 'link_existing') {
                $('#form-link-existing-' + installmentId).show();
            } else if (selectedMethod === 'create_new') {
                $('#form-create-new-' + installmentId).show();
            } else if (selectedMethod === 'auto_debit') {
                $('#form-auto-debit-' + installmentId).show();
            }
        });

        // Handle bilyet selection change for link existing
        $('#bilyet_id_' + installmentId).on('change', function() {
            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#selected-nomor-' + installmentId).text(selectedOption.data('nomor'));
                $('#selected-type-' + installmentId).text(selectedOption.data('type').toUpperCase());
                $('#selected-account-' + installmentId).text(selectedOption.data('giro'));
                $('#selected-date-' + installmentId).text(selectedOption.data('date'));
                $('#bilyet-details-' + installmentId).show();
            } else {
                $('#bilyet-details-' + installmentId).hide();
            }
        });

        // Handle submit button
        $('#submit-payment-' + installmentId).on('click', function() {
            const selectedMethod = $('input[name="payment_method_' + installmentId + '"]:checked').val();
            
            if (selectedMethod === 'link_existing') {
                $('#link-existing-form-' + installmentId).submit();
            } else if (selectedMethod === 'create_new') {
                $('#create-new-form-' + installmentId).submit();
            } else if (selectedMethod === 'auto_debit') {
                $('#auto-debit-form-' + installmentId).submit();
            }
        });

        // Initialize Select2 when modal is shown
        $('#payment-method-' + installmentId).on('shown.bs.modal', function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
        });
    });
</script>

{{-- Modal Create SAP AP Invoice --}}
<div class="modal fade" id="create-sap-ap-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Create SAP AP Invoice</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('accounting.loans.installments.create_sap_ap_invoice', $model->id) }}"
                method="POST">
                @csrf

                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Create SAP AP Invoice</strong><br>
                        This will create an AP Invoice in SAP B1 for this installment.
                    </div>

                    <div class="form-group">
                        <label>Installment Details:</label>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Installment #:</dt>
                            <dd class="col-sm-7">{{ $model->angsuran_ke }}</dd>
                            <dt class="col-sm-5">Due Date:</dt>
                            <dd class="col-sm-7">{{ date('d-M-Y', strtotime($model->due_date)) }}</dd>
                            <dt class="col-sm-5">Amount:</dt>
                            <dd class="col-sm-7">IDR {{ number_format($model->bilyet_amount, 2) }}</dd>
                            <dt class="col-sm-5">Payment Method:</dt>
                            <dd class="col-sm-7">{{ $model->payment_method_label }}</dd>
                            <dt class="col-sm-5">Creditor:</dt>
                            <dd class="col-sm-7">{{ $model->loan->creditor->name ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="modal-footer float-left">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Create AP
                        Invoice</button>
                </div>
            </form>

        </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>

{{-- Modal Link SAP AP Invoice --}}
<div class="modal fade" id="link-sap-ap-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Link SAP AP Invoice</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('accounting.loans.installments.link_sap_ap_invoice', $model->id) }}"
                method="POST">
                @csrf

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i>
                        <strong>Link Existing SAP AP Invoice</strong><br>
                        Enter the SAP AP Invoice document numbers if the invoice was already created in SAP B1.
                    </div>

                    <div class="form-group">
                        <label>Installment Details:</label>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Installment #:</dt>
                            <dd class="col-sm-7">{{ $model->angsuran_ke }}</dd>
                            <dt class="col-sm-5">Amount:</dt>
                            <dd class="col-sm-7">IDR {{ number_format($model->bilyet_amount, 2) }}</dd>
                            @if ($model->sap_ap_doc_num)
                                <dt class="col-sm-5">Current AP DocNum:</dt>
                                <dd class="col-sm-7"><span
                                        class="badge badge-info">{{ $model->sap_ap_doc_num }}</span></dd>
                            @endif
                        </dl>
                    </div>

                    <div class="form-group">
                        <label for="sap_ap_doc_num">SAP AP Invoice Document Number <span
                                class="text-danger">*</span></label>
                        <input type="text" name="sap_ap_doc_num" class="form-control"
                            value="{{ $model->sap_ap_doc_num }}" required placeholder="e.g., 12345">
                    </div>

                    <div class="form-group">
                        <label for="sap_ap_doc_entry">SAP AP Invoice DocEntry <span
                                class="text-danger">*</span></label>
                        <input type="number" name="sap_ap_doc_entry" class="form-control"
                            value="{{ $model->sap_ap_doc_entry }}" required placeholder="e.g., 123456">
                    </div>
                </div>

                <div class="modal-footer float-left">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-link"></i> Link AP
                        Invoice</button>
                </div>
            </form>

        </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>
