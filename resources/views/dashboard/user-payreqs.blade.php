{{-- PAYREQS --}}
<div class="col-lg-6 col-12">
    <div class="modern-dashboard-card">
        <div class="card-header-gradient payreq-gradient">
            <div class="card-header-content">
                <div class="card-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="card-header-text">
                    <h4 class="card-title mb-0">Your Ongoing Payreqs</h4>
                    <small>Payment requests in progress</small>
                </div>
            </div>
            <div class="card-actions">
                <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-light">
                    <i class="fas fa-list"></i> View All
                </a>
            </div>
        </div>

        <div class="modern-card-body">
            @php
                $hasItems = false;
                foreach ($user_ongoing_payreqs['payreq_status'] as $item) {
                    if ($item['count'] > 0) {
                        $hasItems = true;
                        break;
                    }
                }
                if ($user_ongoing_payreqs['over_due_payreq']['count'] > 0) {
                    $hasItems = true;
                }
            @endphp

            @if ($hasItems)
                @foreach ($user_ongoing_payreqs['payreq_status'] as $item)
                    @if ($item['count'] > 0)
                        <div class="status-item">
                            <div class="status-header">
                                <span class="status-badge badge-{{ $item['status'] }}">
                                    {{ ucfirst($item['status']) }}
                                </span>
                                <span class="status-count">{{ $item['count'] }}
                                    {{ $item['count'] === 1 ? 'payreq' : 'payreqs' }}</span>
                            </div>
                            <div class="status-amount">
                                Rp {{ number_format((float) $item['amount'], 0, ',', '.') }}
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($user_ongoing_payreqs['over_due_payreq']['count'] > 0)
                    <div class="status-item status-overdue">
                        <div class="status-header">
                            <span class="status-badge badge-overdue">
                                <i class="fas fa-exclamation-triangle"></i> OVERDUE
                            </span>
                            <span class="status-count">{{ $user_ongoing_payreqs['over_due_payreq']['count'] }}
                                {{ $user_ongoing_payreqs['over_due_payreq']['count'] === 1 ? 'payreq' : 'payreqs' }}</span>
                        </div>
                        <div class="status-amount text-danger">
                            Rp
                            {{ number_format((float) $user_ongoing_payreqs['over_due_payreq']['amount'], 0, ',', '.') }}
                        </div>
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No ongoing payreqs</p>
                    <small>All caught up!</small>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- REALIZATIONS --}}
<div class="col-lg-6 col-12">
    <div class="modern-dashboard-card">
        <div class="card-header-gradient realization-gradient">
            <div class="card-header-content">
                <div class="card-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="card-header-text">
                    <h4 class="card-title mb-0">Your Ongoing Realizations</h4>
                    <small>Expense realizations in progress</small>
                </div>
            </div>
            <div class="card-actions">
                <a href="{{ route('user-payreqs.realizations.index') }}" class="btn btn-sm btn-light">
                    <i class="fas fa-list"></i> View All
                </a>
            </div>
        </div>

        <div class="modern-card-body">
            @php
                $hasItems = false;
                foreach ($user_ongoing_realizations['realization_status'] as $item) {
                    if ($item['count'] > 0) {
                        $hasItems = true;
                        break;
                    }
                }
                if ($user_ongoing_realizations['overdue_realization']['count'] > 0) {
                    $hasItems = true;
                }
            @endphp

            @if ($hasItems)
                @foreach ($user_ongoing_realizations['realization_status'] as $item)
                    @if ($item['count'] > 0)
                        <div class="status-item">
                            <div class="status-header">
                                <span class="status-badge badge-{{ $item['status'] }}">
                                    {{ ucfirst($item['status']) }}
                                </span>
                                <span class="status-count">{{ $item['count'] }}
                                    {{ $item['count'] === 1 ? 'realization' : 'realizations' }}</span>
                            </div>
                            <div class="status-amount">
                                Rp {{ number_format((float) $item['amount'], 0, ',', '.') }}
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($user_ongoing_realizations['overdue_realization']['count'] > 0)
                    <div class="status-item status-overdue">
                        <div class="status-header">
                            <span class="status-badge badge-overdue">
                                <i class="fas fa-exclamation-triangle"></i> OVERDUE
                            </span>
                            <span class="status-count">{{ $user_ongoing_realizations['overdue_realization']['count'] }}
                                {{ $user_ongoing_realizations['overdue_realization']['count'] === 1 ? 'realization' : 'realizations' }}</span>
                        </div>
                        <div class="status-amount text-danger">
                            Rp
                            {{ number_format((float) $user_ongoing_realizations['overdue_realization']['amount'], 0, ',', '.') }}
                        </div>
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No ongoing realizations</p>
                    <small>All caught up!</small>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .modern-dashboard-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .modern-dashboard-card:hover {
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    .card-header-gradient {
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .payreq-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .realization-gradient {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .card-header-content {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .card-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .card-icon i {
        font-size: 24px;
        color: #fff;
    }

    .card-header-text {
        color: #fff;
    }

    .card-header-text h4 {
        color: #fff;
        font-size: 18px;
        font-weight: 600;
    }

    .card-header-text small {
        color: rgba(255, 255, 255, 0.8);
        font-size: 12px;
    }

    .card-actions .btn {
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .modern-card-body {
        padding: 20px;
    }

    .status-item {
        padding: 15px;
        border-radius: 8px;
        background: #f8f9fa;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }

    .status-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }

    .status-item:last-child {
        margin-bottom: 0;
    }

    .status-overdue {
        background: #fff5f5;
        border-left: 4px solid #dc3545;
    }

    .status-overdue:hover {
        background: #ffe5e5;
    }

    .status-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.badge-draft {
        background: #e7f3ff;
        color: #0056b3;
    }

    .status-badge.badge-submitted {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge.badge-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.badge-paid,
    .status-badge.badge-done {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-badge.badge-overdue {
        background: #f8d7da;
        color: #721c24;
    }

    .status-count {
        font-size: 13px;
        color: #6c757d;
    }

    .status-amount {
        font-size: 18px;
        font-weight: bold;
        color: #495057;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 48px;
        color: #28a745;
        margin-bottom: 15px;
    }

    .empty-state p {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .empty-state small {
        font-size: 14px;
        color: #6c757d;
    }
</style>
