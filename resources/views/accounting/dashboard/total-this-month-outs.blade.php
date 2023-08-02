<div class="col-6">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><h5>Today Outgoing <small>(non DnC)</small></h5></div>
            <div class="float-right"><h5>Rp.{{ number_format($today_outgoings->sum('payreq_idr'), 0) }}</h5></div>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><h5>This Month Outgoing <small>(non DnC)</small></h5></div>
            <div class="float-right"><h5>Rp.{{ number_format($this_month_outgoings->sum('payreq_idr')) }}</h5></div>
        </div>
    </div>
</div>


