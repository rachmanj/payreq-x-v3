<div class="col-lg-6">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0"><i class="fas fa-exchange-alt"></i> Today Tx</h3>
        </div>
        <div class="card-body">
            <div class="vj-stat-grid mb-0" style="grid-template-columns: repeat(2, 1fr);">
                <div class="vj-stat vj-stat-danger">
                    <div class="vj-stat-icon"><i class="fas fa-arrow-circle-up"></i></div>
                    <div class="vj-stat-body">
                        <span class="vj-stat-label">Today Outgoing</span>
                        <span class="vj-stat-value">Rp. {{ number_format($dashboard_data['today_outgoing']['amount'], 0) }} | {{ $dashboard_data['today_outgoing']['count'] }} payreqs</span>
                    </div>
                </div>
                <div class="vj-stat vj-stat-success">
                    <div class="vj-stat-icon"><i class="fas fa-arrow-circle-down"></i></div>
                    <div class="vj-stat-body">
                        <span class="vj-stat-label">Today Incoming</span>
                        <span class="vj-stat-value">Rp. {{ number_format($dashboard_data['today_incoming']['amount'], 0) }} | {{ $dashboard_data['today_incoming']['count'] }} payreqs</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
