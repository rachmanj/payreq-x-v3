<form id="delete-form-{{ $id }}" action="{{ route('document-number.destroy', $id) }}" method="POST">
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
</form>