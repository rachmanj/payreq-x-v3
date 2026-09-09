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

        .vj-show #notulen-history .notulen-history-item {
            cursor: pointer;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.15s ease;
        }

        .vj-show #notulen-history .notulen-history-item:hover {
            background-color: rgba(0, 123, 255, 0.04);
        }

        .vj-show #notulen-history .notulen-history-item:last-child {
            border-bottom: none;
        }

        .vj-show #notulen-sources .notulen-source-excerpt {
            border-left: 3px solid #dee2e6;
            padding-left: 0.65rem;
            margin-top: 0.35rem;
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
                        <div class="form-group">
                            <label for="notulen-question">Pertanyaan Anda</label>
                            <textarea id="notulen-question" class="form-control" rows="3" maxlength="4000"
                                placeholder="Contoh: Apa keputusan rapat terakhir tentang anggaran?"></textarea>
                        </div>

                        <div class="vj-form-panel">
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-md-0">
                                    <label for="notulen-meeting-ids">Batasi ke dokumen (opsional)</label>
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
                            <button type="button" id="notulen-ask-btn" class="vj-btn vj-btn-primary">
                                <i class="fas fa-paper-plane"></i> Tanya
                            </button>
                            <button type="button" id="notulen-copy-btn" class="vj-btn vj-action-print d-none">
                                <i class="fas fa-copy"></i> Salin jawaban
                            </button>
                        </div>

                        <div class="vj-note notulen-answer-wrap mb-0">
                            <div id="notulen-answer"></div>
                        </div>
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
            const streamingEnabled = @json($streamingEnabled);
            const btn = document.getElementById('notulen-ask-btn');
            const copyBtn = document.getElementById('notulen-copy-btn');
            const questionEl = document.getElementById('notulen-question');
            const answerEl = document.getElementById('notulen-answer');
            const sourcesEl = document.getElementById('notulen-sources');
            const historyEl = document.getElementById('notulen-history');
            const meetingIdsEl = document.getElementById('notulen-meeting-ids');
            const dateFromEl = document.getElementById('notulen-date-from');
            const dateToEl = document.getElementById('notulen-date-to');
            let lastAnswer = '';
            const history = [];

            if (window.jQuery && meetingIdsEl && typeof jQuery.fn.select2 === 'function') {
                jQuery(meetingIdsEl).select2({
                    width: '100%',
                    placeholder: meetingIdsEl.getAttribute('data-placeholder')
                });
            }

            function escapeHtml(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function formatAnswer(text) {
                const esc = escapeHtml(text);
                return esc
                    .split(/\n{2,}/)
                    .map(block => {
                        const lines = block.split('\n');
                        const isList = lines.every(l => /^\s*([-*]|\d+\.)\s+/.test(l));
                        if (isList) {
                            const items = lines.map(l => '<li>' + l.replace(/^\s*([-*]|\d+\.)\s+/, '') + '</li>').join('');
                            return '<ul class="mb-2 pl-3">' + items + '</ul>';
                        }
                        return '<p class="mb-2">' + lines.join('<br>') + '</p>';
                    })
                    .join('');
            }

            function renderSources(sources) {
                if (!sources || !sources.length) {
                    sourcesEl.innerHTML = '';
                    return;
                }
                let html = '<h6 class="text-muted mb-2"><i class="fas fa-file-pdf text-danger mr-1"></i> Sumber PDF</h6><ul class="list-unstyled mb-0">';
                sources.forEach(s => {
                    const label = escapeHtml(s.title) + (s.meeting_date ? ' (' + escapeHtml(s.meeting_date) + ')' : '');
                    const score = s.score != null ? ' <span class="vj-chip vj-chip-neutral">score ' + escapeHtml(String(s.score)) + '</span>' : '';
                    html += '<li class="mb-3"><a href="' + escapeHtml(s.url) +
                        '" target="_blank" rel="noopener" class="font-weight-medium"><i class="fas fa-file-pdf text-danger mr-1"></i>' +
                        label + '</a>' + score;
                    if (s.excerpt) {
                        html += '<div class="text-muted small notulen-source-excerpt">' + escapeHtml(s.excerpt) + '</div>';
                    }
                    html += '</li>';
                });
                html += '</ul>';
                sourcesEl.innerHTML = html;
            }

            function pushHistory(question, answer) {
                history.unshift({
                    q: question,
                    a: answer
                });
                if (history.length > 8) history.pop();
                historyEl.innerHTML = history.map((item, idx) => {
                    return '<li class="notulen-history-item px-3 py-2" data-idx="' + idx + '">' +
                        '<div class="font-weight-bold text-truncate">' + escapeHtml(item.q) + '</div>' +
                        '<div class="text-muted text-truncate">' + escapeHtml(item.a) + '</div></li>';
                }).join('');
            }

            function collectPayload(question) {
                const meetingIds = (window.jQuery && typeof jQuery.fn.select2 === 'function')
                    ? (jQuery(meetingIdsEl).val() || [])
                    : Array.from(meetingIdsEl.selectedOptions).map(o => o.value);
                const payload = {
                    question
                };
                if (meetingIds.length) payload.meeting_ids = meetingIds.map(Number);
                if (dateFromEl.value) payload.date_from = dateFromEl.value;
                if (dateToEl.value) payload.date_to = dateToEl.value;
                return payload;
            }

            document.querySelectorAll('.notulen-example').forEach(el => {
                el.addEventListener('click', function() {
                    questionEl.value = this.getAttribute('data-q') || '';
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
                lastAnswer = entry.a;
                answerEl.innerHTML = formatAnswer(entry.a);
                copyBtn.classList.remove('d-none');
            });

            copyBtn.addEventListener('click', function() {
                if (!lastAnswer) return;
                navigator.clipboard.writeText(lastAnswer).then(() => {
                    if (window.toastr) toastr.success('Jawaban disalin.');
                });
            });

            btn.addEventListener('click', function() {
                const question = questionEl.value.trim();
                answerEl.innerHTML = '';
                sourcesEl.innerHTML = '';
                copyBtn.classList.add('d-none');
                lastAnswer = '';

                if (!question) {
                    toastr.warning('Silakan masukkan pertanyaan.');
                    return;
                }

                btn.disabled = true;
                answerEl.innerHTML = '<p class="text-muted mb-0"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses…</p>';

                const payload = collectPayload(question);

                fetch(askUrl, {
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
                    if (!r.ok) {
                        throw new Error(data.message || ('HTTP ' + r.status));
                    }
                    return data;
                }).then(data => {
                    lastAnswer = data.answer || '';
                    answerEl.innerHTML = formatAnswer(lastAnswer);
                    renderSources(data.sources || []);
                    if (lastAnswer) {
                        copyBtn.classList.remove('d-none');
                        pushHistory(question, lastAnswer);
                    }
                }).catch(err => {
                    answerEl.innerHTML = '<div class="vj-alert vj-alert-danger mb-0"><i class="fas fa-exclamation-circle"></i><div>' + escapeHtml(err.message) + '</div></div>';
                }).finally(() => {
                    btn.disabled = false;
                });
            });
        })();
    </script>
@endsection
