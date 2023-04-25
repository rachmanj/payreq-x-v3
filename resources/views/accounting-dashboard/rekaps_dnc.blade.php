<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><b>DNC</b></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-3 col-6">
                  <div class="description-block border-right">
                    <span class="description-text">PC Balance</span>
                    <h4 class="description-header">Rp.{{ number_format($accounts->where('account_no', '111115')->first()->balance) }}</h4>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-6">
                  <div class="description-block border-right">
                    <span class="description-text">Wait Payment</span>
                      <h5 class="description-header">Rp.{{ number_format($dnc_wait_payment->sum('payreq_idr'), 0) }}</h5>
                      <span class="description-percentage">{{ number_format($dnc_wait_payment->count(), 0) }} payreqs</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-6">
                  <div class="description-block border-right">
                    <span class="description-text">Today Outgoing</span>
                      <h5 class="description-header">Rp.{{ number_format($dnc_today_outgoings->sum('payreq_idr'), 0) }}</h5>
                      <span class="description-percentage"></span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-3 col-6">
                  <div class="description-block">
                      <span class="description-text">This Year Avg Days</span>
                      <h5 class="description-header">{{ $dnc_yearly_average_days }}</h5>
                      <span class="description-percentage">(Outgoing to Verify Date)</span>
                  </div>
                  <!-- /.description-block -->
                </div>
              </div>
              <!-- /.row -->
        </div>
    </div>
</div>