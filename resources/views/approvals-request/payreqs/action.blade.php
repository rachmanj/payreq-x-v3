@if ($model->payreq->type === 'reimburse')
    <a href="{{ route('approvals.request.payreqs.show', $model->id) }}" class="btn btn-xs btn-warning">detail</a>
@else
    {{-- button call modal to update --}}
    <button type="button" class="btn btn-xs btn-warning" data-toggle="modal"
        data-target="#approvals-update-{{ $model->id }}">detail</button>

    {{-- modal update --}}
    <div class="modal fade" id="approvals-update-{{ $model->id }}">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Approval for Payreq No. {{ $model->payreq->nomor }}</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <form action="{{ route('approvals.plan.update', $model->id) }}" method="POST" class="approval-form">
                    @csrf @method('PUT')
                    <input type="hidden" name="document_type" value="payreq">
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="requestor">Requestor</label>
                                    <input type="text" name="requestor" id="requestor" class="form-control"
                                        value="{{ $model->payreq->requestor->name }}" readonly>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="amount">Amount</label>
                                    <input type="text" name="amount" id="amount" class="form-control"
                                        value="IDR {{ number_format($model->payreq->amount, 2) }}" readonly>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="submit_at">Submit at</label>
                                    <input type="text" name="submit_at" id="submit_at" class="form-control"
                                        value="{{ Carbon\Carbon::parse($model->payreq->submit_at)->addHours(8)->format('d-M-Y H:i:s') }}"
                                        readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea name="remarks" id="remarks" class="form-control" rows="2" readonly>{{ $model->payreq->remarks }}</textarea>
                                </div>
                            </div>
                        </div>

                        @if ($model->payreq->lot_no != null)
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>LOT No</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control"
                                                value="{{ $model->payreq->lot_no }}" readonly>
                                            @if ($model->payreq->lot_no)
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-info" id="view_lot_detail">
                                                        <strong>LOT Detail</strong>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($model->payreq->rab_id != null)
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>RAB</label>
                                        <input type="text" class="form-control"
                                            value="{{ 'No. ' . $model->payreq->anggaran->nomor . ' | ' . $model->payreq->anggaran->rab_project . ' | ' . $model->payreq->anggaran->description }}"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="status">Approval Status</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">-- change status --</option>
                                        <option value="1">Approved</option>
                                        <option value="2">Revise</option>
                                        <option value="3">Reject</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div id="remarks-container" class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea name="remarks" id="approval-remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i>
                            Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif
