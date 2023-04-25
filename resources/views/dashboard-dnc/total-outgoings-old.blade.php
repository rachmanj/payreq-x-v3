<div class="col-4">
    <div class="card-info">
        <div class="card-header">
            <div class="card-title"><h5>This Month Outgoing</h5></div>
            <div class="float-right"><h5>Rp.{{ number_format($this_month_payreqs->sum('payreq_idr'), 0) }}</h5></div>
        </div>
    </div>
</div>
<div class="col-4">
    <div class="card-info">
        <div class="card-header">
            <div class="card-title"><h5>This Year Outgoing</h5></div>
            <div class="float-right"><h5>Rp.{{ number_format($this_year_payreqs->sum('payreq_idr'), 0) }}</h5></div>
        </div>
    </div>
</div>
<div class="col-4">
    <div class="card-info">
        <div class="card-header">
            <div class="card-title"><h5>This Year Realization</h5></div>
            <div class="float-right"><h5>Rp.{{ number_format($this_year_realization, 0) }}</h5></div>
        </div>
    </div>
</div>