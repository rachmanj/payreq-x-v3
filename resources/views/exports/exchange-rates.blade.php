<table>
    <thead>
        <tr>
            <th>Currency From</th>
            <th>Currency To</th>
            <th>Currency Pair</th>
            <th>Exchange Rate</th>
            <th>Effective Date</th>
            <th>Created By</th>
            <th>Created At</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($exchangeRates as $rate)
            <tr>
                <td>{{ $rate->currency_from }}</td>
                <td>{{ $rate->currency_to }}</td>
                <td>{{ $rate->currency_pair }}</td>
                <td>{{ $rate->exchange_rate }}</td>
                <td>{{ $rate->effective_date->format('Y-m-d') }}</td>
                <td>{{ $rate->creator->name ?? 'N/A' }}</td>
                <td>{{ $rate->created_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $rate->updated_at->format('Y-m-d H:i:s') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
