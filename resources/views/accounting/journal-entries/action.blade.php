<div class="btn-group btn-group-sm">
    <a href="{{ route('accounting.journal-entries.show', $model->id) }}" class="btn btn-info" title="View">
        <i class="fas fa-eye"></i>
    </a>
    @if ($model->sap_submission_status !== 'success' && empty($model->sap_reversed_at))
        <a href="{{ route('accounting.journal-entries.edit', $model->id) }}" class="btn btn-warning" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
        <form action="{{ route('accounting.journal-entries.destroy', $model->id) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Delete this journal entry?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    @endif
    <a href="{{ route('accounting.journal-entries.print', $model->id) }}" class="btn btn-secondary" title="Print" target="_blank">
        <i class="fas fa-print"></i>
    </a>
</div>
