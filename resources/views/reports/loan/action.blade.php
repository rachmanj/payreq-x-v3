@hasanyrole('superadmin|admin|cashier')
<button type="submit" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#installment-edit-{{ $model->id }}" >edit</button>
@endhasanyrole

{{-- Modal edit --}}
<div class="modal fade" id="installment-edit-{{ $model->id }}">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
  
        <div class="modal-header">
          <h4 class="modal-title"> Edit Installment / Paid date</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <form action="{{ route('reports.loan.update') }}" method="POST">
          @csrf 
  
          <div class="modal-body">
  
            <input type="hidden" name="installment_id" value="{{ $model->id }}">
            <input type="hidden" name="form_type" value="reports">
            <div class="row">
              <div class="col-12">
                <div class="form-group">
                  <label for="paid_date">Paid Date</label>
                  <input type="date" name="paid_date" id="paid_date" value="{{ $model->paid_date }}" class="form-control">
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