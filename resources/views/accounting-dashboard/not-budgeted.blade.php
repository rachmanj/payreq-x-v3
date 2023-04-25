<div class="card card-danger">
    <div class="card-header border-transparent">
        <h3 class="card-title"><b>Payreqs Not Budgeted</b></h3>
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
                @foreach ($payreqs_not_budgeted as $payreq)
                    <tr>
                        <td>{{ date('M Y', strtotime($payreq->month . '-01')) }}</td>
                        {{-- <td>{{ $payreq->month }}</td> --}}
                        <td class="text-right">{{ number_format($payreq->total_amount, 0) }}</td>
                    </tr>
                @endforeach
                <th>Total</th>
                <th class="text-right">{{ number_format($payreqs_not_budgeted->sum('total_amount'), 0) }}</th>
            </tbody>
        </table>
    </div>
</div>