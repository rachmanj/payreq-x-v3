<tr data-row-id="row_{{ $index }}">
    <td class="line-number">{{ is_numeric($index) ? $index + 1 : '' }}</td>
    <td>
        <div class="position-relative">
            <input type="text" id="account_number_row_{{ $index }}" name="lines[{{ $index }}][account_code]"
                class="form-control form-control-sm" value="{{ $line['account_code'] ?? '' }}" placeholder="Account" autocomplete="off" required>
            <div id="account_suggestions_row_{{ $index }}" class="account-suggestions-dropdown list-group shadow-sm border bg-white"></div>
        </div>
        <input type="text" id="account_name_row_{{ $index }}" class="form-control form-control-sm border-0 bg-transparent p-0 mt-1" placeholder="Account name" disabled>
    </td>
    <td>
        <select name="lines[{{ $index }}][debit_credit]" class="form-control form-control-sm line-debit-credit" required>
            <option value="debit" {{ ($line['debit_credit'] ?? '') === 'debit' ? 'selected' : '' }}>Debit</option>
            <option value="credit" {{ ($line['debit_credit'] ?? '') === 'credit' ? 'selected' : '' }}>Credit</option>
        </select>
    </td>
    <td>
        <input type="number" name="lines[{{ $index }}][{{ $amountField }}]" class="form-control form-control-sm line-amount"
            value="{{ $line[$amountField] ?? $line['amount'] ?? '' }}" step="0.01" min="{{ $requireAmount ? '0.01' : '0' }}"
            {{ $requireAmount ? 'required' : '' }}>
    </td>
    <td>
        <select name="lines[{{ $index }}][project]" class="form-control form-control-sm">
            <option value="">—</option>
            @foreach ($projects as $project)
                <option value="{{ $project->code }}" {{ ($line['project'] ?? '') === $project->code ? 'selected' : '' }}>
                    {{ $project->code }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <select name="lines[{{ $index }}][cost_center]" class="form-control form-control-sm">
            <option value="">—</option>
            @foreach ($departments as $department)
                <option value="{{ $department->sap_code ?? $department->akronim }}"
                    {{ ($line['cost_center'] ?? '') === ($department->sap_code ?? $department->akronim) ? 'selected' : '' }}>
                    {{ $department->akronim }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text" name="lines[{{ $index }}][description]" class="form-control form-control-sm"
            value="{{ $line['description'] ?? '' }}">
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn"><i class="fas fa-times"></i></button>
    </td>
</tr>
