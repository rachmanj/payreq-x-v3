@extends('templates.main')

@section('title_page')
    Bank Reconciliation
@endsection

@section('breadcrumb_title')
    cashier / bank-reconciliation
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($canValidate)
                <div class="alert alert-info py-2">
                    <strong>Validator:</strong> Open <strong>Pending validation</strong> below, click <strong>Validate</strong> on a session,
                    then use <strong>Validate</strong> or <strong>Reject</strong> on the review page.
                    You cannot approve reconciliations you prepared or submitted.
                </div>
            @endif

            <div class="card card-outline card-primary">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h3 class="card-title mb-2 mb-md-0">Bank reconciliation sessions</h3>
                    <a href="{{ route('cashier.bank-reconciliation.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> New reconciliation
                    </a>
                </div>

                @if ($canValidate)
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs px-3">
                            <li class="nav-item">
                                <a class="nav-link {{ $view === 'all' ? 'active' : '' }}"
                                    href="{{ route('cashier.bank-reconciliation.index', ['view' => 'all']) }}">
                                    All sessions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $view === 'pending_validation' ? 'active' : '' }}"
                                    href="{{ route('cashier.bank-reconciliation.index', ['view' => 'pending_validation']) }}">
                                    Pending validation
                                    @if ($pendingValidationCount > 0)
                                        <span class="badge badge-warning ml-1">{{ $pendingValidationCount }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif

                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Giro</th>
                                <th>Period</th>
                                <th>Status</th>
                                @if ($view === 'pending_validation')
                                    <th>Submitted</th>
                                @endif
                                <th>Updated</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reconciliations as $row)
                                @php
                                    $rowCanValidate = $canValidate
                                        && $row->validation_status === \App\Models\BankReconciliation::VALIDATION_PENDING
                                        && ! $row->isPreparer((int) auth()->id());
                                @endphp
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>
                                        <small>
                                            {{ $row->giro?->acc_no }} — {{ $row->giro?->acc_name }}
                                            <span class="badge badge-secondary">{{ $row->giro?->project }}</span>
                                        </small>
                                    </td>
                                    <td>{{ $row->periode?->format('M Y') }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $row->status }}</span>
                                        @if ($row->validation_status)
                                            <span class="badge badge-warning">{{ $row->validation_status }}</span>
                                        @endif
                                        <span class="badge badge-light border">{{ $row->source_mode }}</span>
                                    </td>
                                    @if ($view === 'pending_validation')
                                        <td>
                                            <small>
                                                {{ $row->submittedBy?->name ?? '—' }}
                                                @if ($row->submitted_at)
                                                    <br><span class="text-muted">{{ $row->submitted_at->format('d/m/Y H:i') }}</span>
                                                @endif
                                            </small>
                                        </td>
                                    @endif
                                    <td><small>{{ $row->updated_at?->diffForHumans() }}</small></td>
                                    <td class="text-right text-nowrap">
                                        <a href="{{ route('cashier.bank-reconciliation.show', $row) }}"
                                            class="btn btn-xs {{ $rowCanValidate ? 'btn-success' : 'btn-info' }}">
                                            {{ $rowCanValidate ? 'Validate' : 'Review' }}
                                        </a>
                                        <a href="{{ route('cashier.bank-reconciliation.report', $row) }}"
                                            class="btn btn-xs btn-secondary">Report</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $view === 'pending_validation' ? 7 : 6 }}" class="text-center text-muted py-4">
                                        @if ($view === 'pending_validation')
                                            No bank reconciliations waiting for your validation.
                                        @else
                                            No reconciliations yet.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($reconciliations->hasPages())
                    <div class="card-footer">{{ $reconciliations->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
