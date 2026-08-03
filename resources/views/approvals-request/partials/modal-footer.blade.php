<div class="modal-footer justify-content-between">
    <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal">
        <i class="fas fa-times"></i>
        <span>{{ $closeLabel ?? 'Close' }}</span>
    </button>
    <button type="{{ $submitType ?? 'submit' }}" class="vj-btn vj-btn-primary" @if (!empty($submitId)) id="{{ $submitId }}" @endif>
        <i class="fas {{ $submitIcon ?? 'fa-save' }}"></i>
        <span>{{ $submitLabel ?? 'Save' }}</span>
    </button>
</div>
