@php
    /** @var \App\Models\Anggaran|null $ang */
    $ang = $ang ?? null;
    $maxDescription = $maxDescription ?? 120;
@endphp
@if ($ang)
    @php
        $angLine =
            'No. '.$ang->nomor
            .(filled($ang->rab_no) && (string) $ang->rab_no !== (string) ($ang->nomor ?? '')
                ? ' | RAB '.$ang->rab_no
                : '')
            .' | '.$ang->rab_project
            .' | '.substr((string) ($ang->description ?? ''), 0, $maxDescription);
    @endphp
    {{ $angLine }}
@else
    —
@endif
