{{-- Toggle Printable Status based on document type --}}
@php
    // Determine which printable status to show based on document type
    if ($payreq->type === 'advance') {
        // For advance type, show realization printable status
        $isPrintable = $payreq->realization ? $payreq->realization->printable : false;
        $buttonTitle = 'Toggle Realization Printable Status';
    } else {
        // For reimburse type, show payreq printable status
        $isPrintable = $payreq->printable;
        $buttonTitle = 'Toggle PayReq Printable Status';
    }
@endphp

<button type="button" class="btn btn-xs {{ $isPrintable ? 'btn-success' : 'btn-secondary' }} toggle-printable"
    data-id="{{ $payreq->id }}" data-current="{{ $isPrintable ? 1 : 0 }}" title="{{ $buttonTitle }}">
    <i class="fas fa-toggle-{{ $isPrintable ? 'on' : 'off' }}"></i>
    {{ $isPrintable ? 'ON' : 'OFF' }}
</button>
