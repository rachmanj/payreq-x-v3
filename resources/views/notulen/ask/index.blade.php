@extends('templates.main')

@section('title_page')
    Notulen AI — Ask
@endsection

@section('breadcrumb_title')
    notulen / ask
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Tanya Notulen Rapat</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="notulen-question">Pertanyaan Anda</label>
                        <textarea id="notulen-question" class="form-control" rows="3" maxlength="4000"
                            placeholder="Contoh: Apa keputusan rapat terakhir tentang anggaran?"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
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
                        <div class="form-group col-md-3">
                            <label for="notulen-date-from">Dari tanggal</label>
                            <input type="date" id="notulen-date-from" class="form-control">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="notulen-date-to">Sampai tanggal</label>
                            <input type="date" id="notulen-date-to" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small mr-2">Contoh:</span>
                        <button type="button" class="btn btn-outline-secondary btn-xs notulen-example mb-1"
                            data-q="Apa keputusan rapat terakhir tentang anggaran?">Keputusan anggaran</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs notulen-example mb-1"
                            data-q="Siapa saja yang hadir pada rapat terakhir?">Daftar hadir</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs notulen-example mb-1"
                            data-q="What action items were assigned in the latest meeting?">Action items</button>
                    </div>

                    <button type="button" id="notulen-ask-btn" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Tanya
                    </button>
                    <button type="button" id="notulen-copy-btn" class="btn btn-outline-secondary d-none">
                        <i class="fas fa-copy mr-1"></i> Salin jawaban
                    </button>
                    <hr>
                    <div id="notulen-answer" style="min-height: 80px;"></div>
                    <div id="notulen-sources" class="mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Tips</h3>
                </div>
                <div class="card-body small">
                    <p class="mb-2">Ajukan pertanyaan tentang isi notulen rapat yang sudah diunggah dan diproses.</p>
                    <p class="mb-2">Gunakan filter dokumen/tanggal untuk mempersempit pencarian.</p>
                    <p class="mb-0">Jawaban disertai tautan PDF sumber dan cuplikan bukti.</p>
                </div>
            </div>
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Riwayat sesi</h3>
                </div>
                <div class="card-body p-0">
                    <ul id="notulen-history" class="list-group list-group-flush small">
                        <li class="list-group-item text-muted">Belum ada pertanyaan di sesi ini.</li>
                    </ul>
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
                let html = '<h6 class="text-muted">Sumber PDF:</h6><ul class="list-unstyled mb-0">';
                sources.forEach(s => {
                    const label = escapeHtml(s.title) + (s.meeting_date ? ' (' + escapeHtml(s.meeting_date) + ')' : '');
                    const score = s.score != null ? ' <span class="badge badge-light">score ' + escapeHtml(String(s.score)) + '</span>' : '';
                    html += '<li class="mb-2"><a href="' + escapeHtml(s.url) +
                        '" target="_blank" rel="noopener"><i class="fas fa-file-pdf text-danger mr-1"></i>' +
                        label + '</a>' + score;
                    if (s.excerpt) {
                        html += '<div class="text-muted small mt-1 border-left pl-2">' + escapeHtml(s.excerpt) + '</div>';
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
                    return '<li class="list-group-item notulen-history-item" style="cursor:pointer" data-idx="' + idx + '">' +
                        '<div class="font-weight-bold">' + escapeHtml(item.q) + '</div>' +
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
                answerEl.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Memproses…</p>';

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
                    answerEl.innerHTML = '<p class="text-danger">' + escapeHtml(err.message) + '</p>';
                }).finally(() => {
                    btn.disabled = false;
                });
            });
        })();
    </script>
@endsection
