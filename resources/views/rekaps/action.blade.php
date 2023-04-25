@can('delete_rekap')
<form action="{{ route('rekaps.destroy', $model->id) }}" method="POST">
    @csrf @method('DELETE')
    <button class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this record?')">delete</button>
</form>
@endcan