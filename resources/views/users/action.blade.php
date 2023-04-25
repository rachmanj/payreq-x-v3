


{{-- <button class="btn btn-xs btn-warning" data-toggle="modal" data-target="#edit-user-{{ $model->id }}">edit</button> --}}

@if ($model->is_active == 1)
  <form action="{{ route('users.deactivate', $model->id) }}" method="POST">
    @csrf @method('PUT')
      <button onclick="return confirm('Are you sure?')" type="submit" class="btn btn-xs btn-warning">deactivate
      </button>
  </form>
@endif

@if ($model->is_active == 0)
  <form action="{{ route('users.activate', $model->id) }}" method="POST">
    @csrf @method('PUT')
      <button onclick="return confirm('Are you sure?')" type="submit" class="btn btn-xs btn-warning">
      activate
      </button>
  </form>
@endif

@can('edit_user')
<a href="{{ route('users.edit', $model->id) }}" class="btn btn-xs btn-info">edit</a>
@endcan

<form action="{{ route('users.destroy', $model->id) }}" method="POST">
  @csrf @method('DELETE')
  @can('delete_user')
    <button class="btn btn-xs btn-danger" type="submit" onclick="return confirm('Are You sure You want to delete this user?')">delete</button>
  @endcan
</form>


{{-- <div class="modal fade" id="edit-user-{{ $model->id }}">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('users.update', $model->id) }}" method="POST">
          @csrf @method('PUT')
          <div class="modal-body">
            <div class="form-group">
              <label for="name">Name</label>
              <input type="text" name="name" class="form-control" value="{{ $model->name }}">
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" value="{{ $model->username }}" disabled>
              </div>
            <div class="form-group">
              <label for="password">Password <small>(biarkan kosong jika tidak berubah)</small></label>
              <input type="password" name="password" class="form-control">
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirm Password <small>(biarkan kosong jika password tidak berubah)</small></label>
                <input type="password_confirmation" name="password_confirmation" class="form-control">
              </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
          </div>
        </form>
      </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div> <!-- /.modal --> --}}