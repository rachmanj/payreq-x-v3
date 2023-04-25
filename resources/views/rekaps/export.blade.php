<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Posting Date</th>
            <th>Employee Name</th>
            <th>Payreq No</th>
            <th>RealzNo</th>
            <th>Amount</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rekaps as $rekap)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $rekap->posting_date ? date('d-M-Y', strtotime($rekap->posting_date)) : '-' }}</td>
                <td>{{ $rekap->employee }}</td>
                <td>{{ $rekap->payreq_no }}</td>
                <td>{{ $rekap->realization_no }}</td>
                <td class="text-right">{{ $rekap->amount }}</td>
                <td>{{ $rekap->remarks }}</td>
            </tr>
        @endforeach
    </tbody>
</table>