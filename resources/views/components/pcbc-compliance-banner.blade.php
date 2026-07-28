@props(['compliance' => null])

@php
    $c = $compliance;
    $variant = $c['variant'] ?? 'info';
    $bannerVariantClass = match ($variant) {
        'success' => 'vj-banner-success',
        'warning' => 'vj-banner-warning',
        'danger' => 'vj-banner-danger',
        default => 'vj-banner-info',
    };
    $bannerIcon = match ($variant) {
        'info' => 'info-circle',
        'danger' => 'exclamation-triangle',
        default => 'exclamation-circle',
    };
@endphp

@if ($c && ($c['show_banner'] ?? false))
    @once
        <style>
                .vj-banner {
                    border: 1px solid transparent;
                    border-radius: 10px;
                    padding: 0.85rem 1rem;
                    margin-bottom: 1rem;
                }

                .vj-banner-inner {
                    display: flex;
                    align-items: flex-start;
                    gap: 0.75rem;
                }

                .vj-banner-icon {
                    width: 2rem;
                    height: 2rem;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    font-size: 0.95rem;
                    border: 1px solid transparent;
                }

                .vj-banner-body {
                    flex: 1;
                    min-width: 0;
                }

                .vj-banner-toolbar {
                    display: flex;
                    justify-content: flex-end;
                    margin-bottom: 0.5rem;
                }

                .vj-banner-lang {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.25rem;
                    padding: 0.15rem;
                    border-radius: 8px;
                    background: rgba(255, 255, 255, 0.55);
                    border: 1px solid rgba(0, 0, 0, 0.06);
                }

                .vj-banner-lang-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 2rem;
                    padding: 0.2rem 0.45rem;
                    border: 1px solid transparent;
                    border-radius: 6px;
                    background: transparent;
                    color: #6c757d;
                    font-size: 0.7rem;
                    font-weight: 600;
                    line-height: 1.2;
                    cursor: pointer;
                    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
                }

                .vj-banner-lang-btn.active {
                    background: #fff;
                    border-color: #dee2e6;
                    color: #343a40;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
                }

                .vj-banner-title {
                    font-size: 0.95rem;
                    font-weight: 600;
                    line-height: 1.35;
                    margin: 0 0 0.35rem;
                    color: inherit;
                }

                .vj-banner-message {
                    font-size: 0.875rem;
                    line-height: 1.45;
                    margin: 0 0 0.5rem;
                }

                .vj-banner-meta {
                    font-size: 0.8125rem;
                    line-height: 1.4;
                    margin: 0;
                    opacity: 0.85;
                }

                .vj-banner-weeks {
                    list-style: none;
                    margin: 0.5rem 0 0;
                    padding: 0;
                    font-size: 0.8125rem;
                }

                .vj-banner-weeks li {
                    display: flex;
                    align-items: center;
                    gap: 0.45rem;
                    margin-bottom: 0.25rem;
                }

                .vj-banner-weeks li:last-child {
                    margin-bottom: 0;
                }

                .vj-banner-week-icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 1.15rem;
                    height: 1.15rem;
                    border-radius: 4px;
                    font-size: 0.65rem;
                    border: 1px solid transparent;
                    flex-shrink: 0;
                }

                .vj-banner-week-icon.is-ok {
                    background: #e8f5e9;
                    border-color: #c8e6c9;
                    color: #198754;
                }

                .vj-banner-week-icon.is-miss {
                    background: #ffebee;
                    border-color: #ffcdd2;
                    color: #c62828;
                }

                .vj-banner-info {
                    background: #e7f3ff;
                    border-color: #b8daff;
                    color: #0c5460;
                }

                .vj-banner-info .vj-banner-icon {
                    background: #fff;
                    border-color: #b8daff;
                    color: #17a2b8;
                }

                .vj-banner-warning {
                    background: #fff8e1;
                    border-color: #ffe082;
                    color: #7a5c00;
                }

                .vj-banner-warning .vj-banner-icon {
                    background: #fff;
                    border-color: #ffe082;
                    color: #f0ad4e;
                }

                .vj-banner-danger {
                    background: #fff5f5;
                    border-color: #f1c2c7;
                    color: #842029;
                }

                .vj-banner-danger .vj-banner-icon {
                    background: #fff;
                    border-color: #f1c2c7;
                    color: #dc3545;
                }

                .vj-banner-success {
                    background: #e8f5e9;
                    border-color: #c8e6c9;
                    color: #1b5e20;
                }

                .vj-banner-success .vj-banner-icon {
                    background: #fff;
                    border-color: #c8e6c9;
                    color: #198754;
                }

                .vj-action-item,
                .vj-action-item-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.4rem;
                    padding: 0.4rem 0.7rem;
                    border: 1px solid transparent;
                    border-radius: 8px;
                    background: transparent;
                    color: #495057;
                    font-size: 0.8125rem;
                    font-weight: 500;
                    text-decoration: none;
                    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
                }

                .vj-action-item-xs {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.75rem;
                }

                .vj-action-item-xs i {
                    font-size: 0.7rem;
                }

                .vj-action-sap {
                    background: #fff3e0;
                    border-color: #ffcc80;
                    color: #e65100;
                }

                .vj-action-sap i {
                    color: #ff9800;
                }

                .vj-action-sap:hover {
                    background: #ffe0b2;
                    border-color: #ffb74d;
                    color: #bf360c;
                    text-decoration: none;
                }
        </style>
    @endonce

    <div data-pcbc-banner class="vj-banner {{ $bannerVariantClass }} no-print" role="alert">
        <div class="vj-banner-inner">
            <div class="vj-banner-icon" aria-hidden="true">
                <i class="fas fa-{{ $bannerIcon }}"></i>
            </div>
            <div class="vj-banner-body">
                <div class="vj-banner-toolbar">
                    <div class="vj-banner-lang" role="group" aria-label="PCBC banner language">
                        <button type="button" class="vj-banner-lang-btn active pcbc-banner-lang-btn" data-lang="en"
                            aria-pressed="true">EN</button>
                        <button type="button" class="vj-banner-lang-btn pcbc-banner-lang-btn" data-lang="id"
                            aria-pressed="false">ID</button>
                    </div>
                </div>

                <div class="pcbc-banner-lang-en">
                    <h5 class="vj-banner-title">{{ $c['title'] }}</h5>
                    <p class="vj-banner-message">{{ $c['message'] }}</p>
                    @if (!empty($c['current_week_label']))
                        <p class="vj-banner-meta">
                            <strong>Current week ({{ config('pcbc_compliance.timezone') }}):</strong>
                            {{ $c['current_week_label'] }}
                        </p>
                    @endif
                    @if (isset($c['weeks']) && is_array($c['weeks']) && !($c['exempt'] ?? false))
                        <ul class="vj-banner-weeks">
                            @foreach (['current', 'w1', 'w2'] as $key)
                                @if (!empty($c['weeks'][$key]))
                                    <li>
                                        <span
                                            class="vj-banner-week-icon {{ $c['weeks'][$key]['has_upload'] ? 'is-ok' : 'is-miss' }}">
                                            <i
                                                class="fas fa-{{ $c['weeks'][$key]['has_upload'] ? 'check' : 'times' }}"></i>
                                        </span>
                                        <span>
                                            <strong>{{ $c['weeks'][$key]['label'] }}</strong>
                                            <span class="text-nowrap">:</span> {{ $c['weeks'][$key]['range'] }}
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                    @if (!($c['exempt'] ?? false) && ($c['sanctioned'] ?? false))
                        <a href="{{ route('cashier.pcbc.index', ['page' => 'upload']) }}"
                            class="vj-action-item vj-action-item-xs vj-action-sap mt-2 d-inline-flex"
                            title="Go to PCBC upload">
                            <i class="fas fa-upload"></i>
                            <span>Go to PCBC upload</span>
                        </a>
                    @endif
                </div>

                <div class="pcbc-banner-lang-id d-none">
                    <h5 class="vj-banner-title" lang="id">{{ $c['title_id'] ?? $c['title'] }}</h5>
                    <p class="vj-banner-message" lang="id">{{ $c['message_id'] ?? $c['message'] }}</p>
                    @if (!empty($c['current_week_label']))
                        <p class="vj-banner-meta" lang="id">
                            <strong>Minggu berjalan ({{ config('pcbc_compliance.timezone') }}):</strong>
                            {{ $c['current_week_label'] }}
                        </p>
                    @endif
                    @if (isset($c['weeks']) && is_array($c['weeks']) && !($c['exempt'] ?? false))
                        <ul class="vj-banner-weeks" lang="id">
                            @foreach (['current', 'w1', 'w2'] as $key)
                                @if (!empty($c['weeks'][$key]))
                                    <li>
                                        <span
                                            class="vj-banner-week-icon {{ $c['weeks'][$key]['has_upload'] ? 'is-ok' : 'is-miss' }}">
                                            <i
                                                class="fas fa-{{ $c['weeks'][$key]['has_upload'] ? 'check' : 'times' }}"></i>
                                        </span>
                                        <span>
                                            <strong>{{ $c['weeks'][$key]['label_id'] ?? $c['weeks'][$key]['label'] }}</strong>
                                            <span class="text-nowrap">:</span> {{ $c['weeks'][$key]['range'] }}
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                    @if (!($c['exempt'] ?? false) && ($c['sanctioned'] ?? false))
                        <a href="{{ route('cashier.pcbc.index', ['page' => 'upload']) }}"
                            class="vj-action-item vj-action-item-xs vj-action-sap mt-2 d-inline-flex"
                            title="Buka unggah PCBC">
                            <i class="fas fa-upload"></i>
                            <span>Buka unggah PCBC</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var root = document.querySelector('[data-pcbc-banner]');
            if (!root) {
                return;
            }
            var storageKey = 'pcbcComplianceBannerLang';
            var panelEn = root.querySelector('.pcbc-banner-lang-en');
            var panelId = root.querySelector('.pcbc-banner-lang-id');
            var buttons = root.querySelectorAll('.pcbc-banner-lang-btn');

            function setLang(lang) {
                var useEn = lang === 'en';
                if (panelEn) {
                    panelEn.classList.toggle('d-none', !useEn);
                }
                if (panelId) {
                    panelId.classList.toggle('d-none', useEn);
                }
                buttons.forEach(function(btn) {
                    var active = btn.getAttribute('data-lang') === lang;
                    btn.classList.toggle('active', active);
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                try {
                    sessionStorage.setItem(storageKey, lang);
                } catch (e) {}
            }

            var stored = null;
            try {
                stored = sessionStorage.getItem(storageKey);
            } catch (e) {}
            var initial = stored === 'id' ? 'id' : 'en';
            setLang(initial);

            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var lang = btn.getAttribute('data-lang');
                    if (lang === 'en' || lang === 'id') {
                        setLang(lang);
                    }
                });
            });
        })();
    </script>
@endif
