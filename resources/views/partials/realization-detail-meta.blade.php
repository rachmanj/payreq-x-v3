@php
    $meta = array_filter([
        filled($detail->unit_no ?? null) ? 'Unit: '.$detail->unit_no : null,
        filled($detail->nopol ?? null) ? 'Nopol: '.$detail->nopol : null,
        filled($detail->km_position ?? null) ? 'HM: '.$detail->km_position : null,
    ]);
@endphp

@if ($meta !== [])
    <br>
    <small class="text-muted">{{ implode(' | ', $meta) }}</small>
@endif
