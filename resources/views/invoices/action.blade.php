<button class="btn btn-xs btn-warning" data-toggle="modal" data-target="#invoices-payment-{{ $model->id }}">pay</button>
  
  <div class="modal fade" id="invoices-payment-{{ $model->id }}">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">No: {{ $model->nomor_invoice }} | {{ $model->vendor_name }} | IDR. {{ number_format($model->amount, 2) }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('invoices.paid', $model->id) }}" method="POST">
          @csrf @method('PUT')
          <div class="modal-body">
            <div class="form-group">
              <label for="payment_date">Payment Date <small>(biarkan kosong jika tanggal hari ini)</small></label>
              <input type="date" name="payment_date" class="form-control">
            </div>
            <div class="form-group">
              <label for="account_id">Account No.</label>
              <select name="account_id" class="form-control">
                  <option value="">-- not PC transaction --</option>
                @foreach (\App\Models\Account::orderBy('account_no', 'asc')->get() as $account)
                    <option value="{{ $account->id }}">{{ $account->account_no . ' - ' . $account->name }}</option>
                  @endforeach
              </select>
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