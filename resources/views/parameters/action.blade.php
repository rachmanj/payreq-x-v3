  <button class="btn btn-xs btn-warning" data-toggle="modal" data-target="#parameter-update-{{ $model->id }}">edit</button>
  
  <div class="modal fade" id="parameter-update-{{ $model->id }}">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"> Edit Parameter</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('parameters.update', $model->id) }}" method="POST">
          @csrf @method('PUT')
        <div class="modal-body">
          <div class="form-group">
            <label for="name1">Param1</label>
            <input type="text" name='name1' value={{ old('name1', $model->name1) }} class="form-control @error('name1') is-invalid @enderror">
            @error('name1')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>    
          <div class="form-group">
            <label for="name2">Param2</label>
            <input type="text" name='name2' value={{ old('name2', $model->name2) }} class="form-control">
          </div>       
          <div class="form-group">
            <label for="param_value">Value</label>
            <input type="text" name='param_value' value={{ old('param_value', $model->param_value) }} class="form-control">
          </div>
        </div>
        <div class="modal-footer float-left">
          <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
          <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
        </div>
      </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>