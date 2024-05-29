<table>
    <thead>
        <tr>
            <td>Akun No</td>
            <td>Remark</td>
            <td>Project</td>
            <td>CCenter</td>
            <td>DebitIDR</td>
            <td>CreditIDR</td>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $key => $item)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $item['account_number'] }}</td>
            <td>{{ $item['description'] }}</td>
            <td>{{ $item['project'] }}</td>
            <td>{{ $item['cost_center'] }}</td>
            @if ($item['d_c'] == 'debit')
                <td>{{ $item['amount'] }}</td>
                <td>0</td>
            @endif
            @if ($item['d_c'] == 'credit')
                <td>0</td>
                <td>{{ $item['amount'] }}</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>
