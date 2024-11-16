<!-- Button to trigger modal -->
<button type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#updateModal-{{ $model->id }}"
    title="update bupot">
    <i class="fas fa-edit"></i>
</button>

<!-- Modal -->
<div class="modal fade" id="updateModal-{{ $model->id }}" tabindex="-1" role="dialog"
    aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Update Bukti Potong</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('accounting.wtax23.update', $model->id) }}"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bupot_no">Bupot No</label>
                        <input type="text" class="form-control" id="bupot_no" name="bupot_no" required>
                    </div>
                    <div class="form-group">
                        <label for="bupot_date">Bupot Date</label>
                        <input type="date" class="form-control" id="bupot_date" name="bupot_date" required>
                    </div>
                    <div class="form-group">
                        <label for="attachment-{{ $model->id }}">File Name</label>
                        <input type="file" class="form-control" id="attachment-{{ $model->id }}"
                            name="attachment">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
