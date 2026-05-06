@php
    use App\Models\OverdueExtension;
    $realizationExtensionHistory = OverdueExtension::query()
        ->where('document_type', OverdueExtension::DOCUMENT_REALIZATION)
        ->where('document_id', $model->id)
        ->with(['reviewer', 'requestor'])
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();
@endphp

<div class="btn-group btn-group-sm">
    @can('approve_overdue_extension')
        <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#extend-{{ $model->id }}">
            extend
        </button>
    @endcan
    @can('approve_overdue_extension')
        <button type="button" class="btn btn-xs btn-info" data-toggle="modal"
            data-target="#history-realization-{{ $model->id }}">
            history
        </button>
    @endcan
</div>

@can('approve_overdue_extension')
    <!-- Extend Modal -->
    <div class="modal fade" id="extend-{{ $model->id }}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel">Extend Due Date</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('document-overdue.realization.extend') }}" method="POST">
                    @csrf
                    <div class="modal-body">

                        <input type="hidden" name="realization_id" value="{{ $model->id }}">

                        <div class="form-group">
                            <label>Remarks</label>
                            <input type="text" value="{{ $model->payreq->remarks }}" class="form-control" readonly>
                        </div>
                        <div class="row">
                            <div class="form-group col-6">
                                <label>Current Due Date</label>
                                <input type="text" value="{{ date('d-M-Y', strtotime($model->due_date)) }}"
                                    class="form-control" readonly>
                            </div>
                            <div class="form-group col-6">
                                <label>Approved Date</label>
                                <input type="text" value="{{ date('d-M-Y', strtotime($model->approved_at)) }}"
                                    class="form-control" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>New Due Date</label>
                            <input type="date" name="new_due_date" class="form-control" value="{{ $model->due_date }}">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan

@can('approve_overdue_extension')
    <!-- History Modal -->
    <div class="modal fade" id="history-realization-{{ $model->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Extension history — {{ $model->nomor }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">{{ $realizationExtensionHistory->count() }} request(s) recorded.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Requested Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Reviewed By</th>
                                    <th>Reviewed At</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($realizationExtensionHistory as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row->requested_due_date?->format('d-M-Y') }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($row->reason, 80) }}</td>
                                        <td>{{ $row->status }}</td>
                                        <td>{{ $row->reviewer->name }}</td>
                                        <td>{{ $row->reviewed_at?->format('d-M-Y H:i') ?? '—' }}</td>
                                        <td>{{ $row->review_notes ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">No extension requests.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endcan
