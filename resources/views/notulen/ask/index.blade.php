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
                    <button type="button" id="notulen-ask-btn" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Tanya
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
                    <p class="mb-0">Jawaban disertai tautan PDF sumber yang dapat dibuka di tab baru.</p>
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
            const btn = document.getElementById('notulen-ask-btn');
            const questionEl = document.getElementById('notulen-question');
            const answerEl = document.getElementById('notulen-answer');
            const sourcesEl = document.getElementById('notulen-sources');

            function escapeHtml(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function formatAnswer(text) {
                const esc = escapeHtml(text);
                return esc.split(/\n{2,}/).map(p => '<p class="mb-2">' + p.replace(/\n/g, '<br>') + '</p>').join('');
            }

            btn.addEventListener('click', function() {
                const question = questionEl.value.trim();
                answerEl.innerHTML = '';
                sourcesEl.innerHTML = '';

                if (!question) {
                    toastr.warning('Silakan masukkan pertanyaan.');
                    return;
                }

                btn.disabled = true;
                answerEl.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Memproses…</p>';

                fetch(askUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        question
                    }),
                }).then(async r => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        throw new Error(data.message || ('HTTP ' + r.status));
                    }
                    return data;
                }).then(data => {
                    answerEl.innerHTML = formatAnswer(data.answer || '');

                    const sources = data.sources || [];
                    if (sources.length) {
                        let html = '<h6 class="text-muted">Sumber PDF:</h6><ul class="list-unstyled mb-0">';
                        sources.forEach(s => {
                            const label = escapeHtml(s.title) + (s.meeting_date ? ' (' + escapeHtml(s
                                .meeting_date) + ')' : '');
                            html += '<li class="mb-1"><a href="' + escapeHtml(s.url) +
                                '" target="_blank" rel="noopener"><i class="fas fa-file-pdf text-danger mr-1"></i>' +
                                label + '</a></li>';
                        });
                        html += '</ul>';
                        sourcesEl.innerHTML = html;
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
