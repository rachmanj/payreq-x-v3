@if ($model->user_id == Auth::user()->id)
    <a href="{{ route('accounting.payreqs.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
    <a href="{{ route('accounting.payreqs.destroy', $model->id) }}" class="btn btn-xs btn-danger">delete</a>
@endif
