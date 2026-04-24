@props(['compliance' => null])

@php
    $c = $compliance;
@endphp

@if ($c && ($c['show_banner'] ?? false))
    <div class="alert alert-{{ $c['variant'] === 'success' ? 'success' : ($c['variant'] === 'warning' ? 'warning' : ($c['variant'] === 'danger' ? 'danger' : 'info')) }} alert-dismissible fade show border-0 shadow-sm mb-3"
        role="alert">
        <div class="d-flex flex-wrap align-items-start">
            <div class="mr-2 mt-1">
                <i
                    class="fas fa-{{ $c['variant'] === 'info' ? 'info-circle' : ($c['variant'] === 'danger' ? 'exclamation-triangle' : 'exclamation-circle') }}"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading {{ !empty($c['title_id']) ? 'mb-0' : 'mb-1' }}">{{ $c['title'] }}</h5>
                @if (!empty($c['title_id']))
                    <p class="mb-2 small font-weight-bold" lang="id">{{ $c['title_id'] }}</p>
                @endif
                <p class="mb-2 @if (!empty($c['message_id'])) mb-md-0 @endif">{{ $c['message'] }}</p>
                @if (!empty($c['message_id']))
                    <p class="mb-2 small" lang="id">{{ $c['message_id'] }}</p>
                @endif
                @if (!empty($c['current_week_label']))
                    <p class="mb-0 small text-muted">
                        <span class="d-block">
                            <strong>Current week ({{ config('pcbc_compliance.timezone') }}):</strong>
                            {{ $c['current_week_label'] }}
                        </span>
                        <span class="d-block" lang="id">
                            <strong>Minggu berjalan ({{ config('pcbc_compliance.timezone') }}):</strong>
                            {{ $c['current_week_label'] }}
                        </span>
                    </p>
                @endif
                @if (isset($c['weeks']) && is_array($c['weeks']) && !($c['exempt'] ?? false))
                    <ul class="list-unstyled small mb-0 mt-2 pl-0">
                        @foreach (['current', 'w1', 'w2'] as $key)
                            @if (!empty($c['weeks'][$key]))
                                <li>
                                    <i
                                        class="fas fa-{{ $c['weeks'][$key]['has_upload'] ? 'check text-success' : 'times text-danger' }}"></i>
                                    <strong>{{ $c['weeks'][$key]['label'] }}</strong>
                                    @if (!empty($c['weeks'][$key]['label_id']))
                                        <span class="text-muted" lang="id">/ {{ $c['weeks'][$key]['label_id'] }}</span>
                                    @endif
                                    <span class="text-nowrap">:</span> {{ $c['weeks'][$key]['range'] }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
                @if (!($c['exempt'] ?? false) && ($c['sanctioned'] ?? false))
                    <a href="{{ route('cashier.pcbc.index', ['page' => 'upload']) }}" class="btn btn-sm btn-light mt-2"
                        title="Buka unggah PCBC">
                        Go to PCBC upload <span class="d-inline" lang="id">· Buka unggah PCBC</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
