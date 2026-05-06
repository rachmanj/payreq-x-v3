@foreach ($payreqs as $payreq)
    @continue(! in_array($payreq->project ?? '', \App\Models\OverdueExtension::eligibleProjects(), true))

    @if (($payreq->overdue_extensions_pending_count ?? 0) === 0)
        <div class="modal fade" id="extension-modal-payreq-{{ $payreq->id }}" tabindex="-1" role="dialog"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('document-overdue.extensions.store') }}" method="POST" novalidate>
                        @csrf
                        <input type="hidden" name="document_type" value="payreq">
                        <input type="hidden" name="document_id" value="{{ $payreq->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title">Request overdue extension — {{ $payreq->nomor }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-2">{{ $payreq->overdue_extensions_total_count ?? 0 }}
                                request(s) submitted,
                                {{ $payreq->overdue_extensions_approved_count ?? 0 }} approved.</p>
                            <div class="form-group">
                                <label>Purpose</label>
                                <input type="text" class="form-control form-control-sm" value="{{ $payreq->remarks }}"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>Current due date</label>
                                <input type="text" class="form-control form-control-sm"
                                    value="{{ \Carbon\Carbon::parse($payreq->due_date)->format('d-M-Y') }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="requested-payreq-{{ $payreq->id }}">Requested new due date</label>
                                <input type="date" name="requested_due_date" id="requested-payreq-{{ $payreq->id }}"
                                    class="form-control form-control-sm" required
                                    min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                                    value="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
                            </div>
                            <div class="form-group">
                                <label for="reason-payreq-{{ $payreq->id }}">Reason</label>
                                <textarea name="reason" id="reason-payreq-{{ $payreq->id }}"
                                    class="form-control form-control-sm" rows="3" required maxlength="500"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-sm btn-primary">Submit request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

@foreach ($realizations as $realization)
    @continue(! in_array($realization->project ?? '', \App\Models\OverdueExtension::eligibleProjects(), true))

    @if (($realization->overdue_extensions_pending_count ?? 0) === 0)
        <div class="modal fade" id="extension-modal-realization-{{ $realization->id }}" tabindex="-1" role="dialog"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('document-overdue.extensions.store') }}" method="POST" novalidate>
                        @csrf
                        <input type="hidden" name="document_type" value="realization">
                        <input type="hidden" name="document_id" value="{{ $realization->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title">Request overdue extension — {{ $realization->nomor }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-2">{{ $realization->overdue_extensions_total_count ?? 0 }}
                                request(s) submitted,
                                {{ $realization->overdue_extensions_approved_count ?? 0 }} approved.</p>
                            <div class="form-group">
                                <label>Related payreq remarks</label>
                                <input type="text" class="form-control form-control-sm"
                                    value="{{ $realization->payreq->remarks ?? '' }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>Current due date</label>
                                <input type="text" class="form-control form-control-sm"
                                    value="{{ \Carbon\Carbon::parse($realization->due_date)->format('d-M-Y') }}"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="requested-real-{{ $realization->id }}">Requested new due date</label>
                                <input type="date" name="requested_due_date" id="requested-real-{{ $realization->id }}"
                                    class="form-control form-control-sm" required
                                    min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                                    value="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
                            </div>
                            <div class="form-group">
                                <label for="reason-real-{{ $realization->id }}">Reason</label>
                                <textarea name="reason" id="reason-real-{{ $realization->id }}"
                                    class="form-control form-control-sm" rows="3" required maxlength="500"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-sm btn-primary">Submit request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
