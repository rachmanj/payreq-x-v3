@extends('templates.main')

@section('title_page', 'Loan History')

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <x-loan-links page="history" />

                <div class="vj-stat-grid mb-3">
                    <div class="vj-stat vj-stat-info">
                        <div class="vj-stat-icon"><i class="fas fa-file-contract"></i></div>
                        <div class="vj-stat-body">
                            <span class="vj-stat-label">Loan Code</span>
                            <span class="vj-stat-value">{{ $loan->loan_code }}</span>
                        </div>
                    </div>
                    <div class="vj-stat vj-stat-success">
                        <div class="vj-stat-icon"><i class="fas fa-building"></i></div>
                        <div class="vj-stat-body">
                            <span class="vj-stat-label">Creditor</span>
                            <span class="vj-stat-value">{{ $loan->creditor->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-history"></i> History for {{ $loan->loan_code }}
                        </h3>
                        <a href="{{ route('accounting.loans.show', $loan->id) }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Back to Loan
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="vj-note mb-4">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Current Status</strong>
                                <div>
                                    <strong>Principal:</strong> IDR {{ number_format($loan->principal, 2) }}<br>
                                    <strong>Tenor:</strong> {{ $loan->tenor }} months<br>
                                    <strong>Status:</strong> {{ ucfirst($loan->status ?? 'Active') }}<br>
                                    <strong>Description:</strong> {{ $loan->description }}<br>
                                    <strong>Created:</strong> {{ $loan->created_at->format('d M Y H:i:s') }} by
                                    {{ $loan->user->name ?? 'Unknown' }}
                                </div>
                            </div>
                        </div>

                        <h5 class="mb-3"><i class="fas fa-stream"></i> Change History</h5>
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
                                                class="vj-chip vj-chip-{{ $audit->action == 'created' ? 'success' : ($audit->action == 'deleted' ? 'danger' : 'info') }}">
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
                                                class="vj-action-item vj-action-item-xs vj-action-export">
                                                <i class="fas fa-eye"></i>
                                                <span>view details</span>
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
                            <div class="vj-note">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>No history records</strong>
                                    <div>No history records found for this loan.</div>
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
            border-radius: 10px;
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
