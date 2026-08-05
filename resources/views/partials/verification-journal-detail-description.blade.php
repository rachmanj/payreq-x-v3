@php
    $description = (string) ($description ?? '');
    $newlinePosition = strpos($description, "\n");
@endphp

@if ($newlinePosition !== false)
    {{ substr($description, 0, $newlinePosition) }}
    <br>
    <small class="text-muted">{{ trim(str_replace(['[', ']'], '', substr($description, $newlinePosition + 1))) }}</small>
@else
    {{ $description }}
@endif
