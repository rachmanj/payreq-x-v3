<button type="submit" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#periode-model-{{ $model->id }}" >edit</button>

{{-- Modal edit --}}
<div class="modal fade" id="periode-model-{{ $model->id }}">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Update Periode Anggaran</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('reports.periode-anggaran.update', $model->id) }}" method="POST">
          @csrf @method('PUT')
        <div class="modal-body">
  
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="periode">Periode</label>
                        <input type="month" name="periode" id="periode" class="form-control" value="{{ date('Y-m', strtotime($model->periode)) }}">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="project">Project</label>
                        <select name="project" class="form-control">
                            @foreach (App\Models\Project::orderBy('code', 'asc')->get() as $project)
                                <option value="{{ $project->code }}" {{ $model->project === $project->code ? 'selected' : '' }}>{{ $project->code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="is_active">Is Active</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" value="yes" name="is_active" {{ $model->is_active === 1 ? 'checked' : ''}} >
                            <label class="form-check-label">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_active" value="no" {{ $model->is_active === 0 ? 'checked' : ''}}>
                            <label class="form-check-label">No</label>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <label for="periode_type">Type</label>
                    <div class="form-group">
                        <div class="form-check d-inline mr-4">
                            <input class="form-check-input" type="radio" name="periode_type" value="anggaran"  {{ $model->periode_type === 'anggaran' ? 'checked' : '' }}>
                            <label class="form-check-label">Anggaran</label>
                        </div>
                        <div class="form-check d-inline mr-4">
                            <input class="form-check-input" type="radio" name="periode_type" value="ofr" {{ $model->periode_type === 'ofr' ? 'checked' : '' }}>
                            <label class="form-check-label">OFR</label>
                        </div>
                    </div>
                </div>
              </div>
  
              <div class="form-group">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" class="form-control" value="{{ $model->description }}">
            </div>
  
        </div> <!-- /.modal-body -->
        <div class="modal-footer float-left">
          <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
          <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
        </div>
      </form>
      </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>