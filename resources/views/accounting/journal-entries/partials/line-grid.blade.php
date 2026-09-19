@php
    $initialLines = $initialLines ?? [
        ['account_code' => '', 'debit_credit' => 'debit', 'currency' => 'IDR', 'amount' => '', 'project' => '', 'cost_center' => '', 'description' => ''],
        ['account_code' => '', 'debit_credit' => 'credit', 'currency' => 'IDR', 'amount' => '', 'project' => '', 'cost_center' => '', 'description' => ''],
    ];
    $amountField = $amountField ?? 'amount';
    $enableMulticurrency = $enableMulticurrency ?? ($amountField === 'amount');
    $amountLabel = $amountField === 'default_amount' ? 'Default Amount' : ($enableMulticurrency ? 'IDR (Rp)' : 'Amount');
    $requireAmount = $amountField === 'amount';
    $lineRowDefaults = [
        'account_code' => '',
        'debit_credit' => 'debit',
        'currency' => 'IDR',
        'amount' => '',
        'default_amount' => '',
        'fc_amount' => '',
        'exchange_rate' => '',
        'project' => '',
        'cost_center' => '',
        'description' => '',
    ];
@endphp

@if ($requireAmount && $enableMulticurrency)
    <p class="text-muted small mb-2">
        <i class="fas fa-info-circle"></i>
        SAP tidak menerima baris valas (USD) dan baris IDR dalam satu jurnal. Jika transaksi melibatkan keduanya, buat dua jurnal terpisah: satu jurnal USD murni dan satu jurnal IDR murni.
    </p>
@endif

<div class="table-responsive">
    <table class="table table-bordered table-sm" id="je-lines-table">
        <thead>
            <tr>
                <th style="width: 4%">#</th>
                <th style="width: {{ $enableMulticurrency ? '14%' : '18%' }}">Account</th>
                <th style="width: 7%">Dr/Cr</th>
                @if ($enableMulticurrency)
                    <th style="width: 7%">Mata Uang</th>
                    <th style="width: 9%">Nominal Valas</th>
                    <th style="width: 9%">Kurs</th>
                @endif
                <th style="width: {{ $enableMulticurrency ? '9%' : '12%' }}">{{ $amountLabel }}</th>
                <th style="width: 8%">Project</th>
                <th style="width: 9%">Cost Center</th>
                <th>Description</th>
                <th style="width: 4%"></th>
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
                    'enableMulticurrency' => $enableMulticurrency,
                ])
            @endforeach
        </tbody>
    </table>
</div>

@if ($requireAmount && $enableMulticurrency)
    <div id="je-currency-summary" class="vj-stat-grid mt-3 mb-2">
        <div id="je-summary-idr" class="vj-stat vj-stat-neutral">
            <div class="vj-stat-icon"><i class="fas fa-coins"></i></div>
            <div class="vj-stat-body">
                <span class="vj-stat-label">Total IDR</span>
                <span class="vj-stat-value" id="je-summary-idr-text">Debit 0 / Kredit 0</span>
                <small id="je-summary-idr-status" class="text-muted">—</small>
            </div>
        </div>
        <div id="je-summary-usd" class="vj-stat vj-stat-neutral">
            <div class="vj-stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="vj-stat-body">
                <span class="vj-stat-label">Total USD</span>
                <span class="vj-stat-value" id="je-summary-usd-text">Debit 0,00 / Kredit 0,00</span>
                <small id="je-summary-usd-status" class="text-muted">—</small>
            </div>
        </div>
    </div>
@elseif ($requireAmount)
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <tbody>
                <tr>
                    <td class="text-right" style="width: 75%"><strong>Total Debit</strong></td>
                    <td><strong id="total-debit">0.00</strong></td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Total Credit</strong></td>
                    <td><strong id="total-credit">0.00</strong></td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Difference</strong></td>
                    <td><strong id="total-diff" class="text-danger">0.00</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
@endif

<button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-line-btn">
    <i class="fas fa-plus"></i> Add Line
</button>

