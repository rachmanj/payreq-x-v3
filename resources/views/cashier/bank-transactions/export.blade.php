<table>
    <thead>
        <tr>
            <td>#</td>
            <td>Akun No</td>
            <td>Remark</td>
            <td>Project</td>
            <td>CCenter</td>
            <td>DebitIDR</td>
            <td>CreditIDR</td>
            <td>PayreqNo</td>
            <td>VJNo</td>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $key => $item)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ $item['account_code'] }}</td>
                <td>{{ $item['description'] }}</td>
                <td>{{ $item['project'] }}</td>
                <td>{{ $item['cost_center'] }}</td>
                @if ($item['debit_credit'] == 'debit')
                    <td>{{ $item['amount'] }}</td>
                    <td>0</td>
                @endif
                @if ($item['debit_credit'] == 'credit')
                    <td>0</td>
                    <td>{{ $item['amount'] }}</td>
                @endif
                <td>{{ $item['realization_no'] }}</td>
                <td>{{ $item['vj_no'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
