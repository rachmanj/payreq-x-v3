@php
    $paymentEditable = $paymentEditable ?? true;
    $selectedMethod = old('payment_method', $payreq->payment_method ?? 'cash');
    $selectedAccountId = old('transfer_account_id', $payreq->transfer_account_id ?? '');
@endphp

<div class="form-group" id="payment-method-section">
    <label>Metode Pembayaran</label>

    @if ($paymentEditable)
        <div class="d-flex flex-wrap gap-3 mb-2">
            <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="payment_method_cash" name="payment_method" value="cash"
                    class="custom-control-input payment-method-radio"
                    {{ $selectedMethod === 'cash' ? 'checked' : '' }}>
                <label class="custom-control-label" for="payment_method_cash">Cash</label>
            </div>
            <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="payment_method_transfer" name="payment_method" value="transfer"
                    class="custom-control-input payment-method-radio"
                    {{ $selectedMethod === 'transfer' ? 'checked' : '' }}>
                <label class="custom-control-label" for="payment_method_transfer">Transfer</label>
            </div>
        </div>
        @error('payment_method')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror

        <div id="transfer-account-block" style="{{ $selectedMethod === 'transfer' ? '' : 'display:none;' }}">
            <label for="transfer_account_id">Akun Transfer</label>
            <div class="d-flex flex-wrap align-items-start gap-2">
                <div class="flex-grow-1" style="min-width: 240px;">
                    <select name="transfer_account_id" id="transfer_account_id"
                        class="form-control select2bs4 @error('transfer_account_id') is-invalid @enderror"
                        data-placeholder="Pilih Akun Transfer" style="width: 100%;">
                        <option value=""></option>
                        @foreach ($transferAccounts as $account)
                            <option value="{{ $account->id }}"
                                {{ (string) $selectedAccountId === (string) $account->id ? 'selected' : '' }}>
                                {{ $account->displayLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('transfer_account_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="btn-open-transfer-modal"
                    data-toggle="modal" data-target="#transferAccountModal">
                    <i class="fas fa-plus"></i> Tambah Akun Transfer
                </button>
            </div>
        </div>
    @else
        <div class="form-control-plaintext">
            @if ($payreq->payment_method === 'transfer' && $payreq->transferAccount)
                <span class="badge badge-info">Transfer</span>
                <div class="small text-muted mt-1">
                    {{ $payreq->transferAccount->displayLabel }}
                </div>
            @elseif ($payreq->payment_method === 'cash')
                <span class="badge badge-secondary">Cash</span>
            @else
                -
            @endif
        </div>
    @endif
</div>

@if ($paymentEditable)
    <div class="modal fade" id="transferAccountModal" tabindex="-1" role="dialog"
        aria-labelledby="transferAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferAccountModalLabel">Tambah Akun Transfer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new_transfer_label">Label</label>
                        <input type="text" id="new_transfer_label" class="form-control"
                            placeholder="mis. Vendor A - Sertifikasi">
                    </div>
                    <div class="form-group">
                        <label for="new_transfer_bank_id">Bank</label>
                        <select id="new_transfer_bank_id" class="form-control select2bs4-modal"
                            data-placeholder="Pilih Bank" style="width: 100%;">
                            <option value=""></option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new_transfer_account_number">No. Rekening</label>
                        <input type="text" id="new_transfer_account_number" class="form-control">
                    </div>
                    <div class="form-group mb-0">
                        <label for="new_transfer_account_name">Atas Nama</label>
                        <input type="text" id="new_transfer_account_name" class="form-control">
                    </div>
                    <div id="transfer-account-modal-error" class="text-danger small mt-2" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btn-save-transfer-account">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
