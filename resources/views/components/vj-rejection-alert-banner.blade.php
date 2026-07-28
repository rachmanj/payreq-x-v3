@props(['alerts' => []])

@if ($alerts->isNotEmpty())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3 no-print" role="alert">
        <div class="d-flex flex-wrap align-items-start">
            <div class="mr-2 mt-1">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-2">
                    Verification Journal{{ $alerts->count() > 1 ? 's' : '' }} Rejected
                </h5>
                <p class="mb-2">
                    {{ $alerts->count() === 1
                        ? 'Your verification journal was rejected and needs correction before it can be re-validated.'
                        : $alerts->count() . ' of your verification journals were rejected and need correction before they can be re-validated.' }}
                </p>

                @foreach ($alerts as $alert)
                    <div class="border rounded bg-white p-3 mb-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-start">
                            <div class="mb-2 mb-md-0">
                                <strong>{{ $alert['nomor'] }}</strong>
                                <span class="badge badge-secondary ml-1">{{ $alert['project'] }}</span>
                                @if (! empty($alert['rejected_by']))
                                    <div class="small text-muted mt-1">
                                        Rejected by {{ $alert['rejected_by'] }}
                                        @if (! empty($alert['rejected_at']))
                                            on {{ $alert['rejected_at'] }}
                                        @endif
                                    </div>
                                @endif
                                @if (! empty($alert['rejection_reason']))
                                    <div class="mt-2">
                                        <strong>Reason:</strong> {{ $alert['rejection_reason'] }}
                                    </div>
                                @endif
                            </div>
                            <a href="{{ $alert['url'] }}" class="btn btn-sm btn-danger">
                                <i class="fas fa-edit"></i> Review &amp; Fix
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
