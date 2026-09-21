<div class="vj-inline-actions">
    <a href="{{ route('accounting.journal-entries.show', $model->id) }}" class="vj-action-item vj-action-item-xs vj-action-export" title="View">
        <i class="fas fa-eye"></i>
    </a>
    @if ($model->sap_submission_status !== 'success' && empty($model->sap_reversed_at))
        <a href="{{ route('accounting.journal-entries.edit', $model->id) }}" class="vj-action-item vj-action-item-xs vj-action-edit" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
        <form action="{{ route('accounting.journal-entries.destroy', $model->id) }}" method="POST" class="vj-action-item-form"
            onsubmit="return confirm('Delete this journal entry?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="vj-action-item vj-action-item-xs vj-action-cancel" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    @endif
    <a href="{{ route('accounting.journal-entries.print', $model->id) }}" class="vj-action-item vj-action-item-xs vj-action-print" title="Print" target="_blank">
        <i class="fas fa-print"></i>
    </a>
</div>
