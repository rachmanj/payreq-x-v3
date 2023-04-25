<form action="{{ route('giros.destroy', $model->id) }}" method="POST">
    @csrf @method('DELETE')
    <a href="{{ route('giros.detail.index', $model->id) }}" class="btn btn-xs btn-info">detail</a>
    @if ($model->filename) <a href="{{ asset('document_upload/') . '/'. $model->filename }}" class='btn btn-xs btn-success' target=_blank>show bilyet</a> @endif
    <a href="{{ route('giros.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
    @if ($model->giro_details()->count() > 0)
        <button type="button" class="btn btn-xs btn-danger" disabled>delete</button>
    @else
        <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure to delete this record?')">delete</button>
    @endif
</form>