@php
    $canSubmitAp = auth()->user()->can('submit_sap_ap_invoice_installment');
    $canSubmitOp = auth()->user()->can('submit_sap_op_installment');
    $hasSplit = $model->principal_amount !== null && $model->interest_amount !== null;
@endphp

@hasanyrole('superadmin|admin|cashier|approver|approver_bo|cashier_bo')
    @if (!$model->paid_date && !$hasSplit)
        <button type="button" class="btn btn-xs btn-outline-secondary btn-split-installment"
            data-id="{{ $model->id }}"
            data-principal="{{ $model->principal_amount ?? '' }}"
            data-interest="{{ $model->interest_amount ?? '' }}"
            data-angsuran="{{ $model->angsuran_ke }}"
            title="Split Pokok/Bunga">
            <i class="fas fa-divide"></i> Split
        </button>
    @endif

    @if ($canSubmitAp && !$model->paid_date && $hasSplit && !$model->hasSapAp())
        <button type="button" class="btn btn-xs btn-primary btn-submit-ap"
            data-id="{{ $model->id }}"
            data-angsuran="{{ $model->angsuran_ke }}"
            data-amount="{{ number_format((float) $model->bilyet_amount, 0, ',', '.') }}"
            title="Submit AP ke SAP">
            <i class="fas fa-file-invoice-dollar"></i> Submit AP
        </button>
    @endif

    @if ($canSubmitOp && !$model->paid_date && $model->hasSapAp() && !$model->hasSapPayment())
        <button type="button" class="btn btn-xs btn-success btn-create-op"
            data-id="{{ $model->id }}"
            data-angsuran="{{ $model->angsuran_ke }}"
            title="Buat Outgoing Payment">
            <i class="fas fa-money-check-alt"></i> Buat OP
        </button>
    @endif

    @if (!$model->paid_date)
        <button type="button" class="btn btn-xs btn-primary" data-toggle="modal"
            data-target="#payment-method-{{ $model->id }}" title="Set Payment Method">
            <i class="fas fa-credit-card"></i> Payment
        </button>
    @endif

    @if (!$model->paid_date && in_array($model->payment_method, ['bilyet', 'auto_debit']) && !$model->sap_ap_doc_num)
        <button type="button" class="btn btn-xs btn-outline-primary" data-toggle="modal"
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

@include('accounting.loans.partials.installment_modals', ['model' => $model])
