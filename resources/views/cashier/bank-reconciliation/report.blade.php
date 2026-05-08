@extends('templates.main')

@section('title_page')
    Bank Reconciliation Report #{{ $bankReconciliation->id }}
@endsection

@section('breadcrumb_title')
    cashier / bank-reconciliation / {{ $bankReconciliation->id }} / report
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-10">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Bank reconciliation summary</h3>
                    <a href="{{ route('cashier.bank-reconciliation.show', $bankReconciliation) }}"
                        class="btn btn-sm btn-default">Back to review</a>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Account:</strong> {{ $bankReconciliation->giro?->acc_no }}
                        {{ $bankReconciliation->giro?->acc_name }}</p>
                    <p class="mb-1"><strong>Period:</strong> {{ $bankReconciliation->periode?->format('F Y') }}</p>
                    <p class="mb-3"><strong>Status:</strong> {{ $bankReconciliation->status }}</p>

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
                                        {{ \Illuminate\Support\Str::limit($line->description, 60) }}</li>
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
                                        {{ \Illuminate\Support\Str::limit($line->description, 60) }}</li>
                                @empty
                                    <li class="text-muted">None</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    @if ($bankReconciliation->reconciled_at)
                        <p class="small text-muted mb-0 mt-3">
                            Completed {{ $bankReconciliation->reconciled_at->format('d M Y H:i') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
