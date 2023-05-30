<button type="submit" class="btn btn-xs btn-success" form="outgoings-store" data-toggle="modal" data-target="#outgoing-store-{{ $model->id }}" >Pay</button>
<a href="{{ route('outgoings.quick', $model->id) }}" class="btn btn-xs btn-success">quick</a>

{{-- MODAL PAYMENT --}}
<div class="modal fade" id="outgoing-store-{{ $model->id }}">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">No: {{ $model->payreq_no }} | {{ $model->requestor->name }}  | IDR. {{ number_format($model->amount, 0) }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('outgoings.store') }}" method="POST" id="outgoings-store">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            {{-- <label for="account_no">Account No <small>(optional)</small></label>
            <input type="text" name="account_no" class="form-control"> --}}

            <label for="account_id">Account No <small>(optional)</small></label>
              <select name="account_id" id="account_id" class="form-control">
                <option value="">-- select account --</option>
                {{-- @foreach (\App\Models\Account::orderBy('account_no', 'asc')->get() as $account)
                  <option value="{{ $account->id }}">{{ $account->account_no }}</option>
                @endforeach --}}
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