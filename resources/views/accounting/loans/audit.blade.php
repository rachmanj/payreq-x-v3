@extends('templates.main')

@section('title_page', 'Loan Audit Trail')

@section('content')
    <div class="row">
        <div class="col-12">
            <x-loan-links page="audit" />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Loan Audit Trail
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('accounting.loans.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Loans
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="action">Action</label>
                                    <select name="action" id="action" class="form-control form-control-sm">
                                        <option value="">All Actions</option>
                                        <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>
                                            Created</option>
                                        <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>
                                            Updated</option>
                                        <option value="status_changed"
                                            {{ request('action') == 'status_changed' ? 'selected' : '' }}>Status Changed
                                        </option>
                                        <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>
                                            Deleted</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_from">Date From</label>
                                    <input type="date" name="date_from" id="date_from"
                                        class="form-control form-control-sm" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_to">Date To</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                                        value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <a href="{{ route('accounting.loans.audit.index') }}"
                                            class="btn btn-sm btn-secondary">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Audit Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Action</th>
                                    <th>Loan</th>
                                    <th>User</th>
                                    <th>Changes</th>
                                    <th>IP Address</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($audits as $audit)
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                {{ $audit->created_at->format('d M Y H:i:s') }}
                                            </small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $audit->action == 'created' ? 'success' : ($audit->action == 'deleted' ? 'danger' : 'info') }}">
                                                {{ $audit->action_label }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($audit->loan)
                                                <a href="{{ route('accounting.loans.history', $audit->loan_id) }}"
                                                    class="text-primary">
                                                    {{ $audit->loan->loan_code }}
                                                </a>
                                                <br>
                                                <small
                                                    class="text-muted">{{ $audit->loan->creditor->name ?? 'N/A' }}</small>
                                            @else
                                                <span class="text-muted">Deleted</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $audit->user->name ?? 'Unknown' }}
                                            <br>
                                            <small class="text-muted">{{ $audit->user->email ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $audit->changes_summary }}</small>
                                            @if ($audit->notes)
                                                <br>
                                                <small class="text-muted">{{ $audit->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $audit->ip_address ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('accounting.loans.audit.show', $audit->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> No audit records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($audits->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $audits->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card-header .active {
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection
