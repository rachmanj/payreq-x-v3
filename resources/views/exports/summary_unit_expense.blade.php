<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Unit No</th>
            <th>Fuel</th>
            <th>Service</th>
            <th>Other</th>
            <th>Tax</th>
            <th>Total</th>
            {{-- FCPKM, Est. FCPL, Last KM - commented for later use
            <th>Est. FCPL</th>
            --}}
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $row)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $row->unit_no }}</td>
            <td>{{ $row->fuel_amount }}</td>
            <td>{{ $row->service_amount }}</td>
            <td>{{ $row->other_amount }}</td>
            <td>{{ $row->tax_amount }}</td>
            <td>{{ $row->total_amount }}</td>
            {{-- Est. FCPL - commented for later use
            <td>{{ ($row->fuel_qty ?? 0) > 0 ? round($row->fuel_amount / $row->fuel_qty, 0) : '' }}</td>
            --}}
        </tr>
        @endforeach
    </tbody>
</table>
