@hasanyrole('superadmin|admin|acc_cashier')
<button type="button" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#transaksi-destroy-{{ $model->id }}">delete</button>
@endhasanyrole


<!-- modal Delete -->
<div class="modal fade" id="transaksi-destroy-{{ $model->id }}">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">WARNING !!!</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>This action will also UPDATE balance of the account</p>
        <p>Are You sure??</p>
      </div>
      <div class="modal-footer justify-content-between">
        <form action="{{ route('transaksi.destroy', $model->id) }}" method="POST">
          @csrf @method('DELETE')
          <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-sm btn-danger">DELETE</button>
        </form>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal --> 