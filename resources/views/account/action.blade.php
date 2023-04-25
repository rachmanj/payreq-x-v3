@hasanyrole('superadmin')
<button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#account-edit-{{ $model->id }}">edit</button>
@endhasanyrole

{{-- Modal edit --}}
<div class="modal fade" id="account-edit-{{ $model->id }}">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> Edit Account</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <div class="modal-body">

        <form action="{{ route('account.update', $model->id) }}" method="POST" id="form-edit">
          @csrf @method('PUT')
          <div class="form-group">
            <label for="name">Account Name</label>
            <input name="name" id="name" value="{{ $model->name }}" class="form-control @error('name') is-invalid @enderror" autocomplete="off">
          </div>

          <div class="form-group">
            <label for="account_no">Account No</label>
            <input name="account_no" id="account_no" value="{{ $model->account_no }}" class="form-control @error('account_no') is-invalid @enderror" autocomplete="off">
          </div>

          <div class="form-group">
            <label for="balance">Balance</label>
            <input type="text" value="{{ number_format($model->balance, 2) }}" class="form-control" readonly>
          </div>
        </form>

      </div> <!-- /.modal-body -->
      <div class="modal-footer float-left">
        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
        <button type="submit" class="btn btn-sm btn-primary" form="form-edit"><i class="fas fa-save"></i> Save</button>
      </div>
    
    </div> <!-- /.modal-content -->
  </div> <!-- /.modal-dialog -->
</div>