@php
    $detailRows = $detailRows ?? [[]];
@endphp
<tbody id="budget-detail-body">
    @foreach ($detailRows as $i => $detail)
        <tr class="budget-detail-row">
            <td><input type="text" name="details[{{ $i }}][description]" class="form-control form-control-sm"
                    value="{{ old('details.'.$i.'.description', data_get($detail, 'description', '')) }}"></td>
            <td><input type="number" step="0.0001" name="details[{{ $i }}][qty]"
                    class="form-control form-control-sm detail-qty"
                    value="{{ old('details.'.$i.'.qty', data_get($detail, 'qty', 1)) }}"></td>
            <td><input type="text" name="details[{{ $i }}][unit]" class="form-control form-control-sm"
                    value="{{ old('details.'.$i.'.unit', data_get($detail, 'unit', 'each')) }}"></td>
            <td><input type="number" step="0.01" name="details[{{ $i }}][unit_price]"
                    class="form-control form-control-sm detail-unit-price"
                    value="{{ old('details.'.$i.'.unit_price', data_get($detail, 'unit_price', 0)) }}"></td>
            <td>
                @php
                    $lineAmount = old('details.'.$i.'.amount', data_get($detail, 'amount', 0));
                    $lineAmountNumeric = is_numeric($lineAmount) ? (float) $lineAmount : 0.0;
                    $lineAmountDisplay = number_format($lineAmountNumeric, 2, '.', ',');
                @endphp
                <input type="hidden" name="details[{{ $i }}][amount]" class="detail-amount"
                    value="{{ $lineAmountNumeric }}">
                <input type="text" class="form-control form-control-sm bg-light text-right detail-amount-display"
                    value="{{ $lineAmountDisplay }}" readonly autocomplete="off">
            </td>
            <td class="text-nowrap text-center">
                <button type="button" class="btn btn-xs btn-danger btn-remove-budget-detail" title="Remove line"
                    @if (count($detailRows) <= 1) disabled @endif>
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
</tbody>
