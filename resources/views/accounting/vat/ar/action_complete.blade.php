<!-- Button to trigger modal -->
<button type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#updateModal-{{ $model->id }}"
    title="update bupot">
    <i class="fas fa-edit"></i>
</button>
@if ($model->attachment)
    <a href="{{ $model->attachment }}" class="btn btn-primary btn-xs" target="_blank" title="show bupot"><i
            class="fas fa-file-pdf"></i></a>
@endif

@if($model->sap_ar_doc_num)
    <span class="badge badge-success">
        <i class="fas fa-check"></i> Submitted
    </span>
@endif

<!-- Modal -->
<div class="modal fade" id="updateModal-{{ $model->id }}" tabindex="-1" role="dialog"
    aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Update Faktur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('accounting.vat.sales_update', $model->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="doc_num">SAP Docnum</label>
                        <input type="text" class="form-control" id="doc_num" name="doc_num"
                            value="{{ old('doc_num', $model->doc_num) }}">
                    </div>
                    <div class="form-group">
                        <label for="posting_date">Posting Date</label>
                        <input type="date" class="form-control" id="posting_date" name="posting_date"
                            value="{{ old('posting_date', $model->posting_date) }}">
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
