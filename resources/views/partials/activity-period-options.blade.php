@php
    use App\Support\ActivityPeriodOptions;

    $selectedPeriode = filled($selectedPeriode ?? null)
        ? $selectedPeriode
        : ActivityPeriodOptions::currentMonthValue();
    $monthlyOptions = ActivityPeriodOptions::monthly();
    $annualOptions = ActivityPeriodOptions::annual();
    $knownValues = array_merge(array_keys($monthlyOptions), array_keys($annualOptions));
    $legacyValue = ! in_array($selectedPeriode, $knownValues, true) ? $selectedPeriode : null;
@endphp

@if ($legacyValue)
    <option value="{{ $legacyValue }}" selected>{{ $legacyValue }}</option>
@endif

@foreach ($monthlyOptions as $value => $label)
    <option value="{{ $value }}" @selected($selectedPeriode === $value)>{{ $label }}</option>
@endforeach

<optgroup label="Tahunan">
    @foreach ($annualOptions as $value => $label)
        <option value="{{ $value }}" @selected($selectedPeriode === $value)>{{ $label }}</option>
    @endforeach
</optgroup>
