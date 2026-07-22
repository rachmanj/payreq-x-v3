@props([
    'icon' => 'fas fa-check-circle',
    'title' => 'All caught up!',
    'subtitle' => null,
    'iconClass' => 'tw-text-green-500',
])

<div {{ $attributes->merge(['class' => 'tw-text-center tw-py-10 tw-px-5 tw-text-gray-500']) }}>
    <i class="{{ $icon }} tw-text-5xl tw-mb-4 {{ $iconClass }}"></i>
    <p class="tw-text-base tw-font-semibold tw-mb-1 tw-text-gray-600">{{ $title }}</p>
    @if ($subtitle)
        <small class="tw-text-sm tw-text-gray-500">{{ $subtitle }}</small>
    @endif
</div>
