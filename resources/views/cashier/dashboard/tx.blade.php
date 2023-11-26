<div class="col-lg-6">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Today Tx</h3>
        </div>
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    Today Outgoing
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                    Rp. {{ number_format($dashboard_data['today_outgoing']['amount'], 0) }} | {{ $dashboard_data['today_outgoing']['count'] }} payreqs 
                </span>
                </p>
            </div>

            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    Today Incoming
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                    Rp. {{ number_format($dashboard_data['today_incoming']['amount'], 0) }} | {{ $dashboard_data['today_incoming']['count'] }} payreqs 
                </span>
                </p>
            </div>
            
        </div>
    </div>
</div>