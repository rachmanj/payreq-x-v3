@php
    $advanceAllocationRows =
        $payreq->isAdvanceMultiBudget() && $payreq->anggaranAllocations->isNotEmpty();
@endphp
@if ($advanceAllocationRows)
    @foreach ($payreq->anggaranAllocations as $allocRow)
        <tr>
            <td style="border: 1px solid black;">{{ $loop->iteration }}</td>
            <td style="border: 1px solid black;">
                @if ($loop->first)
                    {{ $payreq->remarks }} <br>
                @endif
                @if ($allocRow->anggaran)
                    @php
                        $ang = $allocRow->anggaran;
                        $rabLine =
                            'RAB No. '.$ang->nomor
                            .(filled($ang->rab_no) && (string) $ang->rab_no !== (string) ($ang->nomor ?? '')
                                ? ' | RAB '.$ang->rab_no
                                : '')
                            .' | '.$ang->rab_project
                            .' | '
                            .substr((string) ($ang->description ?? ''), 0, 100);
                    @endphp
                    {{ $rabLine }}
                @else
                    Anggaran #{{ $allocRow->anggaran_id }}
                @endif
                @if (filled($allocRow->remarks))
                    <br><small>Note: {{ $allocRow->remarks }}</small>
                @endif
            </td>
            <td class="text-right" style="border: 1px solid black;">
                {{ number_format((float) $allocRow->amount, 2) }}</td>
        </tr>
    @endforeach
@else
    <tr>
        <td style="border: 1px solid black;">1</td>
        <td style="border: 1px solid black;">{{ $payreq->remarks }} <br>
            {{ $payreq->rab_id ? 'RAB No. '.$payreq->anggaran->nomor.' | '.$payreq->anggaran->rab_project.' | '.substr((string) ($payreq->anggaran->description ?? ''), 0, 100) : '' }}
        </td>
        <td class="text-right" style="border: 1px solid black;">{{ number_format($payreq->amount, 2) }}</td>
    </tr>
@endif
