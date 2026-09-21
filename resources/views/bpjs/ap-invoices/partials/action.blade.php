@php
    $canCancel = auth()->user()?->can('cancel_sap_ap_invoice_bpjs') ?? false;
    $showCancel = $canCancel
        && $invoice->status === \App\Models\BpjsApInvoice::STATUS_POSTED
        && (float) $invoice->paid_amount <= 0;
    $showRetryCancelJe = $canCancel
        && $invoice->status === \App\Models\BpjsApInvoice::STATUS_CANCELLED
        && $invoice->je_status === \App\Models\BpjsApInvoice::JE_STATUS_FAILED;
    $showPrintOp = (float) $invoice->paid_amount > 0;
    $showRepostSap = ($canSubmit ?? false)
        && ! empty($invoice->sap_doc_entry)
        && $invoice->sap_cancelled === true
        && $invoice->status !== \App\Models\BpjsApInvoice::STATUS_CANCELLED
        && (float) $invoice->paid_amount <= 0;
    $hasActions = ($canSubmit ?? false) || $showCancel || $showRetryCancelJe || $showPrintOp || $showRepostSap;
@endphp

@if ($hasActions)
    <div class="btn-group btn-group-sm" role="group">
        @if ($canSubmit)
            @if (in_array($invoice->status, ['pending', 'failed'], true))
                <a href="{{ route('bpjs-ap-invoices.preview', $invoice) }}"
                    class="vj-action-item vj-action-item-xs vj-action-show" title="Preview">
                    <i class="fas fa-eye"></i>
                </a>
            @endif

            @if ($invoice->status === 'failed')
                <form method="POST" action="{{ route('bpjs-ap-invoices.retry', $invoice) }}" class="d-inline bpjs-retry-form">
                    @csrf
                    <button type="submit" class="vj-action-item vj-action-item-xs vj-action-submit bpjs-retry-btn"
                        title="Retry Submit SAP">
                        <i class="fas fa-redo"></i>
                    </button>
                </form>
            @endif

            @if (
                $invoice->jenis === \App\Models\BpjsApInvoice::JENIS_KETENAGAKERJAAN
                && $invoice->je_status === \App\Models\BpjsApInvoice::JE_STATUS_FAILED
                && $invoice->status !== \App\Models\BpjsApInvoice::STATUS_CANCELLED
            )
                <form method="POST" action="{{ route('bpjs-ap-invoices.retry-je', $invoice) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="vj-action-item vj-action-item-xs vj-action-submit bpjs-retry-je-btn"
                        title="Retry Jurnal Akrual">
                        <i class="fas fa-book-medical"></i>
                    </button>
                </form>
            @endif

            @if ($invoice->status === 'posted' || $invoice->status === 'paid')
                <a href="{{ route('bpjs-ap-invoices.preview', $invoice) }}"
                    class="vj-action-item vj-action-item-xs vj-action-show" title="Detail">
                    <i class="fas fa-info-circle"></i>
                </a>
            @endif

            @if ($showRepostSap)
                <form method="POST" action="{{ route('bpjs-ap-invoices.repost-sap', $invoice) }}"
                    class="d-inline bpjs-repost-sap-form">
                    @csrf
                    <button type="submit" class="vj-action-item vj-action-item-xs vj-action-submit bpjs-repost-sap-btn"
                        title="Post ulang AP Invoice ke SAP">
                        <i class="fas fa-file-import"></i>
                    </button>
                </form>
            @endif
        @endif

        @if ($showCancel)
            <button type="button" class="vj-action-item vj-action-item-xs vj-action-cancel bpjs-cancel-btn"
                title="Batalkan"
                data-cancel-url="{{ route('bpjs-ap-invoices.cancel', $invoice) }}"
                data-jenis="{{ $invoice->jenis }}"
                data-invoice-label="{{ $invoice->invoiceNumber() }}">
                <i class="fas fa-ban"></i>
            </button>
        @endif

        @if ($showRetryCancelJe)
            <form method="POST" action="{{ route('bpjs-ap-invoices.cancel-je', $invoice) }}"
                class="d-inline bpjs-cancel-je-form">
                @csrf
                <button type="submit" class="vj-action-item vj-action-item-xs vj-action-submit bpjs-cancel-je-btn"
                    title="Retry Reversal JE">
                    <i class="fas fa-redo"></i>
                </button>
            </form>
        @endif

        @if ($showPrintOp)
            <a href="{{ route('bpjs-ap-invoices.print-op', $invoice) }}"
                class="vj-action-item vj-action-item-xs vj-action-print" target="_blank" title="Print OP">
                <i class="fas fa-print"></i>
            </a>
        @endif
    </div>
@endif
