<form action="{{ route('giros.detail.destroy', $model->id) }}" method="POST">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure to delete this record?')">delete</button>
</form>