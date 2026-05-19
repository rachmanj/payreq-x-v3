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
                    value="{{ old('details.'.$i.'.unit', data_get($detail, 'unit', '')) }}"></td>
            <td><input type="number" step="0.01" name="details[{{ $i }}][unit_price]"
                    class="form-control form-control-sm detail-unit-price"
                    value="{{ old('details.'.$i.'.unit_price', data_get($detail, 'unit_price', 0)) }}"></td>
            <td><input type="number" step="0.01" name="details[{{ $i }}][amount]"
                    class="form-control form-control-sm detail-amount"
                    value="{{ old('details.'.$i.'.amount', data_get($detail, 'amount', 0)) }}"></td>
            <td class="text-nowrap text-center">
                <button type="button" class="btn btn-xs btn-danger btn-remove-budget-detail" title="Remove line"
                    @if (count($detailRows) <= 1) disabled @endif>
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
</tbody>
