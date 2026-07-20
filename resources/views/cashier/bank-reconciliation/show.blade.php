@extends('templates.main')

@section('title_page')
    Bank Reconciliation #{{ $bankReconciliation->id }}
@endsection

@section('breadcrumb_title')
    cashier / bank-reconciliation / {{ $bankReconciliation->id }}
@endsection

@push('styles')
    <style>
        .br-match-bar {
            left: 250px;
            right: 0;
            width: auto;
            z-index: 1030;
        }

        .br-match-bar .br-match-bar-inner {
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        body.sidebar-collapse .br-match-bar {
            left: 4.6rem;
        }

        @media (max-width: 991.98px) {
            .br-match-bar,
            body.sidebar-collapse .br-match-bar {
                left: 0;
            }
        }
    </style>
@endpush

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
        $isReconciled = $statement['is_reconciled'] ?? false;
        $isIncomplete = $statement['incomplete'] ?? false;
        $categoryLabels = [
            \App\Models\BankStatementLine::TYPE_CREDIT_NOT_BOOKED => 'Credit / interest not booked',
            \App\Models\BankStatementLine::TYPE_CHARGE_NOT_BOOKED => 'Bank charge not booked',
            \App\Models\BankStatementLine::TYPE_BANK_ERROR => 'Bank error',
            \App\Models\SapGlLine::TYPE_DEPOSIT_IN_TRANSIT => 'Deposit in transit',
            \App\Models\SapGlLine::TYPE_OUTSTANDING_PAYMENT => 'Outstanding payment',
            \App\Models\SapGlLine::TYPE_BOOK_ERROR => 'Book error',
        ];
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

            <div id="br-failure-banner"
                class="alert alert-danger alert-dismissible {{ $bankReconciliation->status === \App\Models\BankReconciliation::STATUS_FAILED && filled($bankReconciliation->notes) ? '' : 'd-none' }}"
                role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>Last error:</strong> <span id="br-failure-notes">{{ $bankReconciliation->notes }}</span>
                @if (! $bankReconciliation->dokumen_id)
                    <br><small class="mt-1 d-block">No koran PDF is linked to this reconciliation — select one below and click Parse PDF.</small>
                @endif
            </div>

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
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap align-items-center mb-1">
                        <span class="mr-3"><strong>Reconciliation statement</strong></span>
                        @if ($isIncomplete)
                            <span class="badge badge-secondary">Incomplete — enter closing balances</span>
                        @elseif ($isReconciled)
                            <span class="badge badge-success" id="br-reconciled-badge">Reconciled</span>
                        @else
                            <span class="badge badge-danger" id="br-reconciled-badge">Not reconciled</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap align-items-center small">
                        <span class="mr-3">Closing bank: <strong id="br-closing-bank">{{ $statement['closing_balance_bank'] !== null ? number_format($statement['closing_balance_bank'], 2) : '—' }}</strong></span>
                        <span class="mr-3">Closing book: <strong id="br-closing-book">{{ $statement['closing_balance_book'] !== null ? number_format($statement['closing_balance_book'], 2) : '—' }}</strong></span>
                        <span class="mr-3">Adjusted bank: <strong id="br-adjusted-bank">{{ $statement['adjusted_bank'] !== null ? number_format($statement['adjusted_bank'], 2) : '—' }}</strong></span>
                        <span class="mr-3">Adjusted book: <strong id="br-adjusted-book">{{ $statement['adjusted_book'] !== null ? number_format($statement['adjusted_book'], 2) : '—' }}</strong></span>
                        <span class="mr-3">Unexplained: <strong id="br-unexplained" class="{{ $isReconciled ? 'text-success' : 'text-danger' }}">{{ $statement['unexplained_difference'] !== null ? number_format($statement['unexplained_difference'], 2) : '—' }}</strong></span>
                    </div>
                    @if (! empty($statement['diagnostic']))
                        <div class="small text-danger mt-1" id="br-diagnostic">{{ $statement['diagnostic'] }}</div>
                    @else
                        <div class="small text-danger mt-1 d-none" id="br-diagnostic"></div>
                    @endif
                    <div class="small text-muted mt-1">
                        Movement totals (legacy): Bank net <span id="br-bank-net">{{ number_format($balanceSummary['bank_net'], 2) }}</span>
                        | Book net <span id="br-book-net">{{ number_format($balanceSummary['book_net'], 2) }}</span>
                        | Diff <span id="br-diff">{{ number_format($balanceSummary['difference'], 2) }}</span>
                    </div>
                </div>
            </div>

            @if ($canEditMatch)
                <div class="card card-outline card-info mb-2">
                    <div class="card-header py-2">
                        <strong>Opening / closing balances</strong>
                        <small class="text-muted ml-2">Required for submit. AI parse / SAP fetch fill these when available.</small>
                    </div>
                    <div class="card-body py-2">
                        <form action="{{ route('cashier.bank-reconciliation.balances.update', $bankReconciliation) }}" method="post"
                            class="form-row align-items-end">
                            @csrf
                            @method('PUT')
                            <div class="form-group col-md-3 mb-2">
                                <label class="small mb-0">Opening — Bank</label>
                                <input type="number" step="0.01" name="opening_balance_bank" class="form-control form-control-sm"
                                    value="{{ old('opening_balance_bank', $bankReconciliation->opening_balance_bank) }}">
                            </div>
                            <div class="form-group col-md-3 mb-2">
                                <label class="small mb-0">Closing — Bank</label>
                                <input type="number" step="0.01" name="closing_balance_bank" class="form-control form-control-sm"
                                    value="{{ old('closing_balance_bank', $bankReconciliation->closing_balance_bank) }}">
                            </div>
                            <div class="form-group col-md-3 mb-2">
                                <label class="small mb-0">Opening — Book</label>
                                <input type="number" step="0.01" name="opening_balance_book" class="form-control form-control-sm"
                                    value="{{ old('opening_balance_book', $bankReconciliation->opening_balance_book) }}">
                            </div>
                            <div class="form-group col-md-3 mb-2">
                                <label class="small mb-0">Closing — Book</label>
                                <input type="number" step="0.01" name="closing_balance_book" class="form-control form-control-sm"
                                    value="{{ old('closing_balance_book', $bankReconciliation->closing_balance_book) }}">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-info">Save balances</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($canEditMatch)
                <div class="btn-toolbar mb-2 flex-wrap" role="toolbar">
                    @if ($bankReconciliation->dokumen_id)
                        <form action="{{ route('cashier.bank-reconciliation.parse', $bankReconciliation) }}" method="post"
                            class="d-inline mr-1 mb-1"
                            onsubmit="return confirm('Re-parse will replace all bank statement lines and clear related match groups. Continue?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Re-parse PDF (AI)</button>
                        </form>
                    @elseif ($bankReconciliation->source_mode === \App\Models\BankReconciliation::SOURCE_AI && $koranDokumens->isNotEmpty())
                        <form action="{{ route('cashier.bank-reconciliation.parse', $bankReconciliation) }}" method="post"
                            class="d-inline-flex align-items-center flex-wrap mr-1 mb-1"
                            onsubmit="return confirm('Parse will replace all bank statement lines and clear related match groups. Continue?');">
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
                        class="d-inline mr-1 mb-1"
                        onsubmit="return confirm('Fetch SAP will replace all SAP lines and clear related match groups. Continue?');">
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
                        <button type="submit" class="btn btn-sm btn-primary" id="br-submit-btn" @disabled(! $isReconciled)>
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
                                                        <button type="button" class="btn btn-xs btn-outline-primary classify-bank-btn"
                                                            data-line-id="{{ $line->id }}"
                                                            data-type="{{ $line->reconciling_type }}">Type</button>
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
                                                @if ($unmatched)
                                                    <br><small class="text-info">{{ $categoryLabels[$line->reconcilingCategory()] ?? $line->reconcilingCategory() }}</small>
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
                                                <td class="align-middle text-nowrap">
                                                    @if ($unmatched)
                                                        <button type="button" class="btn btn-xs btn-outline-warning exclude-sap-btn"
                                                            data-line-id="{{ $line->id }}">Excl</button>
                                                        <button type="button" class="btn btn-xs btn-outline-primary classify-sap-btn"
                                                            data-line-id="{{ $line->id }}"
                                                            data-type="{{ $line->reconciling_type }}">Type</button>
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
                                                @if ($unmatched)
                                                    <br><small class="text-info">{{ $categoryLabels[$line->reconcilingCategory()] ?? $line->reconcilingCategory() }}</small>
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
        <div class="fixed-bottom bg-light border-top shadow-sm py-2 px-3 br-match-bar">
            <div class="br-match-bar-inner">
                <form id="br-manual-match-form"
                    action="{{ route('cashier.bank-reconciliation.match', $bankReconciliation) }}" method="post"
                    class="row align-items-center justify-content-center">
                    @csrf
                    <div id="br-hidden-fields"></div>
                    <div class="col-lg-7 col-md-8 mb-2 mb-md-0 text-center text-md-left">
                        <span class="small text-muted mr-2">Selection — Bank net:</span>
                        <strong id="br-sel-bank">0.00</strong>
                        <span class="small text-muted mx-2">SAP net:</span>
                        <strong id="br-sel-sap">0.00</strong>
                        <span class="small text-muted mx-2">Difference:</span>
                        <strong id="br-sel-diff">0.00</strong>
                        <span class="small text-muted d-none d-lg-inline ml-2">(bank + SAP must be &lt; 0.005)</span>
                    </div>
                    <div class="col-lg-3 col-md-4 text-center text-md-right">
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

        <div class="modal fade" id="classify-line-modal" tabindex="-1">
            <div class="modal-dialog">
                <form method="post" id="classify-line-form" class="modal-content">
                    @csrf
                    <div class="modal-header py-2">
                        <h5 class="modal-title">Classify reconciling item</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label>Reconciling type</label>
                            <select name="reconciling_type" id="classify-type-select" class="form-control form-control-sm">
                                <option value="">Auto (from amount sign)</option>
                            </select>
                            <small class="form-text text-muted">Annotation only — does not change adjusted balances.</small>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
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
        const classifyBankUrlTemplate = @json(route('cashier.bank-reconciliation.lines.classify', [$bankReconciliation, '__LINE__']));
        const classifySapUrlTemplate = @json(route('cashier.bank-reconciliation.sap-lines.classify', [$bankReconciliation, '__LINE__']));
        @php
            $bankTypeOptions = [
                ['value' => \App\Models\BankStatementLine::TYPE_CREDIT_NOT_BOOKED, 'label' => 'Credit / interest not booked'],
                ['value' => \App\Models\BankStatementLine::TYPE_CHARGE_NOT_BOOKED, 'label' => 'Bank charge not booked'],
                ['value' => \App\Models\BankStatementLine::TYPE_BANK_ERROR, 'label' => 'Bank error'],
            ];
            $sapTypeOptions = [
                ['value' => \App\Models\SapGlLine::TYPE_DEPOSIT_IN_TRANSIT, 'label' => 'Deposit in transit'],
                ['value' => \App\Models\SapGlLine::TYPE_OUTSTANDING_PAYMENT, 'label' => 'Outstanding payment'],
                ['value' => \App\Models\SapGlLine::TYPE_BOOK_ERROR, 'label' => 'Book error'],
            ];
        @endphp
        const bankTypeOptions = @json($bankTypeOptions);
        const sapTypeOptions = @json($sapTypeOptions);
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
                        if (diffEl) diffEl.textContent = Number(data.difference).toFixed(2);
                    }
                    if (data.adjusted_bank !== undefined) {
                        const fmt = (v) => (v === null || v === undefined) ? '—' : Number(v).toFixed(2);
                        const setText = (id, val) => {
                            const node = document.getElementById(id);
                            if (node) node.textContent = val;
                        };
                        setText('br-adjusted-bank', fmt(data.adjusted_bank));
                        setText('br-adjusted-book', fmt(data.adjusted_book));
                        const unexplainedEl = document.getElementById('br-unexplained');
                        if (unexplainedEl) {
                            unexplainedEl.textContent = fmt(data.unexplained_difference);
                            unexplainedEl.classList.toggle('text-success', !!data.is_reconciled);
                            unexplainedEl.classList.toggle('text-danger', !data.is_reconciled);
                        }
                        const badge = document.getElementById('br-reconciled-badge');
                        if (badge) {
                            if (data.incomplete) {
                                badge.className = 'badge badge-secondary';
                                badge.textContent = 'Incomplete — enter closing balances';
                            } else if (data.is_reconciled) {
                                badge.className = 'badge badge-success';
                                badge.textContent = 'Reconciled';
                            } else {
                                badge.className = 'badge badge-danger';
                                badge.textContent = 'Not reconciled';
                            }
                        }
                        const diagnostic = document.getElementById('br-diagnostic');
                        if (diagnostic) {
                            if (data.diagnostic) {
                                diagnostic.textContent = data.diagnostic;
                                diagnostic.classList.remove('d-none');
                            } else {
                                diagnostic.textContent = '';
                                diagnostic.classList.add('d-none');
                            }
                        }
                        const submitBtn = document.getElementById('br-submit-btn');
                        if (submitBtn) submitBtn.disabled = !data.is_reconciled;
                    }
                    const failureBanner = document.getElementById('br-failure-banner');
                    const failureNotes = document.getElementById('br-failure-notes');
                    if (failureBanner && failureNotes) {
                        if (data.status === 'failed' && data.notes) {
                            failureNotes.textContent = data.notes;
                            failureBanner.classList.remove('d-none');
                        } else if (data.status && data.status !== 'failed') {
                            failureBanner.classList.add('d-none');
                        }
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
            const d = b + s;
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

        function openClassifyModal(url, options, currentType) {
            const form = document.getElementById('classify-line-form');
            const select = document.getElementById('classify-type-select');
            if (!form || !select) return;
            form.action = url;
            select.innerHTML = '<option value="">Auto (from amount sign)</option>';
            options.forEach(opt => {
                const o = document.createElement('option');
                o.value = opt.value;
                o.textContent = opt.label;
                if (currentType && currentType === opt.value) o.selected = true;
                select.appendChild(o);
            });
            $('#classify-line-modal').modal('show');
        }

        document.querySelectorAll('.classify-bank-btn').forEach(btn => {
            btn.addEventListener('click', () => openClassifyModal(
                classifyBankUrlTemplate.replace('__LINE__', btn.dataset.lineId),
                bankTypeOptions,
                btn.dataset.type || ''
            ));
        });
        document.querySelectorAll('.classify-sap-btn').forEach(btn => {
            btn.addEventListener('click', () => openClassifyModal(
                classifySapUrlTemplate.replace('__LINE__', btn.dataset.lineId),
                sapTypeOptions,
                btn.dataset.type || ''
            ));
        });
    </script>
@endsection
