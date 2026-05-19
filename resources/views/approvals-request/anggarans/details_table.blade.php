@php
    $details = $anggaran->details ?? collect();
@endphp
@if ($details->isNotEmpty())
    <div class="row {{ ($compact ?? false) ? 'mb-0' : '' }}">
        <div class="col-12">
            <h5 class="{{ ($compact ?? false) ? 'mb-2' : 'mt-2' }}">Budget lines</h5>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Qty</th>
                            <th>Unit</th>
                            <th class="text-right">Unit price</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $line)
                            <tr>
                                <td>{{ $line->description ?: '-' }}</td>
                                <td class="text-right">{{ number_format((float) $line->qty, 4) }}</td>
                                <td>{{ $line->unit ?: '-' }}</td>
                                <td class="text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="text-right">{{ number_format((float) $line->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">Total</th>
                            <th class="text-right">{{ number_format((float) $details->sum('amount'), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endif
