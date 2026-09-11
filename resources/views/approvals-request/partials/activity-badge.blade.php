@php
    $headerActivity = $headerActivity ?? null;
@endphp
@if ($detail->activity_excluded)
    <span class="vj-chip vj-chip-neutral">Tanpa kegiatan</span>
@elseif ($detail->activity)
    <span class="vj-chip vj-chip-info" title="{{ $detail->activity->name }}">{{ $detail->activity->code }}</span>
@elseif ($headerActivity)
    <span class="vj-chip vj-chip-neutral" title="{{ $headerActivity->name }}">{{ $headerActivity->code }}</span>
@else
    <span class="text-muted">—</span>
@endif
