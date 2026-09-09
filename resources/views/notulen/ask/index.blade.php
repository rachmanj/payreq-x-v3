@extends('templates.main')

@section('title_page')
    Notulen AI — Ask
@endsection

@section('breadcrumb_title')
    notulen / ask
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <style>
        .vj-show .notulen-answer-wrap {
            display: block;
            min-height: 80px;
        }

        .vj-show .notulen-answer-wrap #notulen-answer {
            width: 100%;
        }

        .vj-show #notulen-history .notulen-history-item,
        .vj-show #notulen-db-history .notulen-db-history-item {
            cursor: pointer;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.15s ease;
        }

        .vj-show #notulen-history .notulen-history-item:hover,
        .vj-show #notulen-db-history .notulen-db-history-item:hover {
            background-color: rgba(0, 123, 255, 0.04);
        }

        .vj-show #notulen-history .notulen-history-item:last-child,
        .vj-show #notulen-db-history .notulen-db-history-item:last-child {
            border-bottom: none;
        }

        .vj-show #notulen-sources .notulen-source-excerpt {
            border-left: 3px solid #dee2e6;
            padding-left: 0.65rem;
            margin-top: 0.35rem;
        }

        .vj-show .notulen-question-wrap {
            position: relative;
        }

        .vj-show .notulen-char-counter {
            position: absolute;
            right: 0.5rem;
            bottom: 0.35rem;
            font-size: 0.7rem;
            color: #6c757d;
            pointer-events: none;
        }

        .vj-show #notulen-question {
            resize: none;
            overflow-y: hidden;
            min-height: calc(1.5em + 0.75rem + 2px);
            padding-bottom: 1.4rem;
        }

        .vj-show .markdown-body p {
            margin-bottom: 0.65rem;
        }

        .vj-show .markdown-body p:last-child {
            margin-bottom: 0;
        }

        .vj-show .markdown-body h5,
        .vj-show .markdown-body h6 {
            margin-top: 0.75rem;
            margin-bottom: 0.35rem;
            font-weight: 600;
        }

        .vj-show .markdown-body h5 {
            font-size: 1rem;
        }

        .vj-show .markdown-body h6 {
            font-size: 0.9rem;
        }

        .vj-show .markdown-body ul,
        .vj-show .markdown-body ol {
            margin-bottom: 0.65rem;
            padding-left: 1.25rem;
        }

        .vj-show .markdown-body blockquote {
            border-left: 3px solid #dee2e6;
            padding-left: 0.75rem;
            margin: 0 0 0.65rem;
            color: #6c757d;
        }

        .vj-show .markdown-body code {
            background: #f1f3f5;
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
            font-size: 0.875em;
        }

        .vj-show .markdown-body .notulen-cite {
            display: inline-block;
            font-size: 0.65rem;
            line-height: 1;
            padding: 0.1rem 0.3rem;
            margin: 0 0.05rem;
            border-radius: 999px;
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
            vertical-align: super;
        }

        .vj-show .markdown-body .notulen-cite:hover {
            background: #dee2e6;
            color: #212529;
        }

        .vj-show .notulen-stream-cursor {
            display: inline-block;
            width: 2px;
            height: 1em;
            background: #007bff;
            margin-left: 1px;
            vertical-align: text-bottom;
            animation: notulen-blink 1s step-end infinite;
        }

        @keyframes notulen-blink {
            50% { opacity: 0; }
        }

        .vj-show #notulen-db-history {
            max-height: 220px;
            overflow-y: auto;
        }

        .vj-show .notulen-suggestion-chip {
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-comments"></i> Tanya Notulen Rapat
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-2">
                            <label for="notulen-question">Pertanyaan Anda</label>
                            <div class="notulen-question-wrap">
                                <textarea id="notulen-question" class="form-control" rows="1" maxlength="4000"
                                    placeholder="Contoh: Apa keputusan rapat terakhir tentang anggaran?"></textarea>
                                <span id="notulen-char-counter" class="notulen-char-counter">0/4000</span>
                            </div>
                        </div>

                        <div class="vj-form-panel">
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-md-0">
                                    <label for="notulen-meeting-ids">
                                        Batasi ke dokumen (opsional)
                                        <span id="notulen-meeting-badge" class="vj-chip vj-chip-neutral d-none ml-1"></span>
                                    </label>
                                    <select id="notulen-meeting-ids" class="form-control select2" multiple
                                        data-placeholder="Semua dokumen terproses">
                                        @foreach ($meetings as $meeting)
                                            <option value="{{ $meeting->id }}">
                                                {{ $meeting->title }}
                                                @if ($meeting->meeting_date)
                                                    ({{ $meeting->meeting_date->format('Y-m-d') }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3 mb-md-0">
                                    <label for="notulen-date-from">Dari tanggal</label>
                                    <input type="date" id="notulen-date-from" class="form-control">
                                </div>
                                <div class="form-group col-md-3 mb-0">
                                    <label for="notulen-date-to">Sampai tanggal</label>
                                    <input type="date" id="notulen-date-to" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="text-muted small d-block mb-2">Contoh:</span>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button"
                                    class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-print notulen-example"
                                    data-q="Apa keputusan rapat terakhir tentang anggaran?">Keputusan anggaran</button>
                                <button type="button"
                                    class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-print notulen-example"
                                    data-q="Siapa saja yang hadir pada rapat terakhir?">Daftar hadir</button>
                                <button type="button"
                                    class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-print notulen-example"
                                    data-q="What action items were assigned in the latest meeting?">Action items</button>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" id="notulen-ask-btn" class="vj-btn vj-btn-primary" disabled>
                                <i class="fas fa-paper-plane"></i> Tanya
                            </button>
                            <button type="button" id="notulen-stop-btn" class="vj-btn vj-btn-danger d-none">
                                <i class="fas fa-stop"></i> Stop
                            </button>
                            <button type="button" id="notulen-copy-btn" class="vj-btn vj-action-print d-none">
                                <i class="fas fa-copy"></i> Salin jawaban
                            </button>
                            <button type="button" id="notulen-suggestions-btn" class="vj-btn vj-action-print d-none">
                                <i class="fas fa-lightbulb"></i> Saran pertanyaan lanjutan
                            </button>
                        </div>

                        <div class="vj-note notulen-answer-wrap mb-0">
                            <div id="notulen-answer"></div>
                        </div>
                        <div id="notulen-meta" class="small text-muted mt-2"></div>
                        <div id="notulen-suggestions" class="mt-2"></div>
                        <div id="notulen-sources" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 d-flex flex-column gap-3">
                <div class="vj-note mb-0">
                    <i class="fas fa-info-circle"></i>
                    <div class="small">
                        <p class="mb-2">Ajukan pertanyaan tentang isi notulen rapat yang sudah diunggah dan diproses.</p>
                        <p class="mb-2">Gunakan filter dokumen/tanggal untuk mempersempit pencarian.</p>
                        <p class="mb-0">Jawaban disertai tautan PDF sumber dan cuplikan bukti.</p>
                    </div>
                </div>
                <div class="card card-outline card-info mb-0">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-database"></i> Riwayat pertanyaan saya
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul id="notulen-db-history" class="list-unstyled mb-0 small">
                            <li class="text-muted px-3 py-2">Memuat riwayat…</li>
                        </ul>
                    </div>
                </div>
                <div class="card card-outline card-secondary mb-0">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-history"></i> Riwayat sesi
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul id="notulen-history" class="list-unstyled mb-0 small">
                            <li class="text-muted px-3 py-2">Belum ada pertanyaan di sesi ini.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const askUrl = @json(route('notulen.ask'));
            const historyUrl = @json(route('notulen.ask.history'));
            const historyShowBase = @json(url('/notulen/ask/history'));
            const suggestionsUrl = @json(route('notulen.ask.suggestions'));
            const streamingEnabled = @json($streamingEnabled);

            const btn = document.getElementById('notulen-ask-btn');
            const stopBtn = document.getElementById('notulen-stop-btn');
            const copyBtn = document.getElementById('notulen-copy-btn');
            const suggestionsBtn = document.getElementById('notulen-suggestions-btn');
            const questionEl = document.getElementById('notulen-question');
            const charCounterEl = document.getElementById('notulen-char-counter');
            const answerEl = document.getElementById('notulen-answer');
            const metaEl = document.getElementById('notulen-meta');
            const suggestionsEl = document.getElementById('notulen-suggestions');
            const sourcesEl = document.getElementById('notulen-sources');
            const historyEl = document.getElementById('notulen-history');
            const dbHistoryEl = document.getElementById('notulen-db-history');
            const meetingIdsEl = document.getElementById('notulen-meeting-ids');
            const meetingBadgeEl = document.getElementById('notulen-meeting-badge');
            const dateFromEl = document.getElementById('notulen-date-from');
            const dateToEl = document.getElementById('notulen-date-to');

            let lastAnswer = '';
            let lastSources = [];
            let lastMeta = null;
            let abortController = null;
            let isBusy = false;
            const history = [];

            if (window.jQuery && meetingIdsEl && typeof jQuery.fn.select2 === 'function') {
                jQuery(meetingIdsEl).select2({
                    width: '100%',
                    placeholder: meetingIdsEl.getAttribute('data-placeholder')
                }).on('change', updateMeetingBadge);
            }

            function escapeHtml(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function formatInline(text, sourceCount) {
                let out = text;
                out = out.replace(/`([^`\n]+)`/g, '<code>$1</code>');
                out = out.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
                out = out.replace(/(?<!\*)\*([^*\n]+)\*(?!\*)/g, '<em>$1</em>');
                out = out.replace(/\[(\d+)\]/g, function(_, num) {
                    const n = parseInt(num, 10);
                    if (n >= 1 && n <= sourceCount) {
                        return '<a href="#notulen-src-' + n + '" class="notulen-cite">' + n + '</a>';
                    }
                    return '[' + num + ']';
                });
                return out;
            }

            function renderMarkdown(text, sourceCount) {
                sourceCount = sourceCount || 0;
                if (!text) {
                    return '<div class="markdown-body"></div>';
                }

                const escaped = escapeHtml(text);
                const lines = escaped.split('\n');
                let html = '';
                let i = 0;

                while (i < lines.length) {
                    const line = lines[i];

                    if (line.trim() === '') {
                        i++;
                        continue;
                    }

                    if (/^&gt;\s?/.test(line)) {
                        const quoteLines = [];
                        while (i < lines.length && (/^&gt;\s?/.test(lines[i]) || lines[i].trim() === '')) {
                            if (lines[i].trim() !== '') {
                                quoteLines.push(lines[i].replace(/^&gt;\s?/, ''));
                            }
                            i++;
                        }
                        html += '<blockquote>' + quoteLines.map(l => formatInline(l, sourceCount)).join('<br>') + '</blockquote>';
                        continue;
                    }

                    if (/^####\s+/.test(line)) {
                        html += '<h6>' + formatInline(line.replace(/^####\s+/, ''), sourceCount) + '</h6>';
                        i++;
                        continue;
                    }

                    if (/^###\s+/.test(line)) {
                        html += '<h5>' + formatInline(line.replace(/^###\s+/, ''), sourceCount) + '</h5>';
                        i++;
                        continue;
                    }

                    if (/^\s*[-*]\s+/.test(line)) {
                        let listHtml = '<ul>';
                        while (i < lines.length) {
                            const m = lines[i].match(/^\s*[-*]\s+(.*)$/);
                            if (!m) break;
                            listHtml += '<li>' + formatInline(m[1], sourceCount) + '</li>';
                            i++;
                        }
                        listHtml += '</ul>';
                        html += listHtml;
                        continue;
                    }

                    if (/^\s*\d+\.\s+/.test(line)) {
                        let listHtml = '<ol>';
                        while (i < lines.length) {
                            const m = lines[i].match(/^\s*\d+\.\s+(.*)$/);
                            if (!m) break;
                            listHtml += '<li>' + formatInline(m[1], sourceCount) + '</li>';
                            i++;
                        }
                        listHtml += '</ol>';
                        html += listHtml;
                        continue;
                    }

                    const paraLines = [];
                    while (i < lines.length && lines[i].trim() !== '' &&
                        !/^&gt;\s?/.test(lines[i]) &&
                        !/^#{3,4}\s+/.test(lines[i]) &&
                        !/^\s*[-*]\s+/.test(lines[i]) &&
                        !/^\s*\d+\.\s+/.test(lines[i])) {
                        paraLines.push(lines[i]);
                        i++;
                    }
                    html += '<p>' + paraLines.map(l => formatInline(l, sourceCount)).join('<br>') + '</p>';
                }

                return '<div class="markdown-body">' + html + '</div>';
            }

            function renderSources(sources) {
                lastSources = sources || [];
                if (!sources || !sources.length) {
                    sourcesEl.innerHTML = '';
                    return;
                }
                let html = '<h6 class="text-muted mb-2"><i class="fas fa-file-pdf text-danger mr-1"></i> Sumber PDF</h6><ol class="list-unstyled mb-0">';
                sources.forEach((s, idx) => {
                    const num = idx + 1;
                    const label = escapeHtml(s.title) + (s.meeting_date ? ' (' + escapeHtml(s.meeting_date) + ')' : '');
                    const score = s.score != null ? ' <span class="vj-chip vj-chip-neutral">score ' + escapeHtml(String(s.score)) + '</span>' : '';
                    html += '<li id="notulen-src-' + num + '" class="mb-3"><span class="text-muted mr-1">' + num + '.</span>' +
                        '<a href="' + escapeHtml(s.url) +
                        '" target="_blank" rel="noopener" class="font-weight-medium"><i class="fas fa-file-pdf text-danger mr-1"></i>' +
                        label + '</a>' + score;
                    if (s.excerpt) {
                        html += '<div class="text-muted small notulen-source-excerpt">' + escapeHtml(s.excerpt) + '</div>';
                    }
                    html += '</li>';
                });
                html += '</ol>';
                sourcesEl.innerHTML = html;
            }

            function formatLatency(ms) {
                if (ms == null || isNaN(ms)) return '';
                const sec = ms / 1000;
                return sec.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' dtk';
            }

            function renderMeta(meta) {
                lastMeta = meta;
                if (!meta) {
                    metaEl.innerHTML = '';
                    return;
                }
                const parts = [];
                if (meta.model) parts.push('Model: ' + escapeHtml(meta.model));
                if (meta.latency_ms != null) parts.push('Durasi: ' + formatLatency(meta.latency_ms));
                if (meta.sources && meta.sources.length) {
                    parts.push('Sumber: ' + meta.sources.length);
                } else if (lastSources.length) {
                    parts.push('Sumber: ' + lastSources.length);
                }
                if (meta.top_score != null) parts.push('Skor teratas: ' + escapeHtml(String(meta.top_score)));
                metaEl.innerHTML = parts.length ? parts.join(' · ') : '';
            }

            function pushHistory(question, answer) {
                history.unshift({ q: question, a: answer });
                if (history.length > 8) history.pop();
                renderSessionHistory();
            }

            function renderSessionHistory() {
                if (!history.length) {
                    historyEl.innerHTML = '<li class="text-muted px-3 py-2">Belum ada pertanyaan di sesi ini.</li>';
                    return;
                }
                historyEl.innerHTML = history.map((item, idx) => {
                    return '<li class="notulen-history-item px-3 py-2" data-idx="' + idx + '">' +
                        '<div class="font-weight-bold text-truncate">' + escapeHtml(item.q) + '</div>' +
                        '<div class="text-muted text-truncate">' + escapeHtml(item.a) + '</div></li>';
                }).join('');
            }

            function formatDbDate(iso) {
                if (!iso) return '';
                try {
                    const d = new Date(iso);
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                } catch (e) {
                    return iso;
                }
            }

            function loadDbHistory() {
                fetch(historyUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                }).then(r => r.json()).then(data => {
                    const items = data.items || [];
                    if (!items.length) {
                        dbHistoryEl.innerHTML = '<li class="text-muted px-3 py-2">Belum ada riwayat tersimpan.</li>';
                        return;
                    }
                    dbHistoryEl.innerHTML = items.map(item => {
                        const badge = item.not_found
                            ? ' <span class="vj-chip vj-chip-warning ml-1">tidak ditemukan</span>' : '';
                        return '<li class="notulen-db-history-item px-3 py-2" data-id="' + item.id + '">' +
                            '<div class="font-weight-bold text-truncate">' + escapeHtml(item.question) + badge + '</div>' +
                            '<div class="text-muted">' + escapeHtml(formatDbDate(item.created_at)) + '</div></li>';
                    }).join('');
                }).catch(() => {
                    dbHistoryEl.innerHTML = '<li class="text-muted px-3 py-2">Gagal memuat riwayat.</li>';
                });
            }

            function showAnswerFromData(data, question) {
                lastAnswer = data.answer || '';
                answerEl.innerHTML = renderMarkdown(lastAnswer, (data.sources || []).length);
                renderSources(data.sources || []);
                renderMeta({
                    model: data.model,
                    latency_ms: data.latency_ms,
                    top_score: data.top_score,
                    sources: data.sources,
                });
                suggestionsEl.innerHTML = '';
                if (lastAnswer) {
                    copyBtn.classList.remove('d-none');
                    suggestionsBtn.classList.remove('d-none');
                    if (question) pushHistory(question, lastAnswer);
                }
            }

            function collectPayload(question) {
                const meetingIds = (window.jQuery && typeof jQuery.fn.select2 === 'function')
                    ? (jQuery(meetingIdsEl).val() || [])
                    : Array.from(meetingIdsEl.selectedOptions).map(o => o.value);
                const payload = { question };
                if (meetingIds.length) payload.meeting_ids = meetingIds.map(Number);
                if (dateFromEl.value) payload.date_from = dateFromEl.value;
                if (dateToEl.value) payload.date_to = dateToEl.value;
                return payload;
            }

            function updateAskButtonState() {
                btn.disabled = isBusy || !questionEl.value.trim();
            }

            function autoGrowTextarea() {
                questionEl.style.height = 'auto';
                const lineHeight = parseInt(window.getComputedStyle(questionEl).lineHeight, 10) || 24;
                const maxHeight = lineHeight * 6;
                questionEl.style.height = Math.min(questionEl.scrollHeight, maxHeight) + 'px';
                questionEl.style.overflowY = questionEl.scrollHeight > maxHeight ? 'auto' : 'hidden';
            }

            function updateCharCounter() {
                charCounterEl.textContent = questionEl.value.length + '/4000';
            }

            function updateMeetingBadge() {
                const meetingIds = (window.jQuery && typeof jQuery.fn.select2 === 'function')
                    ? (jQuery(meetingIdsEl).val() || [])
                    : Array.from(meetingIdsEl.selectedOptions).map(o => o.value);
                if (meetingIds.length) {
                    meetingBadgeEl.textContent = meetingIds.length + ' dokumen';
                    meetingBadgeEl.classList.remove('d-none');
                } else {
                    meetingBadgeEl.classList.add('d-none');
                }
            }

            function setBusy(busy) {
                isBusy = busy;
                updateAskButtonState();
                stopBtn.classList.toggle('d-none', !busy);
            }

            function resetOutput() {
                answerEl.innerHTML = '';
                metaEl.innerHTML = '';
                sourcesEl.innerHTML = '';
                suggestionsEl.innerHTML = '';
                copyBtn.classList.add('d-none');
                suggestionsBtn.classList.add('d-none');
                lastAnswer = '';
                lastSources = [];
                lastMeta = null;
            }

            function showStreamWaiting() {
                answerEl.innerHTML = '<p class="text-muted mb-0"><i class="fas fa-pen mr-1"></i> Sedang menulis…</p>';
            }

            function showStreamText(text, streaming) {
                const esc = escapeHtml(text);
                const cursor = streaming ? '<span class="notulen-stream-cursor"></span>' : '';
                answerEl.innerHTML = '<div class="markdown-body"><p class="mb-0" style="white-space:pre-wrap;">' + esc + cursor + '</p></div>';
            }

            function parseSseBuffer(buffer) {
                const events = [];
                const parts = buffer.split('\n\n');
                const remainder = parts.pop() || '';
                parts.forEach(block => {
                    const lines = block.split('\n');
                    let eventName = 'message';
                    let dataStr = '';
                    lines.forEach(line => {
                        if (line.startsWith('event:')) eventName = line.slice(6).trim();
                        else if (line.startsWith('data:')) dataStr += line.slice(5).trim();
                    });
                    if (dataStr) {
                        try {
                            events.push({ event: eventName, data: JSON.parse(dataStr) });
                        } catch (e) { /* skip malformed */ }
                    }
                });
                return { events, remainder };
            }

            async function askNonStream(question, payload) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses…';
                answerEl.innerHTML = '<p class="text-muted mb-0"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses…</p>';

                const response = await fetch(askUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                    signal: abortController ? abortController.signal : undefined,
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || ('HTTP ' + response.status));
                }
                showAnswerFromData(data, question);
                loadDbHistory();
            }

            async function askStream(question, payload) {
                payload.stream = 1;
                showStreamWaiting();

                const response = await fetch(askUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                    signal: abortController.signal,
                });

                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message || ('HTTP ' + response.status));
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let streamText = '';
                let streamMeta = null;
                let streamSources = [];
                let gotFirstToken = false;
                let abortedByUser = false;

                try {
                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        const parsed = parseSseBuffer(buffer);
                        buffer = parsed.remainder;

                        parsed.events.forEach(ev => {
                            if (ev.event === 'meta') {
                                streamMeta = ev.data;
                                streamSources = ev.data.sources || [];
                                renderSources(streamSources);
                            } else if (ev.event === 'delta') {
                                if (!gotFirstToken) gotFirstToken = true;
                                streamText += ev.data.text || '';
                                showStreamText(streamText, true);
                            } else if (ev.event === 'result' || ev.event === 'done') {
                                const finalData = ev.data;
                                streamText = finalData.answer || streamText;
                                streamSources = finalData.sources || streamSources;
                                showAnswerFromData(finalData, question);
                                loadDbHistory();
                            } else if (ev.event === 'error') {
                                throw new Error(ev.data.message || 'Terjadi kesalahan saat streaming.');
                            }
                        });
                    }
                } catch (err) {
                    if (err.name === 'AbortError') {
                        abortedByUser = true;
                    } else {
                        throw err;
                    }
                }

                if (abortedByUser) {
                    if (streamText) {
                        lastAnswer = streamText;
                        answerEl.innerHTML = renderMarkdown(streamText, streamSources.length);
                        if (streamSources.length) renderSources(streamSources);
                        renderMeta(streamMeta || {});
                        copyBtn.classList.remove('d-none');
                        pushHistory(question, streamText);
                        answerEl.insertAdjacentHTML('beforeend',
                            '<div class="vj-alert vj-alert-warning mt-2 mb-0"><i class="fas fa-hand-paper"></i><div>Dihentikan pengguna.</div></div>');
                    } else {
                        answerEl.innerHTML = '<div class="vj-alert vj-alert-warning mb-0"><i class="fas fa-hand-paper"></i><div>Dihentikan pengguna.</div></div>';
                    }
                } else if (streamText && !lastAnswer) {
                    showAnswerFromData({
                        answer: streamText,
                        sources: streamSources,
                        model: streamMeta ? streamMeta.model : null,
                        top_score: streamMeta ? streamMeta.top_score : null,
                        latency_ms: null,
                    }, question);
                }
            }

            async function submitAsk() {
                const question = questionEl.value.trim();
                resetOutput();

                if (!question) {
                    toastr.warning('Silakan masukkan pertanyaan.');
                    return;
                }

                abortController = new AbortController();
                setBusy(true);

                const payload = collectPayload(question);
                const useStream = streamingEnabled;

                try {
                    if (useStream) {
                        try {
                            await askStream(question, payload);
                        } catch (streamErr) {
                            if (streamErr.name === 'AbortError') {
                                throw streamErr;
                            }
                            resetOutput();
                            await askNonStream(question, collectPayload(question));
                        }
                    } else {
                        await askNonStream(question, payload);
                    }
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        const partial = lastAnswer || '';
                        if (partial) {
                            answerEl.innerHTML = renderMarkdown(partial, lastSources.length) +
                                '<div class="vj-alert vj-alert-danger mt-2 mb-0"><i class="fas fa-exclamation-circle"></i><div>' +
                                escapeHtml(err.message) + '</div></div>';
                        } else {
                            answerEl.innerHTML = '<div class="vj-alert vj-alert-danger mb-0"><i class="fas fa-exclamation-circle"></i><div>' +
                                escapeHtml(err.message) + '</div></div>';
                        }
                    }
                } finally {
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Tanya';
                    setBusy(false);
                    abortController = null;
                }
            }

            function loadHistoryItem(id) {
                fetch(historyShowBase + '/' + id, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                }).then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                }).then(data => {
                    questionEl.value = data.question || '';
                    autoGrowTextarea();
                    updateCharCounter();
                    updateAskButtonState();
                    showAnswerFromData(data, data.question);
                    if (data.question && data.answer) {
                        pushHistory(data.question, data.answer);
                    }
                }).catch(err => {
                    if (window.toastr) toastr.error('Gagal memuat riwayat: ' + err.message);
                });
            }

            function fetchSuggestions() {
                if (!lastAnswer) return;
                suggestionsBtn.disabled = true;
                const payload = collectPayload(questionEl.value.trim());
                payload.answer = lastAnswer;

                fetch(suggestionsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                }).then(async r => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(data.message || ('HTTP ' + r.status));
                    return data;
                }).then(data => {
                    const items = data.suggestions || [];
                    if (items.length < 1) {
                        if (window.toastr) toastr.info('Tidak ada saran pertanyaan.');
                        return;
                    }
                    suggestionsEl.innerHTML = '<div class="small text-muted mb-1">Saran pertanyaan:</div><div id="notulen-suggestion-chips" class="d-flex flex-wrap gap-1"></div>';
                    const chipsWrap = document.getElementById('notulen-suggestion-chips');
                    items.forEach(function(q) {
                        const chip = document.createElement('button');
                        chip.type = 'button';
                        chip.className = 'vj-action-item vj-action-item-btn vj-action-item-xs vj-action-print notulen-suggestion-chip';
                        chip.textContent = q;
                        chip.addEventListener('click', function() {
                            questionEl.value = q;
                            autoGrowTextarea();
                            updateCharCounter();
                            updateAskButtonState();
                            submitAsk();
                        });
                        chipsWrap.appendChild(chip);
                    });
                }).catch(() => {
                    if (window.toastr) toastr.info('Gagal memuat saran pertanyaan.');
                }).finally(() => {
                    suggestionsBtn.disabled = false;
                });
            }

            document.querySelectorAll('.notulen-example').forEach(el => {
                el.addEventListener('click', function() {
                    questionEl.value = this.getAttribute('data-q') || '';
                    autoGrowTextarea();
                    updateCharCounter();
                    updateAskButtonState();
                    questionEl.focus();
                });
            });

            historyEl.addEventListener('click', function(e) {
                const item = e.target.closest('.notulen-history-item');
                if (!item) return;
                const idx = Number(item.getAttribute('data-idx'));
                const entry = history[idx];
                if (!entry) return;
                questionEl.value = entry.q;
                autoGrowTextarea();
                updateCharCounter();
                updateAskButtonState();
                lastAnswer = entry.a;
                answerEl.innerHTML = renderMarkdown(entry.a, lastSources.length);
                copyBtn.classList.remove('d-none');
            });

            dbHistoryEl.addEventListener('click', function(e) {
                const item = e.target.closest('.notulen-db-history-item');
                if (!item) return;
                loadHistoryItem(item.getAttribute('data-id'));
            });

            copyBtn.addEventListener('click', function() {
                if (!lastAnswer) return;
                navigator.clipboard.writeText(lastAnswer).then(() => {
                    if (window.toastr) toastr.success('Jawaban disalin.');
                });
            });

            btn.addEventListener('click', submitAsk);

            stopBtn.addEventListener('click', function() {
                if (abortController) abortController.abort();
            });

            suggestionsBtn.addEventListener('click', fetchSuggestions);

            questionEl.addEventListener('input', function() {
                autoGrowTextarea();
                updateCharCounter();
                updateAskButtonState();
            });

            questionEl.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!isBusy && questionEl.value.trim()) submitAsk();
                }
            });

            autoGrowTextarea();
            updateCharCounter();
            updateAskButtonState();
            updateMeetingBadge();
            loadDbHistory();
        })();
    </script>
@endsection
