<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Document Number</th>
            <th>Date</th>
            <th>Project</th>
            <th>Cashier</th>
            <th>100rb</th>
            <th>50rb</th>
            <th>20rb</th>
            <th>10rb</th>
            <th>5rb</th>
            <th>2rb</th>
            <th>1rb</th>
            <th>500</th>
            <th>100</th>
            <th>System Amount</th>
            <th>Physical Amount</th>
            <th>SAP Amount</th>
            <th>System Variance</th>
            <th>SAP Variance</th>
            <th>Pemeriksa 1</th>
            <th>Pemeriksa 2</th>
            <th>Approved By</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pcbcs as $index => $pcbc)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $pcbc->nomor }}</td>
            <td>{{ \Carbon\Carbon::parse($pcbc->pcbc_date)->format('d-M-Y') }}</td>
            <td>{{ $pcbc->project }}</td>
            <td>{{ $pcbc->createdBy->name ?? 'N/A' }}</td>
            <td>{{ number_format($pcbc->kertas_100rb ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_50rb ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_20rb ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_10rb ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_5rb ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_2rb ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_1rb ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_500 ?? 0) }}</td>
            <td>{{ number_format($pcbc->kertas_100 ?? 0) }}</td>
            <td>{{ number_format($pcbc->system_amount ?? 0, 2) }}</td>
            <td>{{ number_format($pcbc->fisik_amount ?? 0, 2) }}</td>
            <td>{{ number_format($pcbc->sap_amount ?? 0, 2) }}</td>
            <td>{{ number_format($pcbc->system_variance, 2) }}</td>
            <td>{{ number_format($pcbc->sap_variance, 2) }}</td>
            <td>{{ $pcbc->pemeriksa1 }}</td>
            <td>{{ $pcbc->pemeriksa2 ?? 'N/A' }}</td>
            <td>{{ $pcbc->approved_by ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
