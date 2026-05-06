@php
    use App\Models\OverdueExtension;
@endphp

@if ($extension->status === OverdueExtension::STATUS_PENDING && auth()->user()?->can('approve_overdue_extension'))
    <form action="{{ route('document-overdue.extensions.approve', $extension) }}" method="POST" class="d-inline">
        @csrf
        @method('PUT')
        <button type="submit" class="btn btn-xs btn-success">Approve</button>
    </form>
    <button type="button" class="btn btn-xs btn-danger" data-toggle="modal"
        data-target="#reject-extension-{{ $extension->id }}">Reject</button>

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
