<div class="card card-info">
    <div class="card-header border-transparent">
        <h3 class="card-title"><b>Monthly Outgoings via Payreqs</b></h3>
    </div>
    <div class="card-body p-0">
        <table class="table m-0 table-striped table-bordered">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Avg Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($get_months_this_year_outgoings as $outgoing)
                    <tr>
                        <td>{{ date('M', strtotime('2023-' . $outgoing->month . '-01')) }}</td>
                        <td class="text-right">{{ $this_year_outgoings['amounts']->where('month', $outgoing->month)->first() ? number_format($this_year_outgoings['amounts']->where('month', $outgoing->month)->first()->amount, 0) : '-' }}</td>
                        <td class="text-right">{{ $this_year_outgoings['counts']->where('month', $outgoing->month)->first() ? number_format($this_year_outgoings['counts']->where('month', $outgoing->month)->first()->lembars, 0) : '-' }}</td>
                        <td class="text-right">{{ $this_year_outgoings['averages']->where('month', $outgoing->month)->first() ? number_format($this_year_outgoings['averages']->where('month', $outgoing->month)->first()->avg_days, 2) : '-' }}</td>
                    </tr>
                @endforeach
                <th>Total</th>
                <th class="text-right">{{ number_format($this_year_outgoings['amounts']->sum('amount'), 0) }}</th>
                <th class="text-right">{{ number_format($this_year_outgoings['counts']->sum('lembars'), 0) }}</th>
                <th class="text-right">{{ $yearly_average_days }}</th>
            </tbody>
        </table>
    </div>
</div>