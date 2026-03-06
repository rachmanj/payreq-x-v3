<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Unit No</th>
            <th>Jan</th>
            <th>Feb</th>
            <th>Mar</th>
            <th>Apr</th>
            <th>May</th>
            <th>Jun</th>
            <th>Jul</th>
            <th>Aug</th>
            <th>Sep</th>
            <th>Oct</th>
            <th>Nov</th>
            <th>Dec</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $row)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $row->unit_no }}</td>
            <td>{{ $row->jan }}</td>
            <td>{{ $row->feb }}</td>
            <td>{{ $row->mar }}</td>
            <td>{{ $row->apr }}</td>
            <td>{{ $row->may }}</td>
            <td>{{ $row->jun }}</td>
            <td>{{ $row->jul }}</td>
            <td>{{ $row->aug }}</td>
            <td>{{ $row->sep }}</td>
            <td>{{ $row->oct }}</td>
            <td>{{ $row->nov }}</td>
            <td>{{ $row->dec }}</td>
            <td>{{ $row->total_amount }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
