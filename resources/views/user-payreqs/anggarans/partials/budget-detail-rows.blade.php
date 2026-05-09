<tbody id="budget-detail-body">
@forelse (($details ?? collect()) as $i => $detail)
    <tr class="budget-detail-row">
        <td>
            <select name="details[{{ $i }}][account_id]" class="form-control form-control-sm">
                <option value="">—</option>
                @foreach ($accounts as $acc)
                    <option value="{{ $acc->id }}" @selected((int) ($detail->account_id ?? 0) === (int) $acc->id)>{{ $acc->account_number }} — {{ $acc->account_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="details[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $detail->description }}"></td>
        <td><input type="number" step="0.0001" name="details[{{ $i }}][qty]" class="form-control form-control-sm detail-qty" value="{{ $detail->qty ?? 1 }}"></td>
        <td><input type="text" name="details[{{ $i }}][unit]" class="form-control form-control-sm" value="{{ $detail->unit }}"></td>
        <td><input type="number" step="0.01" name="details[{{ $i }}][unit_price]" class="form-control form-control-sm detail-unit-price" value="{{ $detail->unit_price ?? 0 }}"></td>
        <td><input type="number" step="0.01" name="details[{{ $i }}][amount]" class="form-control form-control-sm detail-amount" value="{{ $detail->amount ?? 0 }}"></td>
        <td class="text-nowrap"></td>
    </tr>
@empty
    <tr class="budget-detail-row">
        <td>
            <select name="details[0][account_id]" class="form-control form-control-sm">
                <option value="">—</option>
                @foreach ($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->account_number }} — {{ $acc->account_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="details[0][description]" class="form-control form-control-sm"></td>
        <td><input type="number" step="0.0001" name="details[0][qty]" class="form-control form-control-sm detail-qty" value="1"></td>
        <td><input type="text" name="details[0][unit]" class="form-control form-control-sm"></td>
        <td><input type="number" step="0.01" name="details[0][unit_price]" class="form-control form-control-sm detail-unit-price" value="0"></td>
        <td><input type="number" step="0.01" name="details[0][amount]" class="form-control form-control-sm detail-amount" value="0"></td>
        <td class="text-nowrap"></td>
    </tr>
@endforelse
</tbody>
