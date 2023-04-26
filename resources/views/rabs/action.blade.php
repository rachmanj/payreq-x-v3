  {{-- <a href="{{ route('rabs.show', $model->id) }}" class="btn btn-xs btn-info">detail</a> --}}
  @if ($model->filename) <a href="{{ asset('document_upload/') . '/'. $model->filename }}" class='btn btn-xs btn-success' target=_blank>show RAB</a> @endif
  {{-- <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#buc-show-{{ $model->id }}">show</button> --}}
  @can('edit_rab')
  <a href="{{ route('rabs.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
  @endcan
  @can('update_rab_status')
  <button class="btn btn-xs btn-info" data-toggle="modal" data-target="#status-update-{{ $model->id }}">update status</button>
  @endcan
  <form action="{{ route('rabs.destroy', $model->id) }}" method="POST">
  @csrf @method('DELETE')
    @can('delete_rab')
    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure to delete this record?')" {{ $model->payreqs->count() > 0 ? 'disabled' : '' }}>delete</button>
    @endcan
  </form>

  <div class="modal fade" id="status-update-{{ $model->id }}">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">No: {{ $model->rab_no }} | {{ $model->description }} | Project: {{ $model->project_code }} |  IDR. {{ number_format($model->budget, 0) }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('rabs.update_status', $model->id) }}" method="POST">
          @csrf @method('PUT')
          <div class="modal-body">
            <div class="form-group">
              <label for="status">Change RAB status to: </label>
                <select name="status" id="status" class="form-control">
                 <option value="progress" {{ $model->status === "progress" ? "selected" : "" }}>Progress</option>
                 <option value="finish" {{ $model->status === "finish" ? "selected" : "" }}>Finish</option>
                 <option value="cancel" {{ $model->status === "cancel" ? "selected" : "" }}>Cancel</option>
                </select>
            </div>
          </div>      
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
          </div>
        </form>
      </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
  </div> <!-- /.modal -->