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
        $canEditMatch = $bankReconciliation->status !== \App\Models\BankReconciliation::STATUS_COMPLETED;
    @endphp

    <div class="row pb-5 mb-5">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($bankReconciliation->status === \App\Models\BankReconciliation::STATUS_FAILED && filled($bankReconciliation->notes))
                <div class="alert alert-danger">
                    <strong>Last error:</strong> {{ $bankReconciliation->notes }}
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
                    </div>
                    <div class="small text-muted" id="br-counts">
                        Bank lines: {{ $bankReconciliation->bankStatementLines->count() }} |
                        SAP lines: {{ $bankReconciliation->sapGlLines->count() }} |
                        Match groups: {{ $bankReconciliation->matchGroups->count() }}
                    </div>
                </div>
            </div>

            <div class="btn-toolbar mb-2 flex-wrap" role="toolbar">
                <form action="{{ route('cashier.bank-reconciliation.parse', $bankReconciliation) }}" method="post"
                    class="d-inline mr-1 mb-1">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">Re-parse PDF (AI)</button>
                </form>
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
                <a href="{{ route('cashier.bank-reconciliation.report', $bankReconciliation) }}"
                    class="btn btn-sm btn-default mr-1 mb-1">Report</a>
                <form action="{{ route('cashier.bank-reconciliation.complete', $bankReconciliation) }}" method="post"
                    class="d-inline mb-1"
                    onsubmit="return confirm('Mark reconciliation as complete?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger"
                        @disabled($bankReconciliation->status === \App\Models\BankReconciliation::STATUS_COMPLETED)>
                        Complete
                    </button>
                </form>
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
                        <div class="card-header py-2"><strong>Bank statement (PDF / AI)</strong></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        @if ($canEditMatch)
                                            <th style="width:28px"></th>
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
                                        @endphp
                                        <tr>
                                            @if ($canEditMatch)
                                                <td class="align-middle">
                                                    @if ($unmatched)
                                                        <input type="checkbox" class="br-cb-bank"
                                                            value="{{ $line->id }}"
                                                            data-net="{{ $net }}">
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
                                            <td><small>{{ \Illuminate\Support\Str::limit($line->description, 48) }}</small></td>
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
                                            @endif
                                            <td class="small">
                                                @if (isset($sapLineGroupId[$line->id]))
                                                    <span class="badge badge-light">{{ $sapLineGroupId[$line->id] }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td><small>{{ $line->posting_date?->format('d/m/Y') }}</small></td>
                                            <td><small>{{ \Illuminate\Support\Str::limit($line->description, 48) }}</small></td>
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
    @endif
@endsection

@section('scripts')
    <script>
        const statusUrl = @json(route('cashier.bank-reconciliation.status', $bankReconciliation));
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
                    if (el && data.status) el.textContent = data.status;
                    if (ct && data.bank_lines_count !== undefined) {
                        const mg = data.match_groups_count ?? data.matches_count ?? 0;
                        ct.textContent =
                            `Bank lines: ${data.bank_lines_count} | SAP lines: ${data.sap_lines_count} | Match groups: ${mg}`;
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
    </script>
@endsection
