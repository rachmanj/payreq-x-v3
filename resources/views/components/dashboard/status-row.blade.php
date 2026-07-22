@props([
    'status',
    'count',
    'unit' => 'item',
    'amount' => null,
    'overdue' => false,
])

@php
    $badgeClasses = [
        'draft' => 'tw-bg-blue-50 tw-text-blue-700',
        'submitted' => 'tw-bg-yellow-100 tw-text-yellow-800',
        'approved' => 'tw-bg-green-100 tw-text-green-800',
        'paid' => 'tw-bg-cyan-100 tw-text-cyan-800',
        'done' => 'tw-bg-cyan-100 tw-text-cyan-800',
        'overdue' => 'tw-bg-red-100 tw-text-red-800',
    ];
    $badgeClass = $badgeClasses[strtolower($status)] ?? 'tw-bg-gray-100 tw-text-gray-700';
    $unitLabel = $count === 1 ? $unit : $unit . 's';
@endphp

<div {{ $attributes->merge([
    'class' => 'tw-p-4 tw-rounded-lg tw-mb-3 tw-transition-all tw-duration-300 last:tw-mb-0 ' .
        ($overdue
            ? 'tw-bg-red-50 tw-border-l-4 tw-border-red-500 hover:tw-bg-red-100'
            : 'tw-bg-gray-50 hover:tw-bg-gray-100 hover:tw-translate-x-1'),
]) }}>
    <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
        <span class="tw-inline-block tw-px-3 tw-py-1 tw-rounded-full tw-text-xs tw-font-semibold tw-uppercase {{ $badgeClass }}">
            @if ($overdue)
                <i class="fas fa-exclamation-triangle"></i>
            @endif
            {{ ucfirst($status) }}
        </span>
        <span class="tw-text-[13px] tw-text-gray-500">{{ $count }} {{ $unitLabel }}</span>
    </div>
    @if (!is_null($amount))
        <div @class([
            'tw-text-lg tw-font-bold',
            'tw-text-red-600' => $overdue,
            'tw-text-gray-700' => !$overdue,
        ])>
            Rp {{ number_format((float) $amount, 0, ',', '.') }}
        </div>
    @endif
</div>
