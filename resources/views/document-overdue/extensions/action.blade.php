@php
    use App\Models\OverdueExtension;
@endphp

@if ($extension->status === OverdueExtension::STATUS_PENDING && auth()->user()?->can('approve_overdue_extension'))
    @php
        $typeLabel = $extension->document_type === OverdueExtension::DOCUMENT_PAYREQ ? 'Payreq' : 'Realization';
    @endphp
    <button type="button" class="btn btn-xs btn-success" data-toggle="modal"
        data-target="#approve-extension-{{ $extension->id }}">Approve</button>
    <button type="button" class="btn btn-xs btn-danger" data-toggle="modal"
        data-target="#reject-extension-{{ $extension->id }}">Reject</button>

    <div class="modal fade" id="approve-extension-{{ $extension->id }}" tabindex="-1" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('document-overdue.extensions.approve', $extension) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Approve extension request</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <dl class="row small mb-3">
                            <dt class="col-sm-4">Employee</dt>
                            <dd class="col-sm-8">{{ $extension->requestor->name }}</dd>
                            <dt class="col-sm-4">Document</dt>
                            <dd class="col-sm-8">{{ $typeLabel }} — {{ $extension->resolveNomor() ?? '—' }}</dd>
                            <dt class="col-sm-4">Project</dt>
                            <dd class="col-sm-8">{{ $extension->resolveProject() ?? '—' }}</dd>
                            <dt class="col-sm-4">Current due date</dt>
                            <dd class="col-sm-8">{{ $extension->current_due_date?->format('d-M-Y') ?? '—' }}</dd>
                            <dt class="col-sm-4">Requestor reason</dt>
                            <dd class="col-sm-8">{{ $extension->reason }}</dd>
                            <dt class="col-sm-4">Remarks</dt>
                            <dd class="col-sm-8">{{ $extension->resolveRemarks() ?? '—' }}</dd>
                        </dl>
                        <div class="form-group">
                            <label for="approve-requested-due-{{ $extension->id }}">Requested new due date</label>
                            <input type="date" name="requested_due_date" id="approve-requested-due-{{ $extension->id }}"
                                class="form-control form-control-sm" required
                                value="{{ $extension->requested_due_date?->format('Y-m-d') }}"
                                min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reject-extension-{{ $extension->id }}" tabindex="-1" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('document-overdue.extensions.reject', $extension) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Reject extension request</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="review_notes-{{ $extension->id }}">Review notes</label>
                            <textarea name="review_notes" id="review_notes-{{ $extension->id }}" class="form-control"
                                rows="3" required maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    <span class="text-muted">—</span>
@endif
