@extends('templates.main')

@section('title_page', 'Loan Audit Detail')

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <x-loan-links page="audit" />

                <div class="vj-stat-grid vj-stat-grid-4 mb-3">
                    <div class="vj-stat vj-stat-info">
                        <div class="vj-stat-icon"><i class="fas fa-calendar"></i></div>
                        <div class="vj-stat-body">
                            <span class="vj-stat-label">Date & Time</span>
                            <span class="vj-stat-value">{{ $audit->created_at->format('d M Y H:i:s') }}</span>
                        </div>
                    </div>
                    <div class="vj-stat vj-stat-{{ $audit->action == 'created' ? 'success' : ($audit->action == 'deleted' ? 'danger' : 'warning') }}">
                        <div class="vj-stat-icon">
                            <i
                                class="fas fa-{{ $audit->action == 'created' ? 'plus' : ($audit->action == 'deleted' ? 'times' : 'edit') }}"></i>
                        </div>
                        <div class="vj-stat-body">
                            <span class="vj-stat-label">Action</span>
                            <span class="vj-stat-value">{{ $audit->action_label }}</span>
                        </div>
                    </div>
                    <div class="vj-stat vj-stat-neutral">
                        <div class="vj-stat-icon"><i class="fas fa-user"></i></div>
                        <div class="vj-stat-body">
                            <span class="vj-stat-label">User</span>
                            <span class="vj-stat-value">{{ $audit->user->name ?? 'Unknown' }}</span>
                            <small class="text-muted">{{ $audit->user->email ?? 'N/A' }}</small>
                        </div>
                    </div>
                    <div class="vj-stat vj-stat-neutral">
                        <div class="vj-stat-icon"><i class="fas fa-globe"></i></div>
                        <div class="vj-stat-body">
                            <span class="vj-stat-label">IP Address</span>
                            <span class="vj-stat-value">{{ $audit->ip_address ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-search"></i> Audit Detail
                        </h3>
                        <a href="{{ route('accounting.loans.audit.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Back to Audit Trail
                        </a>
                    </div>

                    <div class="card-body">
                        @if ($audit->loan)
                            <div class="vj-note mb-4">
                                <i class="fas fa-file-contract"></i>
                                <div>
                                    <strong>Loan Information</strong>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Loan Code:</strong> {{ $audit->loan->loan_code }}</p>
                                            <p class="mb-1"><strong>Creditor:</strong> {{ $audit->loan->creditor->name ?? 'N/A' }}</p>
                                            <p class="mb-0"><strong>Principal:</strong> IDR {{ number_format($audit->loan->principal, 2) }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Description:</strong> {{ $audit->loan->description }}</p>
                                            <p class="mb-1"><strong>Tenor:</strong> {{ $audit->loan->tenor }} months</p>
                                            <p class="mb-0"><strong>Status:</strong> {{ ucfirst($audit->loan->status ?? 'N/A') }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('accounting.loans.history', $audit->loan_id) }}"
                                            class="vj-action-item vj-action-item-xs vj-action-export">
                                            <i class="fas fa-history"></i>
                                            <span>View Full History</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="vj-alert vj-alert-warning mb-4">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>This loan has been deleted.</div>
                            </div>
                        @endif

                        <div class="row">
                            @if ($audit->old_values)
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-arrow-left"></i> Previous Values
                                            </h5>
                                            <span class="vj-chip vj-chip-danger">Old</span>
                                        </div>
                                        <div class="card-body">
                                            @foreach ($audit->old_values as $field => $value)
                                                <div class="form-group">
                                                    <label
                                                        class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $field)) }}:</label>
                                                    <p class="form-control-plaintext">{{ $value ?? 'N/A' }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($audit->new_values)
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header d-flex flex-wrap align-items-center gap-2">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-arrow-right"></i> New Values
                                            </h5>
                                            <span class="vj-chip vj-chip-success">New</span>
                                        </div>
                                        <div class="card-body">
                                            @foreach ($audit->new_values as $field => $value)
                                                <div class="form-group">
                                                    <label
                                                        class="font-weight-bold">{{ ucfirst(str_replace('_', ' ', $field)) }}:</label>
                                                    <p class="form-control-plaintext">{{ $value ?? 'N/A' }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($audit->notes)
                            <div class="vj-note mt-4">
                                <i class="fas fa-sticky-note"></i>
                                <div>
                                    <strong>Notes</strong>
                                    <div>{{ $audit->notes }}</div>
                                </div>
                            </div>
                        @endif

                        @if ($audit->user_agent)
                            <div class="vj-note mt-3">
                                <i class="fas fa-desktop"></i>
                                <div>
                                    <strong>User Agent</strong>
                                    <div><small>{{ $audit->user_agent }}</small></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
