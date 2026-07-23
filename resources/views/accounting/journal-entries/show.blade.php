@extends('templates.main')

@section('title_page')
    Journal Entry {{ $journalEntry->number }}
@endsection

@section('breadcrumb_title')
    accounting / journal-entries / show
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            @php
                $headerStatus = $journalEntry->isReversed() ? 'reversed' : ($journalEntry->isPosted() ? 'posted' : 'pending');
            @endphp
            <div class="card card-outline {{ $headerStatus === 'posted' ? 'card-success' : ($headerStatus === 'reversed' ? 'card-secondary' : 'card-warning') }}">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0">Manual Journal Entry</h3>
                            <small>{{ $journalEntry->number }}</small>
                        </div>
                        <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title">Header</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Number</dt>
                        <dd class="col-sm-8"><strong>{{ $journalEntry->number }}</strong></dd>
                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">{{ $journalEntry->date?->format('d-M-Y') }}</dd>
                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8">{{ $journalEntry->reference ?? '—' }}</dd>
                        <dt class="col-sm-4">Memo</dt>
                        <dd class="col-sm-8">{{ $journalEntry->memo ?? '—' }}</dd>
                        <dt class="col-sm-4">Created by</dt>
                        <dd class="col-sm-8">{{ $journalEntry->createdBy?->name }}</dd>
                        @if ($journalEntry->template)
                            <dt class="col-sm-4">From Template</dt>
                            <dd class="col-sm-8">{{ $journalEntry->template->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-outline {{ $journalEntry->isPosted() ? 'card-success' : 'card-warning' }}">
                <div class="card-header"><h3 class="card-title">SAP Integration</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">SAP Journal No</dt>
                        <dd class="col-sm-7">{{ $journalEntry->sap_journal_no ?? 'Not submitted' }}</dd>
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            @if ($journalEntry->isReversed())
                                <span class="badge badge-secondary">Reversed</span>
                            @elseif ($journalEntry->isPosted())
                                <span class="badge badge-success">Posted</span>
                            @elseif ($journalEntry->sap_submission_status === 'failed')
                                <span class="badge badge-danger">Failed</span>
                            @else
                                <span class="badge badge-warning">Draft</span>
                            @endif
                        </dd>
                        @if ($journalEntry->sap_submission_error)
                            <dt class="col-sm-5">Last Error</dt>
                            <dd class="col-sm-7 text-danger">{{ $journalEntry->sap_submission_error }}</dd>
                        @endif
                        @if ($journalEntry->sap_submitted_at)
                            <dt class="col-sm-5">Submitted at</dt>
                            <dd class="col-sm-7">{{ $journalEntry->sap_submitted_at->format('d-M-Y H:i') }}</dd>
                        @endif
                    </dl>

                    <div class="mt-3">
                        @if ($journalEntry->isEditable())
                            <a href="{{ route('accounting.journal-entries.edit', $journalEntry->id) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('accounting.journal-entries.submit_to_sap', $journalEntry->id) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Submit this journal entry to SAP B1?');">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-paper-plane"></i> Submit to SAP B1
                                </button>
                            </form>
                        @endif

                        @if ($journalEntry->isPosted() && ! $journalEntry->isReversed() && auth()->user()->can('cancel_sap_journal'))
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#reverse-modal">
                                <i class="fas fa-undo"></i> Reverse in SAP B1
                            </button>
                        @endif

                        <a href="{{ route('accounting.journal-entries.print', $journalEntry->id) }}" class="btn btn-secondary btn-sm" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Journal Lines</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Account</th>
                                <th>Account Name</th>
                                <th>Project</th>
                                <th>Cost Center</th>
                                <th>Description</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($journalEntry->lines as $line)
                                <tr>
                                    <td>{{ $line->line_no }}</td>
                                    <td>{{ $line->account_code }}</td>
                                    <td>{{ $accountNames[$line->account_code] ?? '—' }}</td>
                                    <td>{{ $line->project ?? '—' }}</td>
                                    <td>{{ $line->cost_center ?? '—' }}</td>
                                    <td>{{ $line->description ?? '—' }}</td>
                                    <td class="text-right">{{ $line->debit_credit === 'debit' ? number_format($line->amount, 2) : '' }}</td>
                                    <td class="text-right">{{ $line->debit_credit === 'credit' ? number_format($line->amount, 2) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-right">Total</th>
                                <th class="text-right">{{ number_format($journalEntry->totalDebit(), 2) }}</th>
                                <th class="text-right">{{ number_format($journalEntry->totalCredit(), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($submissionLogs->isNotEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Submission History</h3></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                    <th>User</th>
                                    <th>SAP Journal No</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($submissionLogs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('d-M-Y H:i') }}</td>
                                        <td>{{ ucfirst($log->action) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $log->status === 'success' ? 'success' : 'danger' }}">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $log->user?->name }}</td>
                                        <td>{{ $log->sap_journal_number ?? '—' }}</td>
                                        <td>{{ $log->error_message ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @can('cancel_sap_journal')
        <div class="modal fade" id="reverse-modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('accounting.journal-entries.reverse_to_sap', $journalEntry->id) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Reverse Journal in SAP B1</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <p>This will cancel journal <strong>{{ $journalEntry->sap_journal_no }}</strong> in SAP B1.</p>
                            <div class="form-group">
                                <label for="reason">Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reverse in SAP B1</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection
