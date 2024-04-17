<table>
    <thead>
        <tr>
            <td>#</td>
            <td>Akun No</td>
            <td>Remark</td>
            <td>RealizationNo</td>
            <td>Project</td>
            <td>CCenter</td>
            <td>DebitIDR</td>
            <td>CreditIDR</td>
        </tr>
    </thead>
    <tbody>
        @foreach($data['debits']['debit_details'] as $key => $item)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $item['account_number'] }}</td>
            <td>{{ $item['description'] }}</td>
            <td>{{ $item['realization_number'] }}</td>
            <td>{{ $item['project'] }}</td>
            <td>{{ $item['department'] }}</td>
            <td>{{ $item['amount'] }}</td>
            <td>0.00</td>
        </tr>
        @endforeach
        <tr>
            <td></td>
            <td>{{ $data['credit']['account_number'] }}</td>
            <td>{{ $data['verification']['nomor'] }}</td>
            <td>-</td>
            <td>{{ $data['verification']['project'] }}</td>
            <td>{{ $data['verification']['department'] }}</td>
            <td>0.00</td>
            <td>{{ $data['credit']['credit_amount'] }}</td>
        </tr>
    </tbody>
</table>
