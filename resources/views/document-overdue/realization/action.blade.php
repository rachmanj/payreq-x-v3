<button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#extend-{{ $model->id }}">
    extend
</button>

<!-- Modal -->
<div class="modal fade" id="extend-{{ $model->id }}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Extend Due Date</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('document-overdue.realization.extend') }}" method="POST">
            @csrf
            <div class="modal-body">
                
                <input type="hidden" name="realization_id" value="{{ $model->id }}">

                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" value="{{ $model->payreq->remarks }}" class="form-control" readonly>
                </div>
                <div class="row">
                    <div class="form-group col-6">
                        <label>Current Due Date</label>
                        <input type="text" value="{{ date('d-M-Y', strtotime($model->due_date)) }}" class="form-control" readonly>
                    </div>
                    <div class="form-group col-6">
                        <label>Approved Date</label>
                        <input type="text" value="{{ date('d-M-Y', strtotime($model->approved_at)) }}" class="form-control" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Due Date</label>
                    <input type="date" name="new_due_date" class="form-control" value="{{ $model->due_date }}" >
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-sm btn-primary">Save changes</button>
            </div>
            </form>
        </div>
    </div>
</div>
