@props([
    'title',
    'subtitle' => null,
    'icon' => 'fas fa-chart-bar',
    'gradient' => 'tw-bg-gradient-to-br tw-from-brand-500 tw-to-brand-700',
])

<div {{ $attributes->merge(['class' => 'tw-bg-white tw-rounded-xl tw-shadow-card tw-overflow-hidden tw-mb-5 tw-transition-all tw-duration-300 hover:tw-shadow-card-hover']) }}>
    <div class="tw-px-5 tw-py-5 tw-flex tw-justify-between tw-items-center tw-flex-wrap tw-gap-3 {{ $gradient }}">
        <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0">
            <div class="tw-bg-white/20 tw-w-[50px] tw-h-[50px] tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mr-4 tw-shrink-0">
                <i class="{{ $icon }} tw-text-2xl tw-text-white"></i>
            </div>
            <div class="tw-text-white tw-min-w-0">
                <h4 class="tw-text-lg tw-font-semibold tw-text-white tw-mb-0 tw-leading-tight">{{ $title }}</h4>
                @if ($subtitle)
                    <small class="tw-text-white/80 tw-text-xs">{{ $subtitle }}</small>
                @endif
            </div>
        </div>

        @isset($action)
            <div class="tw-shrink-0">
                {{ $action }}
            </div>
        @endisset

        @isset($headerExtra)
            <div class="tw-shrink-0">
                {{ $headerExtra }}
            </div>
        @endisset
    </div>

    <div class="tw-p-5">
        {{ $slot }}
    </div>
</div>
