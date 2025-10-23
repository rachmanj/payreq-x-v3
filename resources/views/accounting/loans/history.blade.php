@extends('templates.main')

@section('title_page', 'Loan History')

@section('content')
    <div class="row">
        <div class="col-12">
            <x-loan-links page="history" />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> History for {{ $loan->loan_code }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('accounting.loans.show', $loan->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Loan
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Loan Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-file-contract"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Loan Code</span>
                                    <span class="info-box-number">{{ $loan->loan_code }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-building"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Creditor</span>
                                    <span class="info-box-number">{{ $loan->creditor->name ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Status -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Current Status</h5>
                        <p class="mb-0">
                            <strong>Principal:</strong> IDR {{ number_format($loan->principal, 2) }}
                            <br>
                            <strong>Tenor:</strong> {{ $loan->tenor }} months
                            <br>
                            <strong>Status:</strong> {{ ucfirst($loan->status ?? 'Active') }}
                            <br>
                            <strong>Description:</strong> {{ $loan->description }}
                            <br>
                            <strong>Created:</strong> {{ $loan->created_at->format('d M Y H:i:s') }} by
                            {{ $loan->user->name ?? 'Unknown' }}
                        </p>
                    </div>

                    <!-- History Timeline -->
                    <h5><i class="fas fa-timeline"></i> Change History</h5>
                    <div class="timeline">
                        @foreach ($audits as $audit)
                            <div class="time-label">
                                <span
                                    class="bg-{{ $audit->action == 'created' ? 'green' : ($audit->action == 'deleted' ? 'red' : 'blue') }}">
                                    {{ $audit->created_at->format('d M Y') }}
                                </span>
                            </div>

                            <div>
                                <i
                                    class="fas fa-{{ $audit->action == 'created' ? 'plus' : ($audit->action == 'deleted' ? 'times' : 'edit') }} bg-{{ $audit->action == 'created' ? 'green' : ($audit->action == 'deleted' ? 'red' : 'blue') }}"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> {{ $audit->created_at->format('H:i:s') }}
                                    </span>
                                    <h3 class="timeline-header">
                                        <span
                                            class="badge badge-{{ $audit->action == 'created' ? 'success' : ($audit->action == 'deleted' ? 'danger' : 'info') }}">
                                            {{ $audit->action_label }}
                                        </span>
                                        by {{ $audit->user->name ?? 'Unknown' }}
                                    </h3>
                                    <div class="timeline-body">
                                        <p><strong>Changes:</strong> {{ $audit->changes_summary }}</p>
                                        @if ($audit->notes)
                                            <p><strong>Notes:</strong> {{ $audit->notes }}</p>
                                        @endif
                                        @if ($audit->ip_address)
                                            <p><small class="text-muted">IP: {{ $audit->ip_address }}</small></p>
                                        @endif
                                    </div>
                                    <div class="timeline-footer">
                                        <a href="{{ route('accounting.loans.audit.show', $audit->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div>
                            <i class="fas fa-clock bg-gray"></i>
                        </div>
                    </div>

                    @if ($audits->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No history records found for this loan.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .timeline {
            position: relative;
            padding: 0;
            margin: 0;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
            left: 31px;
            margin: 0;
            border-radius: 2px;
        }

        .timeline>div {
            position: relative;
            margin-bottom: 15px;
        }

        .timeline>div:before,
        .timeline>div:after {
            content: '';
            display: table;
        }

        .timeline>div:after {
            clear: both;
        }

        .timeline .time-label {
            position: relative;
            width: 100px;
            text-align: center;
            padding: 10px;
            margin: 0 auto 20px;
        }

        .timeline .time-label>span {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: #fff;
            font-weight: 600;
        }

        .timeline .timeline-item {
            position: relative;
            margin-left: 60px;
            margin-bottom: 15px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
        }

        .timeline .timeline-item:before {
            content: '';
            position: absolute;
            top: 15px;
            left: -8px;
            width: 0;
            height: 0;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-right: 8px solid #dee2e6;
        }

        .timeline .timeline-item:after {
            content: '';
            position: absolute;
            top: 16px;
            left: -7px;
            width: 0;
            height: 0;
            border-top: 7px solid transparent;
            border-bottom: 7px solid transparent;
            border-right: 7px solid #fff;
        }

        .timeline .timeline-item>i {
            position: absolute;
            left: -40px;
            top: 15px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            color: #fff;
            font-size: 12px;
        }

        .timeline .timeline-item .time {
            color: #999;
            font-size: 12px;
            float: right;
        }

        .timeline .timeline-item .timeline-header {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #f4f4f4;
            padding-bottom: 5px;
            font-size: 16px;
        }

        .timeline .timeline-item .timeline-body {
            padding: 10px 0;
        }

        .timeline .timeline-item .timeline-footer {
            margin-top: 10px;
            border-top: 1px solid #f4f4f4;
            padding-top: 10px;
        }
    </style>
@endsection

@section('styles')
    <style>
        .card-header .active {
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection
