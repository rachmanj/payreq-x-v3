<table>
    <tr><td colspan="5"><strong>Eksposur faktur belum diterima — {{ $masaPajak }}</strong></td></tr>
    <tr>
        <th>Kode supplier</th>
        <th>Nama supplier</th>
        <th>Jumlah invoice</th>
        <th>Total PPN</th>
        <th>Umur max (hari)</th>
    </tr>
    @foreach ($groups as $g)
        <tr>
            <td>{{ $g['card_code'] }}</td>
            <td>{{ $g['card_name'] }}</td>
            <td>{{ $g['invoice_count'] }}</td>
            <td>{{ $g['total_vat'] }}</td>
            <td>{{ $g['max_age_days'] }}</td>
        </tr>
    @endforeach
</table>

<table>
    <tr><td colspan="5"><strong>Rincian per invoice</strong></td></tr>
    <tr>
        <th>Supplier</th><th>DocNum</th><th>Tanggal</th><th>VatSum</th><th>DocTotal</th>
    </tr>
    @foreach ($groups as $g)
        @foreach ($g['rows'] as $row)
            <tr>
                <td>{{ $g['card_name'] }}</td>
                <td>{{ $row['doc_num'] ?? '' }}</td>
                <td>{{ $row['doc_date'] ?? '' }}</td>
                <td>{{ $row['vat_sum'] ?? '' }}</td>
                <td>{{ $row['doc_total'] ?? '' }}</td>
            </tr>
        @endforeach
    @endforeach
</table>
