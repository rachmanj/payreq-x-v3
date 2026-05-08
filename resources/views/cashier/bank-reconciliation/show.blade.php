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
    @endphp

    <div class="row">
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
                        Matches: {{ $bankReconciliation->matches->count() }}
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

            <div class="card mb-3">
                <div class="card-header">
                    <strong>Manual match</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('cashier.bank-reconciliation.match', $bankReconciliation) }}" method="post"
                        class="form-inline flex-wrap align-items-end">
                        @csrf
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-1 small">Bank line</label>
                            <select name="bank_statement_line_id" class="form-control form-control-sm" style="min-width:240px">
                                @foreach ($bankUnmatched as $line)
                                    <option value="{{ $line->id }}">
                                        #{{ $line->id }}
                                        {{ $line->transaction_date?->format('Y-m-d') }}
                                        {{ \Illuminate\Support\Str::limit($line->description, 40) }}
                                        D:{{ number_format((float) $line->debit, 2) }}
                                        C:{{ number_format((float) $line->credit, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-1 small">SAP line</label>
                            <select name="sap_gl_line_id" class="form-control form-control-sm" style="min-width:240px">
                                @foreach ($sapUnmatched as $line)
                                    <option value="{{ $line->id }}">
                                        #{{ $line->id }}
                                        {{ $line->posting_date?->format('Y-m-d') }}
                                        {{ \Illuminate\Support\Str::limit($line->description, 40) }}
                                        D:{{ number_format((float) $line->debit, 2) }}
                                        C:{{ number_format((float) $line->credit, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary mb-2"
                            @disabled($bankUnmatched->isEmpty() || $sapUnmatched->isEmpty())>
                            Match selected
                        </button>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-outline card-info">
                        <div class="card-header py-2"><strong>Bank statement (PDF / AI)</strong></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-right">D</th>
                                        <th class="text-right">C</th>
                                        <th>St</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bankReconciliation->bankStatementLines as $line)
                                        <tr>
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
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-right">D</th>
                                        <th class="text-right">C</th>
                                        <th>St</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bankReconciliation->sapGlLines as $line)
                                        <tr>
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
@endsection

@section('scripts')
    <script>
        const statusUrl = @json(route('cashier.bank-reconciliation.status', $bankReconciliation));

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
                        ct.textContent =
                            `Bank lines: ${data.bank_lines_count} | SAP lines: ${data.sap_lines_count} | Matches: ${data.matches_count}`;
                    }
                })
                .catch(() => {});
        }

        setInterval(pollStatus, 8000);
    </script>
@endsection
