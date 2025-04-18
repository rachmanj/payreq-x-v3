<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Date</th>
            <th>Description</th>
            <th>Document No</th>
            <th>Type</th>
            <th>Project</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($statementLines as $index => $line)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $line['date'] }}</td>
                <td>{{ $line['description'] }}</td>
                <td>{{ $line['doc_num'] }}</td>
                <td>{{ $line['doc_type'] }}</td>
                <td>{{ $line['project_code'] }}</td>
                <td>{{ str_replace(',', '.', str_replace('.', '', rtrim(ltrim($line['debit'], 'Rp '), ' ,-'))) }}</td>
                <td>{{ str_replace(',', '.', str_replace('.', '', rtrim(ltrim($line['credit'], 'Rp '), ' ,-'))) }}</td>
                <td>{{ str_replace(',', '.', str_replace('.', '', rtrim(ltrim($line['balance'], 'Rp '), ' ,-'))) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
