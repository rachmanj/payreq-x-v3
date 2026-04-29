@props(['compliance' => null])

@php
    $c = $compliance;
@endphp

@if ($c && ($c['show_banner'] ?? false))
    <div data-pcbc-banner
        class="alert alert-{{ $c['variant'] === 'success' ? 'success' : ($c['variant'] === 'warning' ? 'warning' : ($c['variant'] === 'danger' ? 'danger' : 'info')) }} alert-dismissible fade show border-0 shadow-sm mb-3"
        role="alert">
        <div class="d-flex flex-wrap align-items-start">
            <div class="mr-2 mt-1">
                <i
                    class="fas fa-{{ $c['variant'] === 'info' ? 'info-circle' : ($c['variant'] === 'danger' ? 'exclamation-triangle' : 'exclamation-circle') }}"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-end mb-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="PCBC banner language">
                        <button type="button" class="btn btn-outline-secondary pcbc-banner-lang-btn active" data-lang="en"
                            aria-pressed="true">EN</button>
                        <button type="button" class="btn btn-outline-secondary pcbc-banner-lang-btn" data-lang="id"
                            aria-pressed="false">ID</button>
                    </div>
                </div>

                <div class="pcbc-banner-lang-en">
                    <h5 class="alert-heading mb-1">{{ $c['title'] }}</h5>
                    <p class="mb-2">{{ $c['message'] }}</p>
                    @if (!empty($c['current_week_label']))
                        <p class="mb-0 small text-muted">
                            <strong>Current week ({{ config('pcbc_compliance.timezone') }}):</strong>
                            {{ $c['current_week_label'] }}
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
                                        <span class="text-nowrap">:</span> {{ $c['weeks'][$key]['range'] }}
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                    @if (!($c['exempt'] ?? false) && ($c['sanctioned'] ?? false))
                        <a href="{{ route('cashier.pcbc.index', ['page' => 'upload']) }}" class="btn btn-sm btn-light mt-2"
                            title="Go to PCBC upload">
                            Go to PCBC upload
                        </a>
                    @endif
                </div>

                <div class="pcbc-banner-lang-id d-none">
                    <h5 class="alert-heading mb-1" lang="id">{{ $c['title_id'] ?? $c['title'] }}</h5>
                    <p class="mb-2" lang="id">{{ $c['message_id'] ?? $c['message'] }}</p>
                    @if (!empty($c['current_week_label']))
                        <p class="mb-0 small text-muted" lang="id">
                            <strong>Minggu berjalan ({{ config('pcbc_compliance.timezone') }}):</strong>
                            {{ $c['current_week_label'] }}
                        </p>
                    @endif
                    @if (isset($c['weeks']) && is_array($c['weeks']) && !($c['exempt'] ?? false))
                        <ul class="list-unstyled small mb-0 mt-2 pl-0" lang="id">
                            @foreach (['current', 'w1', 'w2'] as $key)
                                @if (!empty($c['weeks'][$key]))
                                    <li>
                                        <i
                                            class="fas fa-{{ $c['weeks'][$key]['has_upload'] ? 'check text-success' : 'times text-danger' }}"></i>
                                        <strong>{{ $c['weeks'][$key]['label_id'] ?? $c['weeks'][$key]['label'] }}</strong>
                                        <span class="text-nowrap">:</span> {{ $c['weeks'][$key]['range'] }}
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                    @if (!($c['exempt'] ?? false) && ($c['sanctioned'] ?? false))
                        <a href="{{ route('cashier.pcbc.index', ['page' => 'upload']) }}" class="btn btn-sm btn-light mt-2"
                            title="Buka unggah PCBC">
                            Buka unggah PCBC
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
