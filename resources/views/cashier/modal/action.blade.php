@if($model->receive_amount == null)
    <button class="btn btn-xs btn-success" data-toggle="modal" data-target="#receive-modal-{{ $model->id }}">receive</button>
@endif

{{-- Modal create --}}
<div class="modal fade" id="receive-modal-{{ $model->id }}">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"> Terima Modal Cashier</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('cashier.modal.receive', $model->id) }}" method="POST">
          @csrf @method('PUT')
        <div class="modal-body">
  
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="text" name="date" id="date" class="form-control" value="{{ date('d M Y', strtotime($model->date)) }}" disabled>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <input type="text" name="type" id="type" class="form-control" value="{{ $model->type ? ($model->type == 'begin' ? 'BOD' : 'EOD') : '-' }}" disabled>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="submit_amount">Jumlah diserahkan</label>
                        <input name="submit_amount" id="submit_amount" class="form-control text-right" value="{{ 'IDR ' . number_format($model->submit_amount, 0) }}" disabled>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="receive_amount">Jumlah diterima</label>
                        <input name="receive_amount" id="receive_amount" class="form-control @error('receive_amount') is-invalid @enderror">
                        @error('receive_amount')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
            </div>
  
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label for="remarks">Your Remarks</label>
                        <input type="text" name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror">
                        @error('remarks')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Head Cashier Remarks</label>
                        <input type="text" class="form-control" value="{{ $model->submitter_remarks }}" disabled>
                    </div>
                </div>
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