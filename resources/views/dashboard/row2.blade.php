<div class="col-lg-4 col-md-6 col-12">
    <div class="modern-stat-card {{ $avg_completion_days > 7 ? 'stat-danger' : 'stat-success' }}">
        <div class="stat-icon">
            <i class="fas fa-business-time"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">{{ number_format($avg_completion_days, 1) }}</div>
            <div class="stat-label">Average Completion Days</div>
            <div class="stat-info">
                <i class="fas {{ $avg_completion_days > 7 ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i>
                {{ $avg_completion_days > 7 ? 'Needs attention' : 'On track' }}
            </div>
        </div>
    </div>
</div>

@can('see_vj_not_posted')
    <div class="col-lg-4 col-md-6 col-12">
        <div class="modern-stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-sync-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $vj_not_posted }}</div>
                <div class="stat-label">VJ to be Synced</div>
                <div class="stat-info">
                    <i class="fas fa-info-circle"></i>
                    Pending synchronization
                </div>
            </div>
            <a href="{{ route('accounting.sap-sync.index', ['page' => 'dashboard']) }}" class="stat-action">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
@endcan

<style>
    .modern-stat-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .modern-stat-card:hover {
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
    }

    .modern-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .stat-success::before {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .stat-danger::before {
        background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    }

    .stat-primary::before {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .stat-success .stat-icon {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .stat-danger .stat-icon {
        background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    }

    .stat-primary .stat-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon i {
        font-size: 32px;
        color: #fff;
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .stat-success .stat-value {
        color: #11998e;
    }

    .stat-danger .stat-value {
        color: #ee0979;
    }

    .stat-primary .stat-value {
        color: #667eea;
    }

    .stat-label {
        font-size: 14px;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .stat-info {
        font-size: 12px;
        color: #6c757d;
        display: flex;
        align-items: center;
    }

    .stat-info i {
        margin-right: 5px;
    }

    .stat-action {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        color: #495057;
    }

    .stat-action:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        transform: scale(1.1);
    }
</style>