<template id="je-line-row-template">
    @include('accounting.journal-entries.partials.line-row', [
        'index' => '__INDEX__',
        'line' => $lineRowDefaults,
        'projects' => $projects,
        'departments' => $departments,
        'amountField' => $amountField,
        'requireAmount' => $requireAmount,
        'enableMulticurrency' => $enableMulticurrency,
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

        #je-lines-table .line-amount[readonly] {
            background-color: #f1f3f5;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const jeAmountField = @json($amountField);
        const jeRequireAmount = @json($requireAmount);
        const jeMulticurrencyEnabled = @json($enableMulticurrency);
        const jeDefaultUsdRateUrl = @json($enableMulticurrency ? route('accounting.journal-entries.default_usd_rate') : null);
        let jeSubmitConfirmed = false;

        function jeFormatIdr(value) {
            return Math.round(value).toLocaleString('id-ID');
        }

        function jeFormatUsd(value) {
            return value.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function jeRoundIdr(fc, rate) {
            return Math.round((fc * rate) * 100) / 100;
        }

        function jeIsUsdRow($row) {
            return jeMulticurrencyEnabled && $row.find('.line-currency').val() === 'USD';
        }

        function jeApplyCurrencyMode($row) {
            if (!jeMulticurrencyEnabled) {
                return;
            }

            const isUsd = jeIsUsdRow($row);
            const $fc = $row.find('.line-fc-amount');
            const $rate = $row.find('.line-exchange-rate');
            const $amount = $row.find('.line-amount');

            $fc.prop('disabled', !isUsd).prop('required', isUsd);
            $rate.prop('disabled', !isUsd).prop('required', isUsd);
            $amount.prop('readonly', isUsd);

            if (!isUsd) {
                $fc.val('');
                $rate.val('');
                $amount.prop('readonly', false);
            } else {
                jeRecalcLineIdr($row);
            }
        }

        function jeRecalcLineIdr($row) {
            if (!jeIsUsdRow($row)) {
                return;
            }

            const fc = parseFloat($row.find('.line-fc-amount').val()) || 0;
            const rate = parseFloat($row.find('.line-exchange-rate').val()) || 0;
            if (fc > 0 && rate > 0) {
                $row.find('.line-amount').val(jeRoundIdr(fc, rate).toFixed(2));
            }
        }

        function jeSyncAllUsdAmounts() {
            if (!jeMulticurrencyEnabled) {
                return;
            }

            $('#je-lines-body tr').each(function() {
                jeRecalcLineIdr($(this));
            });
        }

        function jeFetchDefaultUsdRate($row) {
            const journalDate = $('#date').val();
            if (!journalDate || !jeDefaultUsdRateUrl) {
                return;
            }

            $.getJSON(jeDefaultUsdRateUrl, { date: journalDate }, function(data) {
                if (data && data.exchange_rate) {
                    $row.find('.line-exchange-rate').val(data.exchange_rate);
                    $row.data('rate-auto', true);
                    jeRecalcLineIdr($row);
                    jeRecalcTotals();
                }
            });
        }

        function jeCollectTotals() {
            const totals = {
                idrDebit: 0,
                idrCredit: 0,
                usdDebit: 0,
                usdCredit: 0,
            };

            $('#je-lines-body tr').each(function() {
                const $row = $(this);
                const side = $row.find('.line-debit-credit').val();
                const idr = parseFloat($row.find('.line-amount').val()) || 0;

                if (side === 'debit') {
                    totals.idrDebit += idr;
                } else {
                    totals.idrCredit += idr;
                }

                if (jeIsUsdRow($row)) {
                    const fc = parseFloat($row.find('.line-fc-amount').val()) || 0;
                    if (side === 'debit') {
                        totals.usdDebit += fc;
                    } else {
                        totals.usdCredit += fc;
                    }
                }
            });

            return totals;
        }

        function jeSetSummaryState($card, balanced, diffText) {
            $card.removeClass('vj-stat-success vj-stat-danger vj-stat-neutral')
                .addClass(balanced ? 'vj-stat-success' : 'vj-stat-danger');
            const $status = $card.find('small').last();
            $status.removeClass('text-success text-danger text-muted')
                .addClass(balanced ? 'text-success' : 'text-danger')
                .html(balanced ? '<i class="fas fa-check-circle"></i> Seimbang' : '<i class="fas fa-exclamation-circle"></i> Selisih ' + diffText);
        }

        function jeRecalcTotals() {
            if (!jeRequireAmount) {
                return;
            }

            jeSyncAllUsdAmounts();
            const totals = jeCollectTotals();
            const idrDiff = Math.abs(totals.idrDebit - totals.idrCredit);
            const usdDiff = Math.abs(totals.usdDebit - totals.usdCredit);

            if (jeMulticurrencyEnabled) {
                $('#je-summary-idr-text').text(
                    'Debit ' + jeFormatIdr(totals.idrDebit) + ' / Kredit ' + jeFormatIdr(totals.idrCredit)
                );
                $('#je-summary-usd-text').text(
                    'Debit ' + jeFormatUsd(totals.usdDebit) + ' / Kredit ' + jeFormatUsd(totals.usdCredit)
                );
                jeSetSummaryState($('#je-summary-idr'), idrDiff <= 0.01, jeFormatIdr(idrDiff));
                jeSetSummaryState($('#je-summary-usd'), usdDiff <= 0.01, jeFormatUsd(usdDiff));
            } else {
                $('#total-debit').text(totals.idrDebit.toFixed(2));
                $('#total-credit').text(totals.idrCredit.toFixed(2));
                $('#total-diff').text(idrDiff.toFixed(2))
                    .toggleClass('text-danger', idrDiff > 0.01)
                    .toggleClass('text-success', idrDiff <= 0.01);
            }
        }

        function jeValidateBalances() {
            const totals = jeCollectTotals();
            const idrDiff = Math.abs(totals.idrDebit - totals.idrCredit);
            const usdDiff = Math.abs(totals.usdDebit - totals.usdCredit);

            if (idrDiff > 0.01) {
                Swal.fire(
                    'Jurnal tidak seimbang (IDR)',
                    'Total debit IDR ' + jeFormatIdr(totals.idrDebit) + ' dan kredit IDR ' + jeFormatIdr(totals.idrCredit) + ' (selisih ' + jeFormatIdr(idrDiff) + ').',
                    'error'
                );
                return false;
            }

            if (jeMulticurrencyEnabled && usdDiff > 0.01) {
                Swal.fire(
                    'Jurnal tidak seimbang (USD)',
                    'Total debit USD ' + jeFormatUsd(totals.usdDebit) + ' dan kredit USD ' + jeFormatUsd(totals.usdCredit) + ' (selisih ' + jeFormatUsd(usdDiff) + ').',
                    'error'
                );
                return false;
            }

            return true;
        }

        function jeBuildPreviewHtml() {
            let rows = '';
            $('#je-lines-body tr').each(function(i) {
                const $row = $(this);
                const currency = jeMulticurrencyEnabled ? ($row.find('.line-currency').val() || 'IDR') : 'IDR';
                const account = $row.find('[name$="[account_code]"]').val() || '—';
                const side = $row.find('.line-debit-credit').val();
                const idr = parseFloat($row.find('.line-amount').val()) || 0;
                let fcInfo = '—';
                if (currency === 'USD') {
                    const fc = parseFloat($row.find('.line-fc-amount').val()) || 0;
                    const rate = parseFloat($row.find('.line-exchange-rate').val()) || 0;
                    fcInfo = jeFormatUsd(fc) + ' USD @ ' + rate;
                }
                rows += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + account + '</td>' +
                    '<td>' + side.toUpperCase() + '</td>' +
                    '<td>' + currency + '</td>' +
                    '<td class="text-right">' + fcInfo + '</td>' +
                    '<td class="text-right">' + jeFormatIdr(idr) + '</td>' +
                    '</tr>';
            });

            const totals = jeCollectTotals();
            const idrBalanced = Math.abs(totals.idrDebit - totals.idrCredit) <= 0.01;
            const usdBalanced = Math.abs(totals.usdDebit - totals.usdCredit) <= 0.01;

            return '<div class="vj-swal-summary">' +
                '<div class="table-responsive"><table class="table table-sm table-bordered mb-2">' +
                '<thead><tr><th>#</th><th>Akun</th><th>Dr/Cr</th><th>Valuta</th><th class="text-right">Nominal valas</th><th class="text-right">IDR</th></tr></thead>' +
                '<tbody>' + rows + '</tbody></table></div>' +
                '<p class="mb-1"><strong>Total IDR:</strong> Debit ' + jeFormatIdr(totals.idrDebit) + ' / Kredit ' + jeFormatIdr(totals.idrCredit) +
                (idrBalanced ? ' <span class="text-success">✓</span>' : ' <span class="text-danger">tidak seimbang</span>') + '</p>' +
                (jeMulticurrencyEnabled ? '<p class="mb-0"><strong>Total USD:</strong> Debit ' + jeFormatUsd(totals.usdDebit) + ' / Kredit ' + jeFormatUsd(totals.usdCredit) +
                (usdBalanced ? ' <span class="text-success">✓</span>' : ' <span class="text-danger">tidak seimbang</span>') + '</p>' : '') +
                '</div>';
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
            const $newRow = $(html);
            $('#je-lines-body').append($newRow);
            jeApplyCurrencyMode($newRow);
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

        $(document).on('change', '.line-currency', function() {
            const $row = $(this).closest('tr');
            if ($(this).val() === 'USD') {
                jeFetchDefaultUsdRate($row);
            }
            jeApplyCurrencyMode($row);
            jeRecalcTotals();
        });

        $(document).on('input', '.line-fc-amount, .line-exchange-rate', function() {
            const $row = $(this).closest('tr');
            if ($(this).hasClass('line-exchange-rate')) {
                $row.data('rate-auto', false);
            }
            jeRecalcLineIdr($row);
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
            if (!jeRequireAmount) {
                return true;
            }

            if (jeSubmitConfirmed) {
                return true;
            }

            e.preventDefault();
            jeSyncAllUsdAmounts();

            if (!jeValidateBalances()) {
                return false;
            }

            const swal = (typeof VjSwal !== 'undefined') ? VjSwal : Swal;
            swal.fire({
                title: 'Konfirmasi Journal Entry',
                html: jeBuildPreviewHtml(),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                confirmVariant: 'primary',
                customClass: jeMulticurrencyEnabled ? { popup: 'vj-swal-popup vj-swal-popup-wide' } : {},
            }).then(function(result) {
                if (result.isConfirmed) {
                    jeSubmitConfirmed = true;
                    $('#je-form').trigger('submit');
                }
            });

            return false;
        });

        $(function() {
            if (jeMulticurrencyEnabled) {
                $('#je-lines-body tr').each(function() {
                    jeApplyCurrencyMode($(this));
                });
            }
            jeRecalcTotals();
        });
    </script>
@endpush
