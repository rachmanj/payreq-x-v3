<div class="btn-group" role="group">
    <a href="{{ route('user-payreqs.histories.show', $model->id) }}" class="btn btn-xs btn-success">
        <i class="fas fa-eye"></i> show
    </a>

    {{-- Print buttons for printable documents --}}
    @if ($model->type === 'reimburse' && $model->printable)
        <a href="{{ route('user-payreqs.print', $model->id) }}" class="btn btn-xs btn-info mx-1" target="_blank"
            title="Print Payment Request">
            <i class="fas fa-print"></i> print
        </a>
    @endif

    @if ($model->realization && $model->realization->printable)
        <a href="{{ route('user-payreqs.realizations.print', $model->realization->id) }}"
            class="btn btn-xs btn-info mx-1" target="_blank" title="Print Realization">
            <i class="fas fa-print"></i> print
        </a>
    @endif

    {{-- Delete button for canceled documents --}}
    @if ($model->status == 'canceled' && $model->user_id == auth()->user()->id)
        <form action="{{ route('user-payreqs.histories.destroy', $model->id) }}" class="d-inline" method="POST">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-xs btn-danger"
                onclick="return confirm('Are you sure to delete this record?')">
                <i class="fas fa-trash"></i> delete
            </button>
        </form>
    @endif
</div>
