@php
    $status = $dokumen->validation_status;
@endphp
@switch($status)
    @case(\App\Models\Dokumen::VALIDATION_PENDING)
        <span class="badge badge-warning">Pending</span>
        @break

    @case(\App\Models\Dokumen::VALIDATION_VALIDATED)
        <span class="badge badge-success">Validated</span>
        @break

    @case(\App\Models\Dokumen::VALIDATION_REJECTED)
        <div class="d-flex flex-wrap align-items-center">
            <span class="badge badge-danger">Rejected</span>
            @if (! empty($dokumen->rejection_reason))
                <button type="button" class="btn btn-xs btn-outline-secondary ml-1 mt-1 mt-sm-0" data-toggle="modal"
                    data-target="#rejectionInfo-{{ $dokumen->id }}">
                    <i class="fas fa-comment-alt"></i> View reason
                </button>
            @endif
        </div>
        @if (! empty($dokumen->rejection_reason))
            <div class="modal fade text-left" id="rejectionInfo-{{ $dokumen->id }}" tabindex="-1" role="dialog"
                aria-labelledby="rejectionInfoTitle-{{ $dokumen->id }}" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rejectionInfoTitle-{{ $dokumen->id }}">Rejection reason</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-2">
                                @if ($dokumen->validatedBy)
                                    Rejected by <strong>{{ $dokumen->validatedBy->name }}</strong>
                                @endif
                                @if ($dokumen->validated_at)
                                    @if ($dokumen->validatedBy)
                                        &middot;
                                    @endif
                                    {{ $dokumen->validated_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                                @endif
                            </p>
                            <div class="border rounded p-2 bg-light">
                                {!! nl2br(e($dokumen->rejection_reason)) !!}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @break

    @default
        <span class="badge badge-secondary">{{ $status ?? '—' }}</span>
@endswitch
