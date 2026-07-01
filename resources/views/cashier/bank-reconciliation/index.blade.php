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
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Bank reconciliation sessions</h3>
                    <a href="{{ route('cashier.bank-reconciliation.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> New reconciliation
                    </a>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Giro</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reconciliations as $row)
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
                                    <td><small>{{ $row->updated_at?->diffForHumans() }}</small></td>
                                    <td class="text-right">
                                        <a href="{{ route('cashier.bank-reconciliation.show', $row) }}"
                                            class="btn btn-xs btn-info">Review</a>
                                        <a href="{{ route('cashier.bank-reconciliation.report', $row) }}"
                                            class="btn btn-xs btn-secondary">Report</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No reconciliations yet.</td>
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
