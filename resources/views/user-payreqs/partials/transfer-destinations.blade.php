@php
    $paymentEditable = $paymentEditable ?? true;
    $selectedMethod = old('payment_method', $payreq->payment_method ?? 'cash');
    $payreqAmount = isset($payreq) && $payreq->amount !== null ? (int) $payreq->amount : null;

    $destinationRows = old('transfer_destinations');
    if (! is_array($destinationRows) || count($destinationRows) === 0) {
        if (isset($transferDestinations) && $transferDestinations->isNotEmpty()) {
            $destinationRows = $transferDestinations->map(static fn ($destination) => [
                'transfer_account_id' => $destination->transfer_account_id,
                'planned_amount' => $destination->planned_amount,
                'remark' => $destination->remark,
            ])->values()->all();
        } else {
            $destinationRows = [['transfer_account_id' => '', 'planned_amount' => '', 'remark' => '']];
        }
    }

    $nextRowIndex = (int) collect(array_keys($destinationRows))->max() + 1;
    $initialPlannedTotal = collect($destinationRows)->sum(static fn ($row) => (int) ($row['planned_amount'] ?? 0));
    $showBlockInitially = $selectedMethod === 'transfer';
@endphp

@if (! $paymentEditable && isset($payreq) && $payreq->payment_method === 'transfer' && isset($transferDestinations) && $transferDestinations->isNotEmpty())
    <div class="form-group" id="transfer-destinations-readonly">
        <label>Daftar Tujuan Transfer</label>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Rekening Tujuan</th>
                        <th class="text-right" style="width: 160px;">Rencana Nominal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transferDestinations as $destination)
                        <tr>
                            <td>{{ $destination->transferAccount?->displayLabel ?? '-' }}</td>
                            <td class="text-right">
                                {{ $destination->planned_amount !== null ? number_format($destination->planned_amount, 0, ',', '.') : '-' }}
                            </td>
                            <td>{{ $destination->remark ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif ($paymentEditable)
    <div class="form-group" id="transfer-destinations-section">
        <div id="transfer-destinations-block" style="{{ $showBlockInitially ? '' : 'display:none;' }}">
            <input type="hidden" name="transfer_destinations_present" value="1">

            <label class="d-block">Daftar Tujuan Transfer</label>
            <p class="text-muted small mb-2">
                Tentukan rekening tujuan dan rencana nominal transfer. Total rencana tidak boleh melebihi jumlah payreq.
            </p>

            @error('transfer_destinations')
                <div class="text-danger small mb-2">{{ $message }}</div>
            @enderror

            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-2" id="transfer-destinations-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width: 280px;">Rekening Tujuan</th>
                            <th style="width: 180px;">Rencana Nominal</th>
                            <th>Keterangan</th>
                            <th style="width: 70px;"></th>
                        </tr>
                    </thead>
                    <tbody id="transfer-destinations-body">
                        @foreach ($destinationRows as $index => $row)
                            <tr class="transfer-destination-row" data-row-index="{{ $index }}">
                                <td>
                                    <select name="transfer_destinations[{{ $index }}][transfer_account_id]"
                                        class="form-control form-control-sm transfer-destination-account select2bs4-transfer-destination"
                                        data-placeholder="Pilih Rekening Tujuan" style="width: 100%;">
                                        <option value=""></option>
                                        @foreach ($transferAccounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ (string) ($row['transfer_account_id'] ?? '') === (string) $account->id ? 'selected' : '' }}>
                                                {{ $account->displayLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('transfer_destinations.'.$index.'.transfer_account_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number" min="1" step="1"
                                        name="transfer_destinations[{{ $index }}][planned_amount]"
                                        value="{{ $row['planned_amount'] ?? '' }}"
                                        class="form-control form-control-sm transfer-destination-planned-amount">
                                    @error('transfer_destinations.'.$index.'.planned_amount')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="text" name="transfer_destinations[{{ $index }}][remark]"
                                        value="{{ $row['remark'] ?? '' }}"
                                        class="form-control form-control-sm transfer-destination-remark"
                                        maxlength="255">
                                    @error('transfer_destinations.'.$index.'.remark')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm btn-remove-transfer-destination-row"
                                        title="Hapus baris">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-right">
                                <span class="mr-3">
                                    Total Rencana:
                                    <strong id="transfer-destinations-planned-total">{{ number_format($initialPlannedTotal, 0, ',', '.') }}</strong>
                                </span>
                                <span>
                                    Jumlah Payreq:
                                    <strong id="transfer-destinations-payreq-total"
                                        data-initial-amount="{{ $payreqAmount ?? 0 }}">{{ $payreqAmount !== null ? number_format($payreqAmount, 0, ',', '.') : '0' }}</strong>
                                </span>
                                <span id="transfer-destinations-total-warning" class="text-danger small ml-2"
                                    style="{{ $payreqAmount !== null && $initialPlannedTotal > $payreqAmount ? '' : 'display:none;' }}">
                                    Total rencana melebihi jumlah payreq.
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-transfer-destination-row">
                <i class="fas fa-plus"></i> Tambah Baris
            </button>
        </div>
    </div>

    <template id="transfer-destination-row-template">
        <tr class="transfer-destination-row" data-row-index="__INDEX__">
            <td>
                <select name="transfer_destinations[__INDEX__][transfer_account_id]"
                    class="form-control form-control-sm transfer-destination-account select2bs4-transfer-destination"
                    data-placeholder="Pilih Rekening Tujuan" style="width: 100%;">
                    <option value=""></option>
                    @foreach ($transferAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->displayLabel }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" min="1" step="1"
                    name="transfer_destinations[__INDEX__][planned_amount]" value=""
                    class="form-control form-control-sm transfer-destination-planned-amount">
            </td>
            <td>
                <input type="text" name="transfer_destinations[__INDEX__][remark]" value=""
                    class="form-control form-control-sm transfer-destination-remark" maxlength="255">
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-transfer-destination-row"
                    title="Hapus baris">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    <script>
        window.payreqTransferDestinationRowIndex = {{ $nextRowIndex }};
    </script>
@endif
