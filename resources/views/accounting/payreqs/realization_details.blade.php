<table class="table table-striped">
    <thead>
        <tr>
            <th>#</td>
            <th>Desc</td>
            <th class="text-right">Amount (IDR)</th>
        </tr>
    </thead>
    @if ($payreq->realization->realizationDetails) 
        <tbody>
            @foreach ($payreq->realization->realizationDetails as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->description }} 
                        @if ($item->unit_no != null)
                            <br/>
                            @if ($item->type === 'fuel')
                                <small>Unit No: {{ $item->unit_no }}, {{ $item->type }} {{ $item->qty }} {{ $item->uom }}. HM: {{ $item->km_position }}</small>
                            @else
                                <small>{{ $item->type }}, HM: {{ $item->km_position }}</small>
                            @endif 
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-right">Total</td>
                <td class="text-right"><b>{{ number_format($payreq->realization->realizationDetails->sum('amount'), 2) }}</b></td>
            </tr>
        </tfoot>
    @else
        <tbody>
            <tr>
                <td colspan="4" class="text-center">No Data Found</td>
            </tr>
        </tbody>
    @endif
</table>
    
