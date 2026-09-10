<table>
    <thead>
        <tr>
            <th colspan="10"><strong>Ringkasan Biaya per Kegiatan</strong></th>
        </tr>
        <tr>
            <th>Kode</th>
            <th>Nama Kegiatan</th>
            <th>Periode</th>
            <th>Project</th>
            <th>Mode</th>
            <th>Akun Kegiatan</th>
            <th>Jumlah Realisasi</th>
            <th>Jumlah Nota</th>
            <th>Total Biaya</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($activities as $activity)
            <tr>
                <td>{{ $activity->code }}</td>
                <td>{{ $activity->name }}</td>
                <td>{{ $activity->periode }}</td>
                <td>{{ $activity->project ?? 'Semua' }}</td>
                <td>{{ $activity->mode === 'reklasifikasi' ? 'Reklasifikasi' : 'Tanpa Reklasifikasi' }}</td>
                <td>
                    @if ($activity->account)
                        {{ $activity->account->account_number }} — {{ $activity->account->account_name }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ (int) $activity->realization_count }}</td>
                <td>{{ (int) $activity->note_count }}</td>
                <td>{{ (int) $activity->total_cost }}</td>
                <td>{{ $activity->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<br>

<table>
    <thead>
        <tr>
            <th colspan="9"><strong>Detail Nota per Kegiatan</strong></th>
        </tr>
        <tr>
            <th>Kode Kegiatan</th>
            <th>Nama Kegiatan</th>
            <th>Tanggal</th>
            <th>Nomor Realisasi</th>
            <th>Deskripsi</th>
            <th>Akun Asli</th>
            <th>Cost Center</th>
            <th>Jumlah</th>
            <th>Reklasifikasi?</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($notaDetails as $detail)
            <tr>
                <td>{{ $detail->activity_code }}</td>
                <td>{{ $detail->activity_name }}</td>
                <td>{{ $detail->expense_date ? $detail->expense_date->format('Y-m-d') : '' }}</td>
                <td>{{ $detail->realization?->nomor }}</td>
                <td>{{ $detail->description }}</td>
                <td>
                    @if ($detail->account)
                        {{ $detail->account->account_number }} — {{ $detail->account->account_name }}
                    @endif
                </td>
                <td>{{ $detail->department?->sap_code }}</td>
                <td>{{ (int) $detail->amount }}</td>
                <td>{{ $detail->reklasifikasi }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
