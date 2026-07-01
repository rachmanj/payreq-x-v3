@extends('templates.main')

@section('title_page')
    Bank Reconciliation #{{ $bankReconciliation->id }}
@endsection

@section('breadcrumb_title')
    cashier / bank-reconciliation / {{ $bankReconciliation->id }}
@endsection

@section('content')
    @php
        $bankUnmatched = $bankReconciliation->bankStatementLines->where(
            'matched_status',
            \App\Models\BankStatementLine::MATCH_UNMATCHED,
        );
        $sapUnmatched = $bankReconciliation->sapGlLines->where(
            'matched_status',
            \App\Models\SapGlLine::MATCH_UNMATCHED,
        );
        $bankLineGroupId = [];
        $sapLineGroupId = [];
        foreach ($bankReconciliation->matchGroups as $mg) {
            foreach ($mg->matchGroupBankLines as $row) {
                $bankLineGroupId[$row->bank_statement_line_id] = $mg->id;
            }
            foreach ($mg->matchGroupSapLines as $row) {
                $sapLineGroupId[$row->sap_gl_line_id] = $mg->id;
            }
        }
        $canEditMatch = ! $bankReconciliation->isLockedForEditing();
        $canValidate = auth()->user()?->can('validate_bank_reconciliation')
            && ! $bankReconciliation->isPreparer((int) auth()->id());
        $isBalanced = $balanceSummary['is_balanced'] ?? false;
    @endphp

    <div class="row pb-5 mb-5">
        <div class="col-12">
            <div class="mb-3">
                <a href="{{ route('cashier.bank-reconciliation.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Bank Reconciliation
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($bankReconciliation->validation_status === \App\Models\BankReconciliation::VALIDATION_REJECTED && filled($bankReconciliation->rejection_reason))
                <div class="alert alert-warning">
                    <strong>Rejected:</strong> {{ $bankReconciliation->rejection_reason }}
                </div>
            @endif

            @if ($bankReconciliation->status === \App\Models\BankReconciliation::STATUS_FAILED && filled($bankReconciliation->notes))
                <div class="alert alert-danger">
                    <strong>Last error:</strong> {{ $bankReconciliation->notes }}
                    @if (! $bankReconciliation->dokumen_id)
                        <br><small class="mt-1 d-block">No koran PDF is linked to this reconciliation — select one below and click Parse PDF.</small>
                    @endif
                </div>
            @endif

            <div class="card card-outline card-secondary mb-2">
                <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <strong>{{ $bankReconciliation->giro?->acc_no }}</strong>
                        {{ $bankReconciliation->giro?->acc_name }}
                        <span class="badge badge-secondary">{{ $bankReconciliation->giro?->project }}</span>
                        <span class="badge badge-primary">{{ $bankReconciliation->periode?->format('M Y') }}</span>
                        <span class="badge badge-dark" id="br-status">{{ $bankReconciliation->status }}</span>
                        <span class="badge badge-light border">{{ $bankReconciliation->source_mode }}</span>
                        @if ($bankReconciliation->validation_status)
                            <span class="badge badge-warning" id="br-validation-status">{{ $bankReconciliation->validation_status }}</span>
                        @endif
                    </div>
                    <div class="small text-muted" id="br-counts">
                        Bank lines: {{ $bankReconciliation->bankStatementLines->count() }} |
                        SAP lines: {{ $bankReconciliation->sapGlLines->count() }} |
                        Match groups: {{ $bankReconciliation->matchGroups->count() }}
                    </div>
                </div>
            </div>

            <div class="card card-outline card-warning mb-2">
                <div class="card-body py-2 d-flex flex-wrap align-items-center">
                    <span class="mr-3"><strong>Reconciliation totals</strong> (non-excluded lines):</span>
                    <span class="mr-3">Bank net: <strong id="br-bank-net">{{ number_format($balanceSummary['bank_net'], 2) }}</strong></span>
                    <span class="mr-3">Book net: <strong id="br-book-net">{{ number_format($balanceSummary['book_net'], 2) }}</strong></span>
                    <span class="mr-3">Difference: <strong id="br-diff" class="{{ $isBalanced ? 'text-success' : 'text-danger' }}">{{ number_format($balanceSummary['difference'], 2) }}</strong></span>
                    @if ($isBalanced)
                        <span class="badge badge-success">Balanced</span>
                    @else
                        <span class="badge badge-danger">Not balanced — match or exclude lines until difference is 0</span>
                    @endif
                </div>
            </div>

            @if ($canEditMatch)
                <div class="btn-toolbar mb-2 flex-wrap" role="toolbar">
                    @if ($bankReconciliation->dokumen_id)
                        <form action="{{ route('cashier.bank-reconciliation.parse', $bankReconciliation) }}" method="post"
                            class="d-inline mr-1 mb-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Re-parse PDF (AI)</button>
                        </form>
                    @elseif ($bankReconciliation->source_mode === \App\Models\BankReconciliation::SOURCE_AI && $koranDokumens->isNotEmpty())
                        <form action="{{ route('cashier.bank-reconciliation.parse', $bankReconciliation) }}" method="post"
                            class="d-inline-flex align-items-center flex-wrap mr-1 mb-1">
                            @csrf
                            <select name="dokumen_id" class="form-control form-control-sm mr-1 mb-1" style="min-width:220px" required>
                                <option value="">-- select koran PDF --</option>
                                @foreach ($koranDokumens as $doc)
                                    @php
                                        $rawPeriode = $doc->getRawOriginal('periode');
                                        $periodeLabel = $rawPeriode ? \Carbon\Carbon::parse($rawPeriode)->format('Y-m') : '-';
                                        $fname = basename((string) $doc->getRawOriginal('filename1'));
                                    @endphp
                                    <option value="{{ $doc->id }}">{{ $periodeLabel }} — {{ $fname }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary mb-1">Link &amp; Parse PDF (AI)</button>
                        </form>
                    @elseif ($bankReconciliation->source_mode === \App\Models\BankReconciliation::SOURCE_AI)
                        <span class="text-muted small mr-2 mb-1">No koran PDF uploaded for this giro/period — upload on Koran page first.</span>
                    @endif
                    <form action="{{ route('cashier.bank-reconciliation.fetch-sap', $bankReconciliation) }}" method="post"
                        class="d-inline mr-1 mb-1">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Fetch SAP lines</button>
                    </form>
                    <form action="{{ route('cashier.bank-reconciliation.auto-match', $bankReconciliation) }}" method="post"
                        class="d-inline mr-1 mb-1">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">Auto-match</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-info mr-1 mb-1" data-toggle="modal"
                        data-target="#add-bank-line-modal">Add bank line</button>
                    <form action="{{ route('cashier.bank-reconciliation.submit', $bankReconciliation) }}" method="post"
                        class="d-inline mr-1 mb-1"
                        onsubmit="return confirm('Submit this reconciliation for validation? Editing will be locked.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary" id="br-submit-btn" @disabled(! $isBalanced)>
                            Submit for validation
                        </button>
                    </form>
                </div>
            @elseif ($canValidate && $bankReconciliation->validation_status === \App\Models\BankReconciliation::VALIDATION_PENDING)
                <div class="btn-toolbar mb-2 flex-wrap" role="toolbar">
                    <form action="{{ route('cashier.bank-reconciliation.validate', $bankReconciliation) }}" method="post"
                        class="d-inline mr-1 mb-1"
                        onsubmit="return confirm('Validate and mark this reconciliation complete?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">Validate</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-danger mb-1" data-toggle="modal"
                        data-target="#reject-modal">Reject</button>
                </div>
            @endif

            <div class="mb-2">
                <a href="{{ route('cashier.bank-reconciliation.report', $bankReconciliation) }}"
                    class="btn btn-sm btn-default">Report</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($bankReconciliation->matchGroups->isNotEmpty())
                <div class="card mb-3">
                    <div class="card-header py-2"><strong>Match groups</strong></div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th class="text-right">Conf.</th>
                                    <th class="text-right">Bank #</th>
                                    <th class="text-right">SAP #</th>
                                    <th class="text-right">Bank net</th>
                                    <th class="text-right">SAP net</th>
                                    <th class="text-right">Δ</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bankReconciliation->matchGroups as $group)
                                    <tr>
                                        <td>{{ $group->id }}</td>
                                        <td><small>{{ $group->match_type }}</small></td>
                                        <td class="text-right small">{{ number_format((float) $group->confidence_score, 2) }}</td>
                                        <td class="text-right">{{ $group->matchGroupBankLines->count() }}</td>
                                        <td class="text-right">{{ $group->matchGroupSapLines->count() }}</td>
                                        <td class="text-right small">{{ number_format((float) $group->bank_total, 2) }}</td>
                                        <td class="text-right small">{{ number_format((float) $group->sap_total, 2) }}</td>
                                        <td class="text-right small">{{ number_format((float) $group->difference, 2) }}</td>
                                        <td class="text-right">
                                            @if ($canEditMatch)
                                                <form method="post"
                                                    action="{{ route('cashier.bank-reconciliation.unmatch', [$bankReconciliation, $group]) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Remove this match group?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-danger">Unmatch</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-outline card-info">
                        <div class="card-header py-2"><strong>Bank statement (PDF / manual)</strong></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        @if ($canEditMatch)
                                            <th style="width:28px"></th>
                                            <th style="width:60px"></th>
                                        @endif
                                        <th style="width:44px">Grp</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-right">D</th>
                                        <th class="text-right">C</th>
                                        <th>St</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bankReconciliation->bankStatementLines as $line)
                                        @php
                                            $net = (float) $line->debit - (float) $line->credit;
                                            $unmatched = $line->matched_status === \App\Models\BankStatementLine::MATCH_UNMATCHED;
                                            $lowConfidence = $line->is_ai_extracted && $line->ai_confidence !== null && (float) $line->ai_confidence < 0.7;
                                        @endphp
                                        <tr class="{{ $lowConfidence ? 'table-warning' : '' }}">
                                            @if ($canEditMatch)
                                                <td class="align-middle">
                                                    @if ($unmatched)
                                                        <input type="checkbox" class="br-cb-bank"
                                                            value="{{ $line->id }}"
                                                            data-net="{{ $net }}">
                                                    @endif
                                                </td>
                                                <td class="align-middle text-nowrap">
                                                    @if ($unmatched)
                                                        <button type="button" class="btn btn-xs btn-outline-secondary edit-bank-line-btn"
                                                            data-line="{{ e(json_encode([
                                                                'id' => $line->id,
                                                                'transaction_date' => $line->transaction_date?->format('Y-m-d'),
                                                                'value_date' => $line->value_date?->format('Y-m-d'),
                                                                'description' => $line->description,
                                                                'reference' => $line->reference,
                                                                'debit' => $line->debit,
                                                                'credit' => $line->credit,
                                                                'balance' => $line->balance,
                                                                'line_notes' => $line->line_notes,
                                                            ])) }}">Edit</button>
                                                        <form method="post"
                                                            action="{{ route('cashier.bank-reconciliation.lines.destroy', [$bankReconciliation, $line]) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Delete this line?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-xs btn-outline-danger">Del</button>
                                                        </form>
                                                        <button type="button" class="btn btn-xs btn-outline-warning exclude-bank-btn"
                                                            data-line-id="{{ $line->id }}">Excl</button>
                                                    @elseif ($line->matched_status === \App\Models\BankStatementLine::MATCH_EXCLUDED)
                                                        <form method="post"
                                                            action="{{ route('cashier.bank-reconciliation.lines.exclude', [$bankReconciliation, $line]) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-xs btn-outline-info">Include</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="small">
                                                @if (isset($bankLineGroupId[$line->id]))
                                                    <span class="badge badge-light">{{ $bankLineGroupId[$line->id] }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td><small>{{ $line->transaction_date?->format('d/m/Y') }}</small></td>
                                            <td>
                                                <small>{{ \Illuminate\Support\Str::limit($line->description, 48) }}</small>
                                                @if ($line->is_ai_extracted && $line->ai_confidence !== null)
                                                    <br><small class="text-muted">AI {{ number_format((float) $line->ai_confidence * 100, 0) }}%</small>
                                                @endif
                                                @if ($line->exclude_reason)
                                                    <br><small class="text-muted">Excl: {{ $line->exclude_reason }}</small>
                                                @endif
                                            </td>
                                            <td class="text-right small">{{ number_format((float) $line->debit, 2) }}</td>
                                            <td class="text-right small">{{ number_format((float) $line->credit, 2) }}</td>
                                            <td><small>{{ $line->matched_status }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-outline card-success">
                        <div class="card-header py-2"><strong>SAP GL (snapshot)</strong></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        @if ($canEditMatch)
                                            <th style="width:28px"></th>
                                            <th style="width:44px"></th>
                                        @endif
                                        <th style="width:44px">Grp</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-right">D</th>
                                        <th class="text-right">C</th>
                                        <th>St</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bankReconciliation->sapGlLines as $line)
                                        @php
                                            $net = (float) $line->debit - (float) $line->credit;
                                            $unmatched = $line->matched_status === \App\Models\SapGlLine::MATCH_UNMATCHED;
                                        @endphp
                                        <tr>
                                            @if ($canEditMatch)
                                                <td class="align-middle">
                                                    @if ($unmatched)
                                                        <input type="checkbox" class="br-cb-sap"
                                                            value="{{ $line->id }}"
                                                            data-net="{{ $net }}">
                                                    @endif
                                                </td>
                                                <td class="align-middle">
                                                    @if ($unmatched)
                                                        <button type="button" class="btn btn-xs btn-outline-warning exclude-sap-btn"
                                                            data-line-id="{{ $line->id }}">Excl</button>
                                                    @elseif ($line->matched_status === \App\Models\SapGlLine::MATCH_EXCLUDED)
                                                        <form method="post"
                                                            action="{{ route('cashier.bank-reconciliation.sap-lines.exclude', [$bankReconciliation, $line]) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-xs btn-outline-info">Incl</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="small">
                                                @if (isset($sapLineGroupId[$line->id]))
                                                    <span class="badge badge-light">{{ $sapLineGroupId[$line->id] }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td><small>{{ $line->posting_date?->format('d/m/Y') }}</small></td>
                                            <td>
                                                <small>{{ \Illuminate\Support\Str::limit($line->description, 48) }}</small>
                                                @if ($line->exclude_reason)
                                                    <br><small class="text-muted">Excl: {{ $line->exclude_reason }}</small>
                                                @endif
                                            </td>
                                            <td class="text-right small">{{ number_format((float) $line->debit, 2) }}</td>
                                            <td class="text-right small">{{ number_format((float) $line->credit, 2) }}</td>
                                            <td><small>{{ $line->matched_status }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canEditMatch)
        <div class="fixed-bottom bg-light border-top shadow-sm py-2 px-3" style="z-index: 1030;">
            <div class="container-fluid">
                <form id="br-manual-match-form"
                    action="{{ route('cashier.bank-reconciliation.match', $bankReconciliation) }}" method="post"
                    class="row align-items-center">
                    @csrf
                    <div id="br-hidden-fields"></div>
                    <div class="col-md-6 mb-2 mb-md-0">
                        <span class="small text-muted mr-2">Selection — Bank net:</span>
                        <strong id="br-sel-bank">0.00</strong>
                        <span class="small text-muted mx-2">SAP net:</span>
                        <strong id="br-sel-sap">0.00</strong>
                        <span class="small text-muted mx-2">Difference:</span>
                        <strong id="br-sel-diff">0.00</strong>
                        <span class="small text-muted d-none d-lg-inline ml-2">(must be &lt; 0.005)</span>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <button type="submit" id="br-submit-match" class="btn btn-sm btn-primary" disabled>
                            Match selected as group
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="add-bank-line-modal" tabindex="-1">
            <div class="modal-dialog">
                <form method="post" action="{{ route('cashier.bank-reconciliation.lines.store', $bankReconciliation) }}"
                    class="modal-content">
                    @csrf
                    <div class="modal-header py-2">
                        <h5 class="modal-title">Add bank statement line</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('cashier.bank-reconciliation.partials.bank-line-fields')
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Add line</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="edit-bank-line-modal" tabindex="-1">
            <div class="modal-dialog">
                <form method="post" id="edit-bank-line-form" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header py-2">
                        <h5 class="modal-title">Edit bank statement line</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('cashier.bank-reconciliation.partials.bank-line-fields')
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="exclude-line-modal" tabindex="-1">
            <div class="modal-dialog">
                <form method="post" id="exclude-line-form" class="modal-content">
                    @csrf
                    <div class="modal-header py-2">
                        <h5 class="modal-title">Exclude line</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label>Reason</label>
                            <textarea name="exclude_reason" class="form-control form-control-sm" rows="3" required maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm">Exclude</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canValidate)
        <div class="modal fade" id="reject-modal" tabindex="-1">
            <div class="modal-dialog">
                <form method="post" action="{{ route('cashier.bank-reconciliation.reject', $bankReconciliation) }}"
                    class="modal-content">
                    @csrf
                    <div class="modal-header py-2">
                        <h5 class="modal-title">Reject reconciliation</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label>Rejection reason</label>
                            <textarea name="rejection_reason" class="form-control form-control-sm" rows="4" required maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        const statusUrl = @json(route('cashier.bank-reconciliation.status', $bankReconciliation));
        const updateLineUrlTemplate = @json(route('cashier.bank-reconciliation.lines.update', [$bankReconciliation, '__LINE__']));
        const excludeBankUrlTemplate = @json(route('cashier.bank-reconciliation.lines.exclude', [$bankReconciliation, '__LINE__']));
        const excludeSapUrlTemplate = @json(route('cashier.bank-reconciliation.sap-lines.exclude', [$bankReconciliation, '__LINE__']));
        const MANUAL_TOL = 0.005;

        function pollStatus() {
            fetch(statusUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    const el = document.getElementById('br-status');
                    const ct = document.getElementById('br-counts');
                    const vs = document.getElementById('br-validation-status');
                    if (el && data.status) el.textContent = data.status;
                    if (vs && data.validation_status) vs.textContent = data.validation_status;
                    if (ct && data.bank_lines_count !== undefined) {
                        const mg = data.match_groups_count ?? data.matches_count ?? 0;
                        ct.textContent =
                            `Bank lines: ${data.bank_lines_count} | SAP lines: ${data.sap_lines_count} | Match groups: ${mg}`;
                    }
                    if (data.bank_net !== undefined) {
                        document.getElementById('br-bank-net').textContent = Number(data.bank_net).toFixed(2);
                        document.getElementById('br-book-net').textContent = Number(data.book_net).toFixed(2);
                        const diffEl = document.getElementById('br-diff');
                        diffEl.textContent = Number(data.difference).toFixed(2);
                        diffEl.classList.toggle('text-success', data.is_balanced);
                        diffEl.classList.toggle('text-danger', !data.is_balanced);
                        const submitBtn = document.getElementById('br-submit-btn');
                        if (submitBtn) submitBtn.disabled = !data.is_balanced;
                    }
                })
                .catch(() => {});
        }

        setInterval(pollStatus, 8000);

        function sumChecked(selector) {
            let t = 0;
            document.querySelectorAll(selector).forEach(cb => {
                if (cb.checked) t += parseFloat(cb.dataset.net || '0');
            });
            return t;
        }

        function refreshManualTotals() {
            const bankEl = document.getElementById('br-sel-bank');
            const sapEl = document.getElementById('br-sel-sap');
            const diffEl = document.getElementById('br-sel-diff');
            const btn = document.getElementById('br-submit-match');
            if (!bankEl || !sapEl || !diffEl || !btn) return;

            const b = sumChecked('.br-cb-bank');
            const s = sumChecked('.br-cb-sap');
            const d = b - s;
            bankEl.textContent = b.toFixed(2);
            sapEl.textContent = s.toFixed(2);
            diffEl.textContent = d.toFixed(2);

            const nBank = document.querySelectorAll('.br-cb-bank:checked').length;
            const nSap = document.querySelectorAll('.br-cb-sap:checked').length;
            btn.disabled = !(nBank >= 1 && nSap >= 1 && Math.abs(d) < MANUAL_TOL);
        }

        document.querySelectorAll('.br-cb-bank, .br-cb-sap').forEach(cb => {
            cb.addEventListener('change', refreshManualTotals);
        });

        const matchForm = document.getElementById('br-manual-match-form');
        if (matchForm) {
            matchForm.addEventListener('submit', function(e) {
                const hidden = document.getElementById('br-hidden-fields');
                if (!hidden) return;
                hidden.innerHTML = '';
                document.querySelectorAll('.br-cb-bank:checked').forEach(cb => {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'bank_statement_line_ids[]';
                    i.value = cb.value;
                    hidden.appendChild(i);
                });
                document.querySelectorAll('.br-cb-sap:checked').forEach(cb => {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'sap_gl_line_ids[]';
                    i.value = cb.value;
                    hidden.appendChild(i);
                });
            });
            refreshManualTotals();
        }

        document.querySelectorAll('.edit-bank-line-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const line = JSON.parse(this.dataset.line);
                const form = document.getElementById('edit-bank-line-form');
                form.action = updateLineUrlTemplate.replace('__LINE__', line.id);
                ['transaction_date', 'value_date', 'description', 'reference', 'debit', 'credit', 'balance', 'line_notes'].forEach(f => {
                    const el = form.querySelector('[name="' + f + '"]');
                    if (el) el.value = line[f] ?? '';
                });
                $('#edit-bank-line-modal').modal('show');
            });
        });

        function openExcludeModal(url) {
            document.getElementById('exclude-line-form').action = url;
            document.querySelector('#exclude-line-modal textarea[name="exclude_reason"]').value = '';
            $('#exclude-line-modal').modal('show');
        }

        document.querySelectorAll('.exclude-bank-btn').forEach(btn => {
            btn.addEventListener('click', () => openExcludeModal(excludeBankUrlTemplate.replace('__LINE__', btn.dataset.lineId)));
        });
        document.querySelectorAll('.exclude-sap-btn').forEach(btn => {
            btn.addEventListener('click', () => openExcludeModal(excludeSapUrlTemplate.replace('__LINE__', btn.dataset.lineId)));
        });
    </script>
@endsection
