@if ($canSubmit)
    <div class="btn-group btn-group-sm" role="group">
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

        @if ($invoice->status === 'posted' || $invoice->status === 'paid')
            <a href="{{ route('bpjs-ap-invoices.preview', $invoice) }}"
                class="vj-action-item vj-action-item-xs vj-action-show" title="Detail">
                <i class="fas fa-info-circle"></i>
            </a>
        @endif
    </div>
@endif
