<div class="col-lg-6">
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">Info</h3>
        </div>
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    PC Balance
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                   Rp. {{ number_format($dashboard_data['today_pc_balance'], 2) }} 
                </span>
                </p>
            </div>
           
            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    Approved / Ready to Pay
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                    Rp. {{ number_format($dashboard_data['ready_to_pay']['amount'], 0) }} | {{ $dashboard_data['ready_to_pay']['count'] }} payreqs 
                </span>
                </p>
            </div>

            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    Incoming not received yet
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                    Rp. {{ number_format($dashboard_data['incoming']['amount'], 0) }} | {{ $dashboard_data['incoming']['count'] }} payreqs 
                </span>
                </p>
            </div>

            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    Cash-Out Journal Pending
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                    Rp. {{ number_format($dashboard_data['cj_to_create']['outgoings_amount'], 0) }} | {{ $dashboard_data['cj_to_create']['outgoings_count'] }} payreqs 
                </span>
                </p>
            </div>

            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    Cash-In Journal Pending
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                    Rp. {{ number_format($dashboard_data['cj_to_create']['incomings_amount'], 0) }} | {{ $dashboard_data['cj_to_create']['incomings_count'] }} payreqs 
                </span>
                </p>
            </div>

            <div class="d-flex justify-content-between align-items-center border-bottom mb-1">
                <p class="d-flex flex-column">
                    <span class="font-weight-bold">
                    Cash Journal Pending
                    </span>
                </p>
                <p class="d-flex flex-column text-right">
                <span>
                    Rp. {{ number_format($dashboard_data['cj_to_create']['pending_posting_amount'], 0) }} | {{ $dashboard_data['cj_to_create']['pending_posting_count'] }} journals 
                </span>
                </p>
            </div>
            
        </div>
    </div>
</div>