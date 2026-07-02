@extends('templates.main')

@section('title_page')
    Bank Reconciliation Report #{{ $bankReconciliation->id }}
@endsection

@section('breadcrumb_title')
    cashier / bank-reconciliation / {{ $bankReconciliation->id }} / report
@endsection

@push('styles')
    <style>
        .br-report-print-header {
            display: none;
        }

        .floating-buttons-br {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .floating-btn-br {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .floating-btn-br:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            color: #fff;
        }

        .floating-btn-br.print-btn {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .floating-btn-br.back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-decoration: none;
        }

        @media print {
            @page {
                margin: 1cm;
                size: A4;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print,
            .main-header,
            .main-sidebar,
            .content-header,
            .main-footer,
            .floating-buttons-br,
            [data-pcbc-banner] {
                display: none !important;
            }

            .content-wrapper,
            .content,
            .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .br-report-print-header {
                display: block !important;
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
                border-bottom: 2px solid #333;
            }

            .br-report-card {
                box-shadow: none !important;
                border: none !important;
            }

            .br-report-card .card-header {
                display: none !important;
            }

            .table-bordered th,
            .table-bordered td {
                border: 1px solid #000 !important;
            }

            .text-success,
            .text-danger {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $isValidated = $bankReconciliation->validation_status === \App\Models\BankReconciliation::VALIDATION_VALIDATED;
    @endphp

    <div class="floating-buttons-br no-print">
        <button type="button" class="floating-btn-br print-btn" onclick="window.print()" title="Print report">
            <i class="fas fa-print"></i>
        </button>
        <a href="{{ route('cashier.bank-reconciliation.index') }}" class="floating-btn-br back-btn" title="Back to list">
            <i class="fas fa-list"></i>
        </a>
    </div>

    <div class="row">
        <div class="col-lg-10" id="bank-reconciliation-report">
            @if (session('success'))
                <div class="alert alert-success no-print">{{ session('success') }}</div>
            @endif

            <div class="card br-report-card">
                <div class="card-header d-flex justify-content-between align-items-center no-print">
                    <h3 class="card-title mb-0">Bank reconciliation summary</h3>
                    <div>
                        <a href="{{ route('cashier.bank-reconciliation.index') }}"
                            class="btn btn-sm btn-default mr-1">Back to list</a>
                        <a href="{{ route('cashier.bank-reconciliation.show', $bankReconciliation) }}"
                            class="btn btn-sm btn-default">Back to review</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="br-report-print-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h4 class="mb-1">Bank Reconciliation Report</h4>
                                <p class="mb-0 small text-muted">Session #{{ $bankReconciliation->id }}</p>
                            </div>
                            @if ($isValidated)
                                <span class="badge badge-success px-3 py-2">VALIDATED</span>
                            @endif
                        </div>
                    </div>

                    <p class="mb-1"><strong>Account:</strong> {{ $bankReconciliation->giro?->acc_no }}
                        {{ $bankReconciliation->giro?->acc_name }}
                        <span class="badge badge-secondary">{{ $bankReconciliation->giro?->project }}</span>
                    </p>
                    <p class="mb-1"><strong>Bank:</strong> {{ $bankReconciliation->giro?->bank?->name ?? '-' }}</p>
                    <p class="mb-1"><strong>Period:</strong> {{ $bankReconciliation->periode?->format('F Y') }}</p>
                    <p class="mb-1"><strong>Source:</strong> {{ $bankReconciliation->source_mode }}</p>
                    <p class="mb-3">
                        <strong>Status:</strong> {{ $bankReconciliation->status }}
                        @if ($bankReconciliation->validation_status)
                            / {{ $bankReconciliation->validation_status }}
                        @endif
                    </p>

                    <h6 class="text-muted">Movement totals (non-excluded lines)</h6>
                    <table class="table table-sm table-bordered mb-3">
                        <tbody>
                            <tr>
                                <td>Bank net (debit − credit)</td>
                                <td class="text-right">{{ number_format($balanceSummary['bank_net'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Book net (debit − credit)</td>
                                <td class="text-right">{{ number_format($balanceSummary['book_net'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Difference (bank + book)</td>
                                <td class="text-right {{ $balanceSummary['is_balanced'] ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($balanceSummary['difference'], 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h6 class="text-muted">Balances (AI / SAP Bridge)</h6>
                    <table class="table table-sm table-bordered mb-3">
                        <tbody>
                            <tr>
                                <td>Opening — Bank statement</td>
                                <td class="text-right">
                                    {{ $bankReconciliation->opening_balance_bank !== null ? number_format((float) $bankReconciliation->opening_balance_bank, 2) : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td>Closing — Bank statement</td>
                                <td class="text-right">
                                    {{ $bankReconciliation->closing_balance_bank !== null ? number_format((float) $bankReconciliation->closing_balance_bank, 2) : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td>Opening — Books (SAP)</td>
                                <td class="text-right">
                                    {{ $bankReconciliation->opening_balance_book !== null ? number_format((float) $bankReconciliation->opening_balance_book, 2) : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td>Closing — Books (SAP)</td>
                                <td class="text-right">
                                    {{ $bankReconciliation->closing_balance_book !== null ? number_format((float) $bankReconciliation->closing_balance_book, 2) : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h6 class="text-muted">Outstanding (unmatched net debit − credit)</h6>
                    <p class="mb-2"><strong>Bank:</strong> {{ number_format($outstandingBankNet, 2) }}</p>
                    <p class="mb-3"><strong>SAP:</strong> {{ number_format($outstandingSapNet, 2) }}</p>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Unmatched bank lines</h6>
                            <ul class="small">
                                @forelse ($unmatchedBank as $line)
                                    <li>{{ $line->transaction_date?->format('Y-m-d') }} —
                                        {{ \Illuminate\Support\Str::limit($line->description, 60) }}
                                        ({{ number_format((float) $line->debit - (float) $line->credit, 2) }})
                                    </li>
                                @empty
                                    <li class="text-muted">None</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Unmatched SAP lines</h6>
                            <ul class="small">
                                @forelse ($unmatchedSap as $line)
                                    <li>{{ $line->posting_date?->format('Y-m-d') }} —
                                        {{ \Illuminate\Support\Str::limit($line->description, 60) }}
                                        ({{ number_format((float) $line->debit - (float) $line->credit, 2) }})
                                    </li>
                                @empty
                                    <li class="text-muted">None</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <h6 class="text-muted mt-4">Sign-off</h6>
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <td>Prepared by</td>
                                <td>{{ $bankReconciliation->creator?->name ?? '-' }}</td>
                                <td>{{ $bankReconciliation->created_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Submitted by</td>
                                <td>{{ $bankReconciliation->submittedBy?->name ?? '-' }}</td>
                                <td>{{ $bankReconciliation->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Validated by</td>
                                <td>{{ $bankReconciliation->validatedBy?->name ?? '-' }}</td>
                                <td>{{ $bankReconciliation->validated_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    @if ($bankReconciliation->rejection_reason)
                        <p class="small text-danger mb-0">
                            Last rejection: {{ $bankReconciliation->rejection_reason }}
                        </p>
                    @endif

                    @if ($isValidated)
                        <p class="small text-muted mb-0 mt-3">
                            Printed on {{ now()->format('d M Y H:i') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
