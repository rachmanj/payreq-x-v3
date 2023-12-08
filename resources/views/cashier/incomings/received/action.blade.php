{{-- button call modal to update --}}
<button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#receive-incoming-{{ $model->id }}">edit</button>

{{-- modal receive --}}
<div class="modal fade" id="receive-incoming-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Receive Date</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="{{ route('cashier.incomings.received.edit_received_date', $model->id) }}" method="POST">
                @csrf @method('PUT')

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="hidden" name="incoming_id" value="{{ $model->id }}">
                                <input type="text" name="amount" id="amount" class="form-control" value="IDR {{ number_format($model->amount, 2) }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="receive_date">Receive Date</label>
                                <input type="date" name="receive_date" id="receive_date" class="form-control" value="{{ $model->receive_date }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
