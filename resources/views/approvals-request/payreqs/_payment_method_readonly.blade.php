@php
    $hasAllocationTransferRows = $payreq->anggaranAllocations->contains(
        fn ($allocation) => filled($allocation->transfer_account_id)
    );
    $hasMultiTransferDestinations = $payreq->transferDestinations->isNotEmpty();

    if (! isset($singleTransferAccount)) {
        $singleTransferAccount = filled($payreq->transfer_account_id)
            ? ($payreq->relationLoaded('transferAccount')
                ? $payreq->transferAccount
                : \App\Models\TransferAccount::query()->with('bank')->find($payreq->transfer_account_id))
            : null;
    }
@endphp

<div class="form-group mb-3" id="payment-method-readonly">
    <label>Metode Pembayaran</label>
    <div class="vj-inline-actions mb-2">
        @if (($payreq->payment_method ?? 'cash') === 'cash')
            <span class="vj-chip vj-chip-neutral">
                <i class="fas fa-money-bill-wave"></i> Cash
            </span>
        @elseif ($payreq->payment_method === 'transfer')
            <span class="vj-chip vj-chip-info">
                <i class="fas fa-university"></i> Transfer
            </span>
        @else
            <span class="vj-chip vj-chip-neutral">-</span>
        @endif
    </div>
    @if ($payreq->payment_method === 'transfer')
        @if (! $hasMultiTransferDestinations && ! $hasAllocationTransferRows)
            @if ($singleTransferAccount)
                <div id="single-transfer-destination-readonly" class="border rounded p-2 bg-light">
                    <div class="font-weight-bold small text-uppercase text-muted mb-2">Rekening Tujuan</div>
                    <div class="small">
                        <div>{{ $singleTransferAccount->label }}</div>
                        <div>{{ $singleTransferAccount->account_number }}</div>
                        <div>{{ $singleTransferAccount->account_name }}</div>
                        <div>{{ $singleTransferAccount->bank?->name ?? 'n/a' }}</div>
                    </div>
                </div>
            @else
                <p class="small text-muted mb-0" id="transfer-destination-missing-notice">
                    Rekening tujuan belum tercatat.
                </p>
            @endif
        @endif
    @endif
</div>

@if ($hasMultiTransferDestinations || $hasAllocationTransferRows)
    @php
        $paymentEditable = false;
        $transferDestinations = $payreq->transferDestinations;
    @endphp
    @include('user-payreqs.partials.transfer-destinations')
@endif
