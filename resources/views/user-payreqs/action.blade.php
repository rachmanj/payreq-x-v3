<div class="vj-inline-actions">
    @if ($model->editable)
        @if ($model->type == 'advance')
            <a href="{{ route('user-payreqs.advance.edit', $model->id) }}"
                class="vj-action-item vj-action-item-xs vj-action-edit">
                <i class="fas fa-edit"></i>
                <span>edit</span>
            </a>
        @else
            <a href="{{ route('user-payreqs.reimburse.edit', $model->id) }}"
                class="vj-action-item vj-action-item-xs vj-action-edit">
                <i class="fas fa-edit"></i>
                <span>edit</span>
            </a>
        @endif
    @endif
    @if ($model->printable && $model->status !== 'split')
        <a href="{{ route('user-payreqs.print', $model->id) }}" class="vj-action-item vj-action-item-xs vj-action-print"
            target="_blank">
            <i class="fas fa-print"></i>
            <span>print</span>
        </a>
    @endif
    @if ($model->deletable)
        <form action="{{ route('user-payreqs.destroy', $model->id) }}" method="POST" class="vj-action-item-form">
            @csrf @method('PUT')
            @if ($model->type == 'advance')
                <input type="hidden" name="type" value="advance">
            @else
                <input type="hidden" name="type" value="reimburse">
            @endif
            <button type="submit" class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-cancel"
                onclick="return confirm('Are You sure You want to delete this record?')">
                <i class="fas fa-trash"></i>
                <span>delete</span>
            </button>
        </form>
    @endif
</div>
