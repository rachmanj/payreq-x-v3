@php
    $active = $active ?? 'payreq';
    $document_count = $document_count ?? ['payreq' => 0, 'realization' => 0, 'rab' => 0];
    $tabs = [
        'payreq' => [
            'route' => 'approvals.request.payreqs.index',
            'label' => 'Payment Request',
            'icon' => 'fa-file-invoice-dollar',
            'count_key' => 'payreq',
            'badge_class' => 'payreq-badge',
        ],
        'realization' => [
            'route' => 'approvals.request.realizations.index',
            'label' => 'Realization',
            'icon' => 'fa-file-invoice',
            'count_key' => 'realization',
            'badge_class' => 'realization-badge',
        ],
        'rab' => [
            'route' => 'approvals.request.anggarans.index',
            'label' => 'RABs',
            'icon' => 'fa-calculator',
            'count_key' => 'rab',
            'badge_class' => 'rab-badge',
        ],
    ];
@endphp

<nav class="vj-approval-doc-tabs" aria-label="Approval document types">
    @foreach ($tabs as $key => $tab)
        @php
            $count = (int) ($document_count[$tab['count_key']] ?? 0);
        @endphp
        @if ($active === $key)
            <span class="vj-chip vj-chip-primary">
                <i class="fas {{ $tab['icon'] }}"></i>
                {{ $tab['label'] }}
                <span class="vj-chip vj-chip-danger {{ $tab['badge_class'] }}" @if ($count === 0) style="display:none" @endif>{{ $count }}</span>
            </span>
        @else
            <a href="{{ route($tab['route']) }}" class="vj-action-item vj-action-print">
                <i class="fas {{ $tab['icon'] }}"></i>
                <span>{{ $tab['label'] }}</span>
                <span class="vj-chip vj-chip-danger {{ $tab['badge_class'] }}" @if ($count === 0) style="display:none" @endif>{{ $count }}</span>
            </a>
        @endif
    @endforeach
</nav>
