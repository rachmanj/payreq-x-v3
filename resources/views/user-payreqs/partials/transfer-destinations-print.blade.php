@php
    $allocationTransferRows = collect();
    foreach ($payreq->anggaranAllocations as $index => $allocation) {
        if (! $allocation->transfer_account_id) {
            continue;
        }

        $allocationTransferRows->push([
            'row_number' => $index + 1,
            'remarks' => $allocation->remarks,
            'amount' => $allocation->amount,
            'transferAccount' => $allocation->transferAccount,
            'planned_amount' => $allocation->planned_amount,
        ]);
    }
@endphp

@if ($allocationTransferRows->isNotEmpty())
    <div class="row" id="allocation-transfer-destinations-print">
        <div class="col-12 table-responsive">
            <p class="mb-1"><strong>Rekening Tujuan per Baris Transaksi</strong></p>
            <table class="table table-bordered table-sm mb-0" style="border: 1px solid black;">
                <thead>
                    <tr>
                        <th style="border: 1px solid black;">No</th>
                        <th style="border: 1px solid black;">Uraian Baris</th>
                        <th class="text-right" style="border: 1px solid black;">Nominal Baris</th>
                        <th style="border: 1px solid black;">Rekening Tujuan</th>
                        <th class="text-right" style="border: 1px solid black;">Rencana Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allocationTransferRows as $planRow)
                        <tr>
                            <td style="border: 1px solid black;" class="text-center">{{ $planRow['row_number'] }}</td>
                            <td style="border: 1px solid black;">{{ $planRow['remarks'] ?: '–' }}</td>
                            <td style="border: 1px solid black;" class="text-right">
                                {{ number_format((float) $planRow['amount'], 0, ',', '.') }}
                            </td>
                            <td style="border: 1px solid black;">
                                {{ $planRow['transferAccount']?->displayLabel ?? '–' }}
                            </td>
                            <td style="border: 1px solid black;" class="text-right">
                                {{ $planRow['planned_amount'] !== null ? number_format($planRow['planned_amount'], 0, ',', '.') : '–' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($payreq->transferDestinations->isNotEmpty())
    <div class="row" id="transfer-destinations-print">
        <div class="col-12 table-responsive">
            <p class="mb-1"><strong>Daftar Tujuan Transfer</strong></p>
            <table class="table table-bordered table-sm mb-0" style="border: 1px solid black;">
                <thead>
                    <tr>
                        <th style="border: 1px solid black;">No</th>
                        <th style="border: 1px solid black;">Rekening Tujuan</th>
                        <th class="text-right" style="border: 1px solid black;">Rencana Nominal</th>
                        <th style="border: 1px solid black;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payreq->transferDestinations as $destination)
                        <tr>
                            <td style="border: 1px solid black;" class="text-center">{{ $loop->iteration }}</td>
                            <td style="border: 1px solid black;">
                                {{ $destination->transferAccount?->displayLabel ?? '-' }}
                            </td>
                            <td style="border: 1px solid black;" class="text-right">
                                {{ $destination->planned_amount !== null ? number_format($destination->planned_amount, 0, ',', '.') : '-' }}
                            </td>
                            <td style="border: 1px solid black;">{{ $destination->remark ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
