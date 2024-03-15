@hasanyrole('superadmin')
<form action="{{ route('accounting.loans.installments.destroy', $model->id) }}" method="POST">
  @csrf @method('DELETE')
  <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this record?')">delete</button>
</form>
@endhasanyrole

@hasanyrole('superadmin|admin|cashier')
<button type="submit" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#installment-edit-{{ $model->id }}" >edit</button>
@endhasanyrole

{{-- Modal edit --}}
<div class="modal fade" id="installment-edit-{{ $model->id }}">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
  
        <div class="modal-header">
          <h4 class="modal-title"> Edit Installment</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <form action="{{ route('accounting.loans.installments.update') }}" method="POST">
          @csrf 
  
          <div class="modal-body">
  
            <input type="hidden" name="installment_id" value="{{ $model->id }}">
            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label for="due_date">Due Date</label>
                  <input type="date" name="due_date" id="due_date" value="{{ $model->due_date }}" class="form-control">
                </div>
              </div>

              <div class="col-6">
                <div class="form-group">
                  <label for="paid_date">Paid Date</label>
                  <input type="date" name="paid_date" id="paid_date" value="{{ $model->paid_date }}" class="form-control">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label for="bilyet_no">Bilyet No</label>
                  <input type="text" name="bilyet_no" id="bilyet_no" value="{{ $model->bilyet_no }}" class="form-control">
                </div>
              </div>

              <div class="col-6">
                <div class="form-group">
                  <label for="bilyet_amount">Bilyet Amount</label>
                  <input type="text" name="bilyet_amount" id="bilyet_amount" value="{{ $model->bilyet_amount }}" class="form-control">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label for="account_id">Account No</label>
                  <select name="account_id" id="account_id" class="form-control select2bs4">
                    <option value="">-- select account --</option>
                    @foreach (\App\Models\Account::where('type', 'bank')->get() as $account)
                    <option value="{{ $account->id }}" {{ $account->id == $model->account_id ? "selected" : "" }}>{{ $account->account_number }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-6">
                {{--  --}}
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