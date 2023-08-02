<h4>Notification from Payment Request System</h4>

<p>Dear Mr/Ms/Mrs {{ $user }}</p>
<p>This email is to notify your ongoing Payment Request</p>
<table style="width:80%" border="1">
    <tr>
        <th colspan="6" align="left">Approved Payreqs</th>
    </tr>
    <tr style='background-color:LightGray'>
        <th>#</th>
        <th align="center">PayreqNo</th>
        <th>Type</th>
        <th align="center">Approve Date</th>
        <th align="right">IDR</th>
        <th align="right">Days</th>
    </tr>
    @if ($just_approved->count() > 0)
        @foreach ($just_approved->get() as $payreq)
        <tr>
            <td align="right">{{ $loop->iteration }}</td>
            <td align="center">{{ $payreq->payreq_num }}</td>
            <td>{{ $payreq->payreq_type }}</td>
            <td align="center">{{ date('d-m-Y', strtotime($payreq->approve_date)) }}</td>
            <td align="right">{{ number_format($payreq->payreq_idr, 0) }}</td>
            <td align="right">{{ $payreq->days }}</td>
        </tr>
        @endforeach
    @else
        <tr>
        <td colspan="6" align="center">Tidak ada data</td>
        </tr>
    @endif
</table>

<hr />

<table style="width:80%" border="1">
    <tr>
        <th colspan="5" align="left">Payreqs Belum Realisasi</th>
    </tr>
    <tr style='background-color:LightGray'>
        <th>#</th>
        <th align="center">PayreqNo</th>
        <th align="center">Approve Date</th>
        <th align="right">IDR</th>
        <th align="right">Days</th>
    </tr>
    @if ($not_realization->count() > 0)
        @foreach ($not_realization->get() as $payreq)
            <tr style="{{ $payreq->days > 7 ? 'color:red' : 'color:black' }}">
                <td align="right">{{ $loop->iteration }}</td>
                <td align="center">{{ $payreq->payreq_num }}</td>
                <td align="center">{{ date('d-m-Y', strtotime($payreq->outgoing_date)) }}</td>
                <td align="right">{{ number_format($payreq->payreq_idr, 0) }}</td>
                <td align="right">{{ $payreq->days }}</td>
            </tr>
        @endforeach
    @else
        <tr>
            <td colspan="5" align="center">Tidak ada data</td>
        </tr>
    @endif
</table>

<hr />
<table style="width:80%" border="1">
    <tr>
        <th colspan="7" align="left">Dokumen Belum Verifikasi</th>
    </tr>
    <tr style='background-color:LightGray'>
        <th>#</th>
        <th align="center">PayreqNo</th>
        <th align="center">Outgoing</th>
        <th align="center">Realization No</th>
        <th align="center">Realization Date</th>
        <th align="right">IDR</th>
        <th align="right">Days</th>
      </tr>
    @if ($not_verify->count() > 0)
        @foreach ($not_verify->get() as $payreq)
            <tr style="{{ $payreq->days > 3 ? 'color:red' : 'color:black' }}">
                <td align="right">{{ $loop->iteration }}</td>
                <td align="center">{{ $payreq->payreq_num }}</td>
                <td align="center">{{ date('d-m-Y', strtotime($payreq->outgoing_date)) }}</td>
                <td align="center">{{ $payreq->realization_num }}</td>
                <td align="center">{{ date('d-m-Y', strtotime($payreq->realization_date)) }}</td>
                <td align="right">{{ number_format($payreq->payreq_idr, 0) }}</td>
                <td align="right">{{ $payreq->days }}</td>
            </tr>
        @endforeach
    @else
        <tr>
        <td colspan="7" align="center">Tidak ada data</td>
        </tr>
    @endif
</table>
<hr />
<p>If this information does not match your records, please inform immediately to Accounting Dept. Regards</p>
<small>This notification is auto-generated from Payment Request System</small>
          