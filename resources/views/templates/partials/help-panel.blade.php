@can('akses_help')
    <div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true" data-backdrop="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Help</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="helpTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="help-howto-tab" data-toggle="tab" href="#help-howto" role="tab">How-to</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="help-feedback-tab" data-toggle="tab" href="#help-feedback" role="tab">Report / request</a>
                        </li>
                    </ul>
                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="help-howto" role="tabpanel">
                            <div class="form-group">
                                <label for="help-message">Your question</label>
                                <textarea id="help-message" class="form-control" rows="2" maxlength="4000" placeholder="How do I …?"></textarea>
                            </div>
                            <button type="button" id="help-ask-btn" class="btn btn-primary">Ask</button>
                            <hr>
                            <div id="help-answer" class="small" style="max-height: 360px; overflow-y: auto;"></div>
                            <div id="help-sources" class="small text-muted mt-2"></div>
                        </div>
                        <div class="tab-pane fade" id="help-feedback" role="tabpanel">
                            <div class="form-group">
                                <label>Type</label>
                                <select id="help-fb-type" class="form-control">
                                    <option value="bug">Bug</option>
                                    <option value="feature">Feature request</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="help-fb-title">Title</label>
                                <input type="text" id="help-fb-title" class="form-control" maxlength="512">
                            </div>
                            <div class="form-group">
                                <label for="help-fb-body">Description</label>
                                <textarea id="help-fb-body" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="help-fb-steps">Steps to reproduce (optional)</label>
                                <textarea id="help-fb-steps" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="button" id="help-feedback-btn" class="btn btn-primary">Submit</button>
                            <span id="help-feedback-status" class="small text-muted ml-2"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const askUrl = @json(route('help.ask'));
                const fbUrl = @json(route('help.feedback'));

                function escapeHtml(s) {
                    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                }

                function formatAnswer(text) {
                    const esc = escapeHtml(text || '');
                    return esc.split(/\n{2,}/).map(p => '<p>' + p.replace(/\n/g,'<br>') + '</p>').join('');
                }

                document.getElementById('help-ask-btn').addEventListener('click', function() {
                    const msg = document.getElementById('help-message').value.trim();
                    const out = document.getElementById('help-answer');
                    const src = document.getElementById('help-sources');
                    out.innerHTML = '';
                    src.innerHTML = '';
                    if (!msg) {
                        toastr.warning('Please enter a question.');
                        return;
                    }
                    out.textContent = 'Thinking…';
                    fetch(askUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ message: msg, locale: 'auto' }),
                    }).then(async r => {
                        const data = await r.json().catch(() => ({}));
                        if (!r.ok) {
                            throw new Error(data.message || ('HTTP ' + r.status));
                        }
                        return data;
                    }).then(data => {
                        out.innerHTML = formatAnswer(data.answer || '');
                        if (Array.isArray(data.sources) && data.sources.length) {
                            src.innerHTML = '<strong>Sources</strong><ul>' + data.sources.map(s => '<li>' + escapeHtml((s.title || '') + (s.heading ? ' — ' + s.heading : '')) + '</li>').join('') + '</ul>';
                        }
                    }).catch(e => {
                        out.textContent = '';
                        toastr.error(e.message || 'Request failed');
                    });
                });

                document.getElementById('help-feedback-btn').addEventListener('click', function() {
                    const type = document.getElementById('help-fb-type').value;
                    const title = document.getElementById('help-fb-title').value.trim();
                    const body = document.getElementById('help-fb-body').value.trim();
                    const steps = document.getElementById('help-fb-steps').value.trim();
                    const stat = document.getElementById('help-feedback-status');
                    stat.textContent = '';
                    if (!title || !body) {
                        toastr.warning('Title and description are required.');
                        return;
                    }
                    fetch(fbUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            type: type,
                            title: title,
                            body: body,
                            steps_to_reproduce: steps || null,
                        }),
                    }).then(async r => {
                        const data = await r.json().catch(() => ({}));
                        if (!r.ok) {
                            throw new Error(data.message || ('HTTP ' + r.status));
                        }
                        return data;
                    }).then(() => {
                        stat.textContent = 'Sent. Thank you.';
                        toastr.success('Feedback submitted.');
                    }).catch(e => {
                        toastr.error(e.message || 'Request failed');
                    });
                });
            })();
        </script>
    @endpush
@endcan
