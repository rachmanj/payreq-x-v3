<button class="btn btn-xs btn-warning" data-toggle="modal" data-target="#category-update-{{ $model->id }}">edit</button>

<div class="modal fade" id="category-update-{{ $model->id }}">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Category</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('adv-category.update', $model->id) }}" method="POST">
          @csrf @method('PUT')
          <div class="modal-body">
            <div class="form-group">
              <label for="code">Category Code</label>
              <input type="text" name="code" class="form-control" value="{{ $model->code }}">
            </div>
            <div class="form-group">
              <label for="description">Description <small>(optional)</small></label>
              <input type="text" name="description" class="form-control" value="{{ $model->description }}">
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
          </div>
        </form>
      </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
  </div> <!-- /.modal -->