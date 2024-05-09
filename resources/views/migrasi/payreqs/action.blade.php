<form action="{{ route('cashier.migrasi.payreqs.destroy', $model->id) }}" method="POST">
  <a href="{{ route('cashier.migrasi.payreqs.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
  @csrf
  <input type="hidden" name="payreq_id" value="{{ $model->id }}">
  {{-- <input type="hidden" name="payreq_migrasi_id" value="{{ $model->PayreqMigrasi->id }}"> --}}
  
  @hasrole('superadmin')
  <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#payreq-update_no-{{ $model->id }}">edit no</button>
  @endhasrole
  
  <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to pay this payreq?')">delete</button>
</form>  


<div class="modal fade" id="payreq-update_no-{{ $model->id }}">
  <div class="modal-dialog modal-md">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title"> Edit Payreq No</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
      <form action="{{ route('cashier.migrasi.payreqs.update_no', $model->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="modal-body">

          <div class="row">
            <div class="col-4">
              <div class="form-group">
                <label for="payreq_no">Payreq No</label>
                <input name="payreq_no" id="payreq_no" value="{{ old('payreq_no', $model->nomor) }}" class="form-control" autocomplete="off" autofocus>
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