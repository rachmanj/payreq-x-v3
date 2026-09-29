<table>
    <tr><td colspan="6"><strong>Ringkasan PPN Masa {{ $masaPajak }}</strong></td></tr>
    @if ($period)
        <tr>
            <td>PK (SAP)</td><td>{{ $period->pk_total }}</td>
            <td>PM (SAP)</td><td>{{ $period->pm_total }}</td>
            <td>KB/LB</td><td>{{ $period->kb_lb }}</td>
        </tr>
        <tr>
            <td>Selisih SAP-App</td><td>{{ $period->diff_sap_app }}</td>
            <td>Selisih PK-PM App</td><td>{{ $period->diff_pk_pm }}</td>
            <td>Status</td><td>{{ $period->status }}</td>
        </tr>
    @endif
</table>

<table>
    <thead>
        <tr><td colspan="7"><strong>PPN Masukan</strong></td></tr>
        <tr>
            <th>Vendor</th><th>No Faktur</th><th>Tanggal</th><th>DPP</th><th>PPN</th><th>Tarif</th><th>Validasi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($masukan as $row)
            <tr>
                <td>{{ $row->customer->name }}</td>
                <td>{{ $row->faktur_no }}</td>
                <td>{{ $row->faktur_date }}</td>
                <td>{{ $row->dpp }}</td>
                <td>{{ $row->ppn }}</td>
                <td>{{ $row->ppn_rate }}</td>
                <td>{{ $row->validation_status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table>
    <thead>
        <tr><td colspan="6"><strong>PPN Keluaran</strong></td></tr>
        <tr>
            <th>Customer</th><th>No Faktur</th><th>Tanggal</th><th>DPP</th><th>PPN</th><th>SAP</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($keluaran as $row)
            <tr>
                <td>{{ $row->customer->name }}</td>
                <td>{{ $row->faktur_no }}</td>
                <td>{{ $row->faktur_date }}</td>
                <td>{{ $row->dpp }}</td>
                <td>{{ $row->ppn }}</td>
                <td>{{ $row->sap_submission_status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
