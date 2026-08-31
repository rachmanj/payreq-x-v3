<div class="col-lg-6">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0"><i class="fas fa-info-circle"></i> Info</h3>
        </div>
        <div class="card-body">
            <div class="vj-stat-grid mb-0">
                <div class="vj-stat vj-stat-info">
                    <div class="vj-stat-icon"><i class="fas fa-wallet"></i></div>
                    <div class="vj-stat-body">
                        <span class="vj-stat-label">PC Balance</span>
                        <span class="vj-stat-value">Rp. {{ number_format($dashboard_data['today_pc_balance'], 2) }}</span>
                    </div>
                </div>
                <div class="vj-stat vj-stat-success">
                    <div class="vj-stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="vj-stat-body">
                        <span class="vj-stat-label">Approved / Ready to Pay</span>
                        <span class="vj-stat-value">Rp. {{ number_format($dashboard_data['ready_to_pay']['amount'], 0) }} | {{ $dashboard_data['ready_to_pay']['count'] }} payreqs</span>
                    </div>
                </div>
                <div class="vj-stat vj-stat-danger">
                    <div class="vj-stat-icon"><i class="fas fa-inbox"></i></div>
                    <div class="vj-stat-body">
                        <span class="vj-stat-label">Incoming not received yet</span>
                        <span class="vj-stat-value">Rp. {{ number_format($dashboard_data['incoming']['amount'], 0) }} | {{ $dashboard_data['incoming']['count'] }} payreqs</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
