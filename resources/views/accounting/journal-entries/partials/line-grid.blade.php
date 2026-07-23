@php
    $initialLines = $initialLines ?? [['account_code' => '', 'debit_credit' => 'debit', 'amount' => '', 'project' => '', 'cost_center' => '', 'description' => '']];
    $amountField = $amountField ?? 'amount';
    $amountLabel = $amountField === 'default_amount' ? 'Default Amount' : 'Amount';
    $requireAmount = $amountField === 'amount';
@endphp

<div class="table-responsive">
    <table class="table table-bordered" id="je-lines-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 18%">Account</th>
                <th style="width: 10%">Dr/Cr</th>
                <th style="width: 12%">{{ $amountLabel }}</th>
                <th style="width: 10%">Project</th>
                <th style="width: 12%">Cost Center</th>
                <th>Description</th>
                <th style="width: 5%"></th>
            </tr>
        </thead>
        <tbody id="je-lines-body">
            @foreach ($initialLines as $index => $line)
                @include('accounting.journal-entries.partials.line-row', [
                    'index' => $index,
                    'line' => $line,
                    'projects' => $projects,
                    'departments' => $departments,
                    'amountField' => $amountField,
                    'requireAmount' => $requireAmount,
                ])
            @endforeach
        </tbody>
        <tfoot>
            @if ($requireAmount)
                <tr>
                    <td colspan="3" class="text-right"><strong>Total Debit</strong></td>
                    <td><strong id="total-debit">0.00</strong></td>
                    <td colspan="2" class="text-right"><strong>Total Credit</strong></td>
                    <td><strong id="total-credit">0.00</strong></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="7" class="text-right"><strong>Difference</strong></td>
                    <td><strong id="total-diff" class="text-danger">0.00</strong></td>
                </tr>
            @endif
        </tfoot>
    </table>
</div>

<button type="button" class="btn btn-sm btn-outline-primary" id="add-line-btn">
    <i class="fas fa-plus"></i> Add Line
</button>

<template id="je-line-row-template">
    @include('accounting.journal-entries.partials.line-row', [
        'index' => '__INDEX__',
        'line' => ['account_code' => '', 'debit_credit' => 'debit', 'amount' => '', 'default_amount' => '', 'project' => '', 'cost_center' => '', 'description' => ''],
        'projects' => $projects,
        'departments' => $departments,
        'amountField' => $amountField,
        'requireAmount' => $requireAmount,
    ])
</template>

@push('styles')
    <style>
        .account-suggestions-dropdown {
            position: absolute;
            z-index: 1050;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            display: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const jeAmountField = @json($amountField);
        const jeRequireAmount = @json($requireAmount);

        function jeRecalcTotals() {
            if (!jeRequireAmount) return;
            let debit = 0, credit = 0;
            $('#je-lines-body tr').each(function() {
                const side = $(this).find('.line-debit-credit').val();
                const amt = parseFloat($(this).find('.line-amount').val()) || 0;
                if (side === 'debit') debit += amt;
                else credit += amt;
            });
            $('#total-debit').text(debit.toFixed(2));
            $('#total-credit').text(credit.toFixed(2));
            const diff = Math.abs(debit - credit);
            $('#total-diff').text(diff.toFixed(2)).toggleClass('text-danger', diff > 0.01).toggleClass('text-success', diff <= 0.01);
        }

        function jeRenumberRows() {
            $('#je-lines-body tr').each(function(i) {
                $(this).find('.line-number').text(i + 1);
                $(this).find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/lines\[\d+\]/, 'lines[' + i + ']'));
                    }
                });
                const rowId = 'row_' + i;
                $(this).attr('data-row-id', rowId);
                $(this).find('[id^="account_number_"]').attr('id', 'account_number_' + rowId);
                $(this).find('[id^="account_suggestions_"]').attr('id', 'account_suggestions_' + rowId);
                $(this).find('[id^="account_name_"]').attr('id', 'account_name_' + rowId);
            });
        }

        $('#add-line-btn').on('click', function() {
            const index = $('#je-lines-body tr').length;
            let html = $('#je-line-row-template').html().replace(/__INDEX__/g, index);
            $('#je-lines-body').append(html);
            jeRenumberRows();
            jeRecalcTotals();
        });

        $(document).on('click', '.remove-line-btn', function() {
            if ($('#je-lines-body tr').length <= 2) {
                Swal.fire('Minimum lines', 'A journal entry must have at least 2 lines.', 'warning');
                return;
            }
            $(this).closest('tr').remove();
            jeRenumberRows();
            jeRecalcTotals();
        });

        $(document).on('input change', '.line-amount, .line-debit-credit', jeRecalcTotals);

        let accountAutocompleteTimer = null;
        $(document).on('input', 'input[id^="account_number_"]', function() {
            const $input = $(this);
            const rowId = $input.attr('id').replace('account_number_', '');
            const $dropdown = $('#account_suggestions_' + rowId);
            const q = $input.val().trim();
            clearTimeout(accountAutocompleteTimer);
            if (q.length < 1) { $dropdown.hide().empty(); return; }
            accountAutocompleteTimer = setTimeout(function() {
                $.getJSON('{{ route('accounts.autocomplete') }}', { q: q }, function(rows) {
                    $dropdown.empty();
                    if (!rows || !rows.length) { $dropdown.hide(); return; }
                    rows.forEach(function(row) {
                        $('<button type="button" class="list-group-item list-group-item-action">')
                            .text(row.account_number + ' — ' + row.account_name)
                            .on('mousedown', function(e) {
                                e.preventDefault();
                                $('#account_number_' + rowId).val(row.account_number);
                                $('#account_name_' + rowId).val(row.account_name);
                            })
                            .appendTo($dropdown);
                    });
                    $dropdown.show();
                });
            }, 250);
        });

        $(document).on('blur', 'input[id^="account_number_"]', function() {
            const rowId = $(this).attr('id').replace('account_number_', '');
            setTimeout(function() { $('#account_suggestions_' + rowId).hide().empty(); }, 200);
        });

        $('#je-form').on('submit', function(e) {
            if (!jeRequireAmount) return true;
            let debit = 0, credit = 0;
            $('#je-lines-body tr').each(function() {
                const side = $(this).find('.line-debit-credit').val();
                const amt = parseFloat($(this).find('.line-amount').val()) || 0;
                if (side === 'debit') debit += amt; else credit += amt;
            });
            if (Math.abs(debit - credit) > 0.01) {
                e.preventDefault();
                Swal.fire('Not balanced', 'Debit (' + debit.toFixed(2) + ') must equal Credit (' + credit.toFixed(2) + ').', 'error');
                return false;
            }
        });

        $(function() { jeRecalcTotals(); });
    </script>
@endpush
