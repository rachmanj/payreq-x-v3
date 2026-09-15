@php
    $submitLimitUseVjUi = $submitLimitUseVjUi ?? true;
@endphp

<div class="payreq-submit-limit-indicator mb-2">
    <p class="text-muted small mb-1">
        <i class="fas fa-hourglass-half mr-1"></i>
        Payreq menunggu approval: <strong>{{ $submitLimitSummary['count'] }}</strong> dari
        <strong>{{ $submitLimitSummary['limit'] }}</strong>
    </p>
    @if ($submitLimitSummary['blocked'])
        @if ($submitLimitUseVjUi)
            <div class="vj-alert vj-alert-warning mb-0" role="alert">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                {{ $submitLimitBlockedMessage }}
            </div>
        @else
            <div class="alert alert-warning mb-0" role="alert">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                {{ $submitLimitBlockedMessage }}
            </div>
        @endif
    @endif
</div>
