@extends('templates.main')

@section('title_page')
    View Bank Transaction
@endsection

@section('breadcrumb_title')
    bank-transactions/show
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    @include('partials.vj-soft-ui-styles')
@endsection

@section('content')
    @php
        $statusChip = match ($journal->status) {
            'draft' => 'vj-chip-warning',
            'submitted' => 'vj-chip-info',
            'posted' => 'vj-chip-success',
            'canceled' => 'vj-chip-danger',
            default => 'vj-chip-neutral',
        };
    @endphp

    <div class="vj-show">
        <div class="vj-stat-grid vj-stat-grid-4 mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-hashtag"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Journal Number</span>
                    <span class="vj-stat-value">{{ $journal->nomor ?? 'Not assigned yet' }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Posting Date</span>
                    <span class="vj-stat-value">{{ $journal->date ? date('d M Y', strtotime($journal->date)) : '—' }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Amount</span>
                    <span class="vj-stat-value">{{ number_format($journal->amount, 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-flag"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Status</span>
                    <span class="vj-stat-value">
                        <span class="vj-chip {{ $statusChip }}">{{ ucfirst($journal->status) }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-university"></i> Bank Transaction Details
                        </h3>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <a href="{{ route('cashier.bank-transactions.index') }}" class="vj-action-item vj-action-back">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back to List</span>
                            </a>
                            @if ($journal->status == 'draft')
                                <a href="{{ route('cashier.bank-transactions.edit', $journal->id) }}"
                                    class="vj-action-item vj-action-item-xs vj-action-edit">
                                    <i class="fas fa-edit"></i>
                                    <span>Edit</span>
                                </a>
                                <form action="{{ route('cashier.bank-transactions.submit', $journal->id) }}" method="POST"
                                    class="vj-action-item-form">
                                    @csrf
                                    <button type="button" class="vj-action-item vj-action-item-xs vj-action-success submit-transaction"
                                        data-direct-sap="{{ $eligibleForDirectSap ? '1' : '0' }}">
                                        <i class="fas fa-paper-plane"></i>
                                        <span>Submit</span>
                                    </button>
                                </form>
                            @elseif(
                                $journal->status == 'submitted' &&
                                    empty($journal->sap_journal_no) &&
                                    $journal->auto_validated_by_cashier &&
                                    in_array($journal->sap_submission_status, [null, 'failed'], true))
                                <form action="{{ route('cashier.bank-transactions.submit', $journal->id) }}" method="POST"
                                    class="vj-action-item-form">
                                    @csrf
                                    <button type="button" class="vj-action-item vj-action-item-xs vj-action-success submit-transaction"
                                        data-direct-sap="1">
                                        <i class="fas fa-redo"></i>
                                        <span>Retry SAP Submit</span>
                                    </button>
                                </form>
                            @endif
                            @can('recalculate_cashier_balance')
                                @if ($needsRecalculateBalance && $recalculateIncoming)
                                    <form action="{{ route('cashier.bank-transactions.recalculate-balance', $journal->id) }}"
                                        method="POST" class="vj-action-item-form" id="recalculate-balance-form">
                                        @csrf
                                        <button type="button" class="vj-action-item vj-action-item-xs vj-action-warning"
                                            id="recalculate-balance-btn"
                                            data-journal-nomor="{{ $journal->nomor }}"
                                            data-amount="{{ number_format($recalculateIncoming->amount, 0, ',', '.') }}">
                                            <i class="fas fa-calculator"></i>
                                            <span>Recalculate Balance</span>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Type</dt>
                                    <dd class="col-sm-8">{{ $journal->type ?? '—' }}</dd>
                                    <dt class="col-sm-4">Project</dt>
                                    <dd class="col-sm-8">{{ $journal->project ?? '—' }}</dd>
                                    <dt class="col-sm-4">Bank Account</dt>
                                    <dd class="col-sm-8">{{ $journal->bank_account ? $journal->bank_account : 'Not specified' }}</dd>
                                    <dt class="col-sm-4">Description</dt>
                                    <dd class="col-sm-8 mb-0">{{ $journal->description ?? '—' }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Created By</dt>
                                    <dd class="col-sm-8">{{ $journal->createdBy->name ?? '—' }}</dd>
                                    <dt class="col-sm-4">SAP Journal No</dt>
                                    <dd class="col-sm-8">
                                        {{ $journal->sap_journal_no ?? '—' }}
                                        @if ($journal->sap_journal_no)
                                            <a href="{{ route('accounting.sap-sync.show', $journal->id) }}"
                                                class="vj-action-item vj-action-item-xs vj-action-sap ml-1">
                                                <i class="fas fa-cloud"></i>
                                                <span>View in SAP Sync</span>
                                            </a>
                                        @endif
                                    </dd>
                                    <dt class="col-sm-4">SAP Posting Date</dt>
                                    <dd class="col-sm-8">
                                        {{ $journal->sap_posting_date ? date('d M Y', strtotime($journal->sap_posting_date)) : '—' }}
                                    </dd>
                                    <dt class="col-sm-4">Posted By</dt>
                                    <dd class="col-sm-8 mb-0">{{ $journal->postedBy->name ?? '—' }}</dd>
                                </dl>
                            </div>
                        </div>

                        @if ($journal->auto_validated_by_cashier)
                            <div class="vj-note mt-3 mb-0">
                                <i class="fas fa-check-circle"></i>
                                <div>Auto-validated by cashier</div>
                            </div>
                        @endif

                        <h4 class="mt-4 mb-3"><i class="fas fa-list"></i> Transaction Details</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="details-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Realization Date</th>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Debit/Credit</th>
                                        <th>Description</th>
                                        <th>Project</th>
                                        <th>Cost Center</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($journal->verificationJournalDetails as $detail)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ date('d M Y', strtotime($detail->realization_date)) }}</td>
                                            <td>{{ $detail->account_code }}</td>
                                            <td>{{ $accountNames[$detail->account_code] ?? $detail->account_code }}</td>
                                            <td>{{ ucfirst($detail->debit_credit) }}</td>
                                            <td>{{ $detail->description }}</td>
                                            <td>{{ $detail->project }}</td>
                                            <td>{{ $detail->cost_center }}</td>
                                            <td class="text-right">{{ number_format($detail->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No details found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="8" class="text-right">Total:</th>
                                        <th class="text-right">{{ number_format($journal->amount, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if (in_array($journal->status, ['submitted', 'posted'], true) && $incoming)
                            <div class="mt-4">
                                <h4 class="mb-3"><i class="fas fa-link"></i> Linked Incoming Record</h4>
                                <div class="vj-note">
                                    <i class="fas fa-arrow-circle-down"></i>
                                    <div>
                                        <p class="mb-1"><strong>Incoming ID:</strong> {{ $incoming->id }}</p>
                                        <p class="mb-1"><strong>Description:</strong> {{ $incoming->description }}</p>
                                        <p class="mb-1"><strong>Amount:</strong> {{ number_format($incoming->amount, 2) }}</p>
                                        <p class="mb-1"><strong>Received Date:</strong>
                                            {{ $incoming->receive_date ? date('d M Y H:i', strtotime($incoming->receive_date)) : 'Not received yet' }}
                                        </p>
                                        <p class="mb-2"><strong>Created At:</strong>
                                            {{ date('d M Y H:i', strtotime($incoming->created_at)) }}
                                        </p>
                                        <a href="{{ route('cashier.incomings.received.index') }}" class="vj-btn vj-btn-primary">
                                            <i class="fas fa-eye"></i> View in Received Incomings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(function() {
            $('.submit-transaction').on('click', function() {
                const form = $(this).closest('form');
                const directSap = $(this).data('direct-sap') === 1 || $(this).data('direct-sap') === '1';

                const title = directSap ? 'Post to SAP B1?' : 'Submit Transaction?';
                const text = directSap
                    ? 'The journal will be posted immediately in SAP B1. It cannot be edited afterwards; corrections require a reversal (storno) by Accounting.'
                    : 'This will submit the transaction for Accounting validation. An incoming record will be created and you will not be able to edit it afterwards.';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: directSap ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, submit it!',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $('#recalculate-balance-btn').on('click', function() {
                const form = $('#recalculate-balance-form');
                const journalNomor = $(this).data('journal-nomor');
                const amount = $(this).data('amount');

                Swal.fire({
                    title: 'Recalculate Petty Cash Balance?',
                    html: '<p>Transaction <strong>' + journalNomor +
                        '</strong> will credit petty cash by <strong>IDR ' + amount +
                        '</strong>.</p><p class="text-danger mb-0"><small>This updates application balances and is recorded in the audit log. Use only when SAP was posted but petty cash booking failed.</small></p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, recalculate',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#f0ad4e',
                    cancelButtonColor: '#6c757d',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
