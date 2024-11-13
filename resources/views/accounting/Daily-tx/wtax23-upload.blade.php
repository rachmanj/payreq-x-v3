<div class="modal fade" id="modal-upload">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"> Upload Daily WTax 23</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('accounting.daily-tx.uploadWtax23') }}" enctype="multipart/form-data" method="POST">
                @csrf
                <div class="modal-body">
                    <label>Pilih file excel</label>
                    <div class="form-group">
                        <input type="hidden" name="form_type" value="wtax23">
                        <input type="file" name='file_upload' required class="form-control">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"> Upload</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
