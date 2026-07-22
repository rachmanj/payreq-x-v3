@props([
    'icon' => 'fas fa-chart-bar',
    'value',
    'label',
    'info' => null,
    'infoIcon' => null,
    'tone' => 'primary',
    'href' => null,
    'title' => null,
])

@php
    $tones = [
        'success' => [
            'bar' => 'tw-bg-gradient-to-r tw-from-teal-500 tw-to-green-400',
            'icon' => 'tw-bg-gradient-to-br tw-from-teal-500 tw-to-green-400',
            'value' => 'tw-text-teal-600',
            'action' => 'hover:tw-bg-gradient-to-br hover:tw-from-teal-500 hover:tw-to-green-400',
        ],
        'danger' => [
            'bar' => 'tw-bg-gradient-to-r tw-from-pink-600 tw-to-orange-500',
            'icon' => 'tw-bg-gradient-to-br tw-from-pink-600 tw-to-orange-500',
            'value' => 'tw-text-pink-600',
            'action' => 'hover:tw-bg-gradient-to-br hover:tw-from-pink-600 hover:tw-to-orange-500',
        ],
        'warning' => [
            'bar' => 'tw-bg-gradient-to-r tw-from-amber-500 tw-to-amber-600',
            'icon' => 'tw-bg-gradient-to-br tw-from-amber-500 tw-to-amber-600',
            'value' => 'tw-text-amber-600',
            'action' => 'hover:tw-bg-gradient-to-br hover:tw-from-amber-500 hover:tw-to-amber-600',
        ],
        'primary' => [
            'bar' => 'tw-bg-gradient-to-r tw-from-brand-500 tw-to-brand-700',
            'icon' => 'tw-bg-gradient-to-br tw-from-brand-500 tw-to-brand-700',
            'value' => 'tw-text-brand-500',
            'action' => 'hover:tw-bg-gradient-to-br hover:tw-from-brand-500 hover:tw-to-brand-700',
        ],
        'approval' => [
            'bar' => 'tw-bg-gradient-to-r tw-from-yellow-300 tw-to-orange-300',
            'icon' => 'tw-bg-gradient-to-br tw-from-yellow-300 tw-to-orange-300',
            'value' => 'tw-text-orange-400',
            'action' => 'hover:tw-bg-gradient-to-br hover:tw-from-yellow-300 hover:tw-to-orange-300',
        ],
    ];
    $t = $tones[$tone] ?? $tones['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'tw-bg-white tw-rounded-xl tw-shadow-card tw-p-5 tw-mb-5 tw-flex tw-items-center tw-relative tw-overflow-hidden tw-transition-all tw-duration-300 hover:tw-shadow-card-hover hover:-tw-translate-y-1']) }}>
    <div class="tw-absolute tw-top-0 tw-left-0 tw-right-0 tw-h-1 {{ $t['bar'] }}"></div>

    <div class="tw-w-[70px] tw-h-[70px] tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-5 tw-shrink-0 {{ $t['icon'] }}">
        <i class="{{ $icon }} tw-text-[32px] tw-text-white"></i>
    </div>

    <div class="tw-flex-1 tw-min-w-0">
        <div class="tw-text-[32px] tw-font-bold tw-leading-none tw-mb-1 {{ $t['value'] }}">{{ $value }}</div>
        <div class="tw-text-sm tw-text-gray-500 tw-font-medium tw-mb-2">{{ $label }}</div>
        @if ($info)
            <div class="tw-text-xs tw-text-gray-500 tw-flex tw-items-center tw-gap-1">
                @if ($infoIcon)
                    <i class="{{ $infoIcon }}"></i>
                @endif
                {{ $info }}
            </div>
        @endif
    </div>

    @if ($href)
        <a href="{{ $href }}"
            class="tw-w-10 tw-h-10 tw-rounded-full tw-bg-gray-50 tw-flex tw-items-center tw-justify-center tw-ml-4 tw-text-gray-600 tw-no-underline tw-transition-all tw-duration-300 hover:tw-text-white hover:tw-scale-110 {{ $t['action'] }}"
            @if ($title) title="{{ $title }}" @endif>
            <i class="fas fa-arrow-right"></i>
        </a>
    @endif
</div>
