<form action="{{ route('user-payreqs.histories.destroy', $model->id) }}" class="d-inline" method="POST">
    @csrf @method('DELETE')
    <a href="{{ route('user-payreqs.histories.show', $model->id) }}" class="btn btn-xs btn-success">show</a>
    @if ($model->status == 'canceled' && $model->user_id == auth()->user()->id)
    <button type="submit"  class="btn btn-xs btn-danger" onclick="return confirm('Are you sure to delete this record?')">delete</button>
    @endif
</form>

