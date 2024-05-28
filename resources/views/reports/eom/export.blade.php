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
        @foreach($data as $item)
        <tr>
            <td>{{ $item['debit']['account_number'] }}</td>
            <td>{{ $item['debit']['description'] }}</td>
            <td>{{ $item['debit']['project_code'] }}</td>
            <td>{{ $item['debit']['ccenter'] }}</td>
            <td>{{ str_replace(',', '', $item['debit']['amount']) }}</td>
            <td>0.00</td>
        </tr>
        <tr>
            <td>{{ $item['credit']['account_number'] }}</td>
            <td>{{ $item['credit']['description'] }}</td>
            <td>{{ $item['credit']['project_code'] }}</td>
            <td>{{ $item['credit']['ccenter'] }}</td>
            <td>0.00</td>
            <td>{{ str_replace(',', '', $item['credit']['amount']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
