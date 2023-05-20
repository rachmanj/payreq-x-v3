
    {{-- button call modal to update --}}
    <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#approvals-update-{{ $model->id }}">update</button>
    
    {{-- modal update --}}
    <div class="modal fade" id="approvals-update-{{ $model->id }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Payreq Approval</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <form action="{{ route('approvals.plan.update', $model->id) }}" method="POST">
                @csrf @method('PUT')
                    <div class="modal-body">
                        
                        <div class="row">
                            <div class="col-12">
                                <dl>
                                    <dt>Requestor | Payreq No | Created at | Amount</dt>
                                    <dd>{{ $model->payreq->requestor->name }} | {{ $model->payreq->payreq_no }} | {{ $model->payreq->created_at->addHours(8)->format('d-M-Y H:i:s') }} | IDR {{ number_format($model->payreq->amount, 2) }}</dd>
                                    <dt>Remarks</dt>
                                    <dd>{{ $model->payreq->remarks }}</dd>
                                </dl>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="status">Approval Status</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">-- change status --</option>
                                        <option value="approved">Approved</option>
                                        <option value="reject">Reject</option>
                                        <option value="revise">Revise</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div id="remarks" class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea name="remarks" id="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
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

