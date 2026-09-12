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
