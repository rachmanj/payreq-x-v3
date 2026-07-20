@extends('templates.main')

@section('title_page')
    New Bank Reconciliation
@endsection

@section('breadcrumb_title')
    cashier / bank-reconciliation / create
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Create reconciliation</h3>
                </div>
                <form action="{{ route('cashier.bank-reconciliation.store') }}" method="post" id="br-create-form">
                    @csrf
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                @if (session('existing_bank_reconciliation_id'))
                                    <p class="mb-0 mt-2">
                                        <a href="{{ route('cashier.bank-reconciliation.show', session('existing_bank_reconciliation_id')) }}"
                                            class="alert-link font-weight-bold">
                                            Open existing bank reconciliation #{{ session('existing_bank_reconciliation_id') }}
                                        </a>
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Source mode</label>
                            <div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="source_ai" name="source_mode" value="ai"
                                        class="custom-control-input"
                                        @checked(old('source_mode', $prefill['source_mode'] ?? 'ai') === 'ai')>
                                    <label class="custom-control-label" for="source_ai">AI (parse PDF)</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="source_manual" name="source_mode" value="manual"
                                        class="custom-control-input"
                                        @checked(old('source_mode', $prefill['source_mode'] ?? 'ai') === 'manual')>
                                    <label class="custom-control-label" for="source_manual">Manual (key in lines)</label>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                AI mode extracts lines from the uploaded koran PDF. Manual mode skips parsing — you enter bank lines yourself (you can still edit AI lines later if needed).
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Giro (bank account)</label>
                            <select name="giro_id" id="giro_id" class="form-control form-control-sm" required>
                                <option value="">-- select --</option>
                                @foreach ($giros as $giro)
                                    <option value="{{ $giro->id }}"
                                        @selected((int) old('giro_id', $prefill['giro_id'] ?? 0) === $giro->id)>
                                        {{ $giro->acc_no }} — {{ $giro->acc_name }}
                                        ({{ $giro->project }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Period (month)</label>
                            <input type="month" name="periode" id="periode" class="form-control form-control-sm"
                                value="{{ old('periode', isset($prefill['periode']) && $prefill['periode'] ? \Carbon\Carbon::parse($prefill['periode'])->format('Y-m') : '') }}"
                                required>
                            <small class="text-muted">Must match the uploaded koran month when using AI mode.</small>
                        </div>

                        <div class="form-group" id="dokumen-group">
                            <label>Koran dokumen (PDF uploaded) <span id="dokumen-required-mark">*</span></label>
                            <select name="dokumen_id" id="dokumen_id" class="form-control form-control-sm"
                                @disabled((int) old('giro_id', $prefill['giro_id'] ?? 0) === 0 || ! $hasPeriodeFilter)>
                                @if ((int) old('giro_id', $prefill['giro_id'] ?? 0) === 0)
                                    <option value="">-- select giro first --</option>
                                @elseif (! $hasPeriodeFilter)
                                    <option value="">-- select period first --</option>
                                @elseif ($dokumens->isEmpty())
                                    <option value="">-- no koran uploaded for this giro and period --</option>
                                @else
                                    <option value="">-- select koran PDF --</option>
                                    @foreach ($dokumens as $doc)
                                        @php
                                            $rawPeriode = $doc->getRawOriginal('periode');
                                            $periodeLabel = $rawPeriode
                                                ? \Carbon\Carbon::parse($rawPeriode)->format('Y-m')
                                                : '-';
                                            $fname = basename((string) $doc->getRawOriginal('filename1'));
                                        @endphp
                                        <option value="{{ $doc->id }}"
                                            @selected((int) old('dokumen_id', $prefill['dokumen_id'] ?? 0) === $doc->id)>
                                            {{ $periodeLabel }} — {{ $fname }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="text-muted d-block mt-1">Only koran files for the selected giro and period are listed.</small>
                        </div>

                        <p class="text-muted small mb-0" id="mode-help-ai">
                            Submitting will queue AI parsing of the PDF and SAP GL fetch for this period.
                            Ensure <code>OPENROUTER_API_KEY</code> and SAP B1 Service Layer are configured.
                        </p>
                        <p class="text-muted small mb-0 d-none" id="mode-help-manual">
                            Submitting will fetch SAP GL lines only. You will add bank statement lines manually on the review screen.
                        </p>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-sm">Start reconciliation</button>
                        <a href="{{ route('cashier.bank-reconciliation.index') }}" class="btn btn-default btn-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function syncSourceModeUi() {
            const isManual = document.getElementById('source_manual')?.checked;
            const dokumen = document.getElementById('dokumen_id');
            const mark = document.getElementById('dokumen-required-mark');
            document.getElementById('mode-help-ai')?.classList.toggle('d-none', isManual);
            document.getElementById('mode-help-manual')?.classList.toggle('d-none', !isManual);
            if (dokumen) {
                dokumen.required = !isManual;
            }
            if (mark) {
                mark.style.display = isManual ? 'none' : '';
            }
        }

        document.querySelectorAll('input[name="source_mode"]').forEach(el => {
            el.addEventListener('change', syncSourceModeUi);
        });
        syncSourceModeUi();

        document.getElementById('giro_id')?.addEventListener('change', function() {
            const url = new URL(window.location.href);
            const id = this.value;
            const periode = document.getElementById('periode')?.value;
            const sourceMode = document.querySelector('input[name="source_mode"]:checked')?.value;

            if (id) {
                url.searchParams.set('giro_id', id);
            } else {
                url.searchParams.delete('giro_id');
            }

            if (periode) {
                url.searchParams.set('periode', periode);
            } else {
                url.searchParams.delete('periode');
            }

            if (sourceMode) {
                url.searchParams.set('source_mode', sourceMode);
            }

            url.searchParams.delete('dokumen_id');
            window.location.href = url.toString();
        });

        document.getElementById('periode')?.addEventListener('change', function() {
            const url = new URL(window.location.href);
            const giroId = document.getElementById('giro_id')?.value;
            const sourceMode = document.querySelector('input[name="source_mode"]:checked')?.value;

            if (giroId) {
                url.searchParams.set('giro_id', giroId);
            }

            if (this.value) {
                url.searchParams.set('periode', this.value);
            } else {
                url.searchParams.delete('periode');
            }

            if (sourceMode) {
                url.searchParams.set('source_mode', sourceMode);
            }

            url.searchParams.delete('dokumen_id');
            window.location.href = url.toString();
        });
    </script>
@endsection
