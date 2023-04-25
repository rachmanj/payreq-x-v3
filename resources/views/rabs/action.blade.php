<form action="{{ route('rabs.destroy', $model->id) }}" method="POST">
    @csrf @method('DELETE')
    <a href="{{ route('rabs.show', $model->id) }}" class="btn btn-xs btn-info">detail</a>
    @if ($model->filename) <a href="{{ asset('document_upload/') . '/'. $model->filename }}" class='btn btn-xs btn-success' target=_blank>show RAB</a> @endif
    {{-- <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#buc-show-{{ $model->id }}">show</button> --}}
    @hasanyrole('superadmin')
    <a href="{{ route('rabs.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure to delete this record?')">delete</button>
    @endhasanyrole
  </form>