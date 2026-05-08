@php
    $allocations = $payreq->anggaranAllocations;
@endphp
<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
        <thead class="thead-light">
            <tr>
                <th style="width:3rem">#</th>
                <th>Anggaran</th>
                <th>Remarks</th>
                <th class="text-right" style="width:9rem">Amount (IDR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($allocations as $allocRow)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if ($allocRow->anggaran)
                            @php
                                $ang = $allocRow->anggaran;
                                $line =
                                    'No. '.$ang->nomor
                                    .(filled($ang->rab_no) && (string) $ang->rab_no !== (string) ($ang->nomor ?? '')
                                        ? ' | RAB '.$ang->rab_no
                                        : '')
                                    .' | '.$ang->rab_project
                                    .' | '.substr((string) ($ang->description ?? ''), 0, 120);
                            @endphp
                            {{ $line }}
                        @else
                            Anggaran #{{ $allocRow->anggaran_id }}
                        @endif
                    </td>
                    <td>{{ $allocRow->remarks ?: '–' }}</td>
                    <td class="text-right">{{ number_format((float) $allocRow->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total</th>
                <th class="text-right">{{ number_format((float) $payreq->amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
