<button class="btn btn-xs btn-warning" data-toggle="modal" data-target="#realization-edit-{{ $model->id }}">update</button>

<div class="modal fade" id="realization-edit-{{ $model->id }}">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">No: {{ $model->payreq_num }} | {{ $model->employee->name }}  | IDR. {{ number_format($model->payreq_idr, 0) }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('realization.update', $model->id) }}" method="POST" id="form-{{ $model->id }}">
        @csrf @method('PUT')
        <div class="modal-body">
          <div class="form-group">
            <label for="realization_num">Realization No</label>
            <input type="text" name="realization_num" class="form-control">
          </div>
          <div class="form-group">
            <label for="realization_amount">Amount</label>
            <input type="text" name="realization_amount" value="{{ old('realization_amount', $model->payreq_idr) }}" class="form-control">
          </div>
          <div class="form-group">
            <label for="realization_date">Realization Date <small>(kosong = hari ini)</small></label>
            <input type="date" name="realization_date" class="form-control">
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-sm btn-primary" form="form-{{ $model->id }}"><i class="fas fa-save"></i> Save</button>
        </div>
      </form>
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div> <!-- /.modal -->