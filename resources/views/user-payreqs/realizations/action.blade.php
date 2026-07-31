<div class="vj-inline-actions">
    <a href="{{ route('user-payreqs.realizations.add_details', $model->id) }}"
        class="vj-action-item vj-action-item-xs vj-action-edit {{ $model->editable === 0 ? 'is-disabled' : '' }}">
        <i class="fas fa-edit"></i>
        <span>edit</span>
    </a>

    @if (!$model->realizationDetails->count() > 0 || $model->status === 'rejected')
        <form action="{{ route('user-payreqs.realizations.destroy', $model->id) }}" method="POST"
            class="vj-action-item-form">
            @csrf @method('DELETE')
            <button type="submit" class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-cancel"
                onclick="return confirm('Are you sure you want delete this record? This action will also DELETE its realization details!!')"
                {{ $model->deletable === 0 ? 'disabled' : '' }}>
                <i class="fas fa-trash"></i>
                <span>delete</span>
            </button>
        </form>
    @endif

    <a href="{{ route('user-payreqs.realizations.print', $model->id) }}"
        class="vj-action-item vj-action-item-xs vj-action-print {{ $model->printable ? '' : 'is-disabled' }}"
        target="_blank">
        <i class="fas fa-print"></i>
        <span>print</span>
    </a>
</div>
