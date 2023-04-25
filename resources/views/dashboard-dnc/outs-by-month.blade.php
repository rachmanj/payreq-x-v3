<div class="card card-info">
    <div class="card-header border-transparent">
        <h3 class="card-title"><b>Monthly Outgoings</b> <small>(IDR 000)</small></h3>
    </div>
    <div class="card-body p-0">
        <table class="table m-0 table-striped table-bordered">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($monthly_outgoings_amount as $item)
                    <tr>
                        <td>{{ date('M', strtotime('2022-' . $item->month . '-01')) }}</td>
                        <td class="text-right">{{ number_format($item->total_amount / 1000, 0) }}</td>
                    </tr>
                @endforeach
                <th>Total</th>
                <th class="text-right">{{ number_format($monthly_outgoings_amount->sum('total_amount') / 1000, 0) }}</th>
            </tbody>
        </table>
    </div>
</div>