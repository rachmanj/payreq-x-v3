<!-- Start of Selection -->
@if (is_null($model->received_date))
    <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#updateReceiveModal-{{ $model->id }}">
        Receive
    </button>
@endif
<!-- End of Selection -->

<!-- Modal -->
<div class="modal fade" id="updateReceiveModal-{{ $model->id }}" tabindex="-1" role="dialog"
    aria-labelledby="updateReceiveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateReceiveModalLabel">Update Receive Information</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('accounting.deliveries.receive_update', $model->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="receiveDate">Date of Receive</label>
                        <input type="date" class="form-control" id="receiveDate" name="receiveDate" required
                            value="{{ old('receiveDate', date('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label for="feedback">Feedback to Sender</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="3">{{ old('feedback') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
