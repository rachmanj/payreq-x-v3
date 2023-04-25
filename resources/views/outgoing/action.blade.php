<form action="{{ route('outgoing.auto_update', $model->id) }}" method="POST" id="auto-update">
    @csrf @method('PUT')
  </form>
  <a href="{{ route('outgoing.split', $model->id) }}" class="btn btn-xs btn-info">split</a>
  <button class="btn btn-xs btn-warning {{ $model->splits->count() > 0 ? 'disabled' : '' }}" data-toggle="modal" data-target="#outgoing-update-{{ $model->id }}">edit</button>
  <button type="submit" class="btn btn-xs btn-success {{ $model->splits->count() > 0 ? 'disabled' : '' }}" form="auto-update">auto</button>
  
  <div class="modal fade" id="outgoing-update-{{ $model->id }}">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">No: {{ $model->payreq_num }} | {{ $model->employee->name }}  | IDR. {{ number_format($model->payreq_idr, 0) }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('outgoing.update', $model->id) }}" method="POST">
          @csrf @method('PUT')
          <div class="modal-body">
            <div class="form-group">
              {{-- <label for="account_no">Account No <small>(optional)</small></label>
              <input type="text" name="account_no" class="form-control"> --}}

              <label for="account_id">Account No <small>(optional)</small></label>
                <select name="account_id" id="account_id" class="form-control">
                  <option value="">-- select account --</option>
                  @foreach (\App\Models\Account::orderBy('account_no', 'asc')->get() as $account)
                    <option value="{{ $account->id }}">{{ $account->account_no }}</option>
                  @endforeach
                </select>
            </div>
            <div class="form-group">
              <label for="outgoing_date">Outgoing Date <small>(biarkan kosong jika tanggal hari ini)</small></label>
              <input type="date" name="outgoing_date" class="form-control">
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