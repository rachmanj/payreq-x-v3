<div class="vj-inline-actions">
    @if (auth()->user()->id == $model->created_by && $model->editable === 1)
        <a href="{{ route('user-payreqs.anggarans.edit', $model->id) }}"
            class="vj-action-item vj-action-item-xs vj-action-edit">
            <i class="fas fa-edit"></i>
            <span>edit</span>
        </a>
    @endif
    @if ($model->filename)
        <a href="{{ asset('file_upload/') . '/' . $model->filename }}"
            class="vj-action-item vj-action-item-xs vj-action-export" target="_blank">
            <i class="fas fa-file-alt"></i>
            <span>show</span>
        </a>
    @endif
</div>
