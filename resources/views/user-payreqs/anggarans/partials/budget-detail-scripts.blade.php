<script>
    (function () {
        const $body = $('#budget-detail-body');
        const $amount = $('#amount');
        const $amountDisplay = $('#amount_display');

        function formatAmountDisplay(sum) {
            const fixed = (Math.round(sum * 100) / 100).toFixed(2);
            const parts = fixed.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            return { raw: fixed, display: parts.join('.') };
        }

        function lineAmount($tr) {
            let amount = parseFloat($tr.find('.detail-amount').val()) || 0;
            const qty = parseFloat($tr.find('.detail-qty').val()) || 0;
            const unitPrice = parseFloat($tr.find('.detail-unit-price').val()) || 0;

            if (amount <= 0 && qty > 0 && unitPrice > 0) {
                amount = Math.round(qty * unitPrice * 100) / 100;
                $tr.find('.detail-amount').val(amount);
            }

            return amount;
        }

        function recalcTotalAmount() {
            let sum = 0;
            $body.find('tr.budget-detail-row').each(function () {
                sum += lineAmount($(this));
            });
            const formatted = formatAmountDisplay(sum);
            $amount.val(formatted.raw);
            $amountDisplay.val(formatted.display);
        }

        function nextDetailIndex() {
            let max = -1;
            $body.find('input[name^="details["]').each(function () {
                const m = $(this).attr('name').match(/details\[(\d+)]/);
                if (m) {
                    max = Math.max(max, parseInt(m[1], 10));
                }
            });

            return max + 1;
        }

        function reindexDetailRows() {
            $body.find('tr.budget-detail-row').each(function (rowIdx) {
                $(this).find('input').each(function () {
                    const name = $(this).attr('name');
                    if (!name) {
                        return;
                    }
                    $(this).attr('name', name.replace(/details\[\d+]/, 'details[' + rowIdx + ']'));
                });
            });
            updateRemoveButtons();
            recalcTotalAmount();
        }

        function updateRemoveButtons() {
            const count = $body.find('tr.budget-detail-row').length;
            $body.find('.btn-remove-budget-detail').prop('disabled', count <= 1);
        }

        $('#btn-add-budget-detail').on('click', function () {
            const idx = nextDetailIndex();
            const $tr = $('<tr class="budget-detail-row">' +
                '<td><input type="text" name="details[' + idx + '][description]" class="form-control form-control-sm"></td>' +
                '<td><input type="number" step="0.0001" name="details[' + idx + '][qty]" class="form-control form-control-sm detail-qty" value="1"></td>' +
                '<td><input type="text" name="details[' + idx + '][unit]" class="form-control form-control-sm"></td>' +
                '<td><input type="number" step="0.01" name="details[' + idx + '][unit_price]" class="form-control form-control-sm detail-unit-price" value="0"></td>' +
                '<td><input type="number" step="0.01" name="details[' + idx + '][amount]" class="form-control form-control-sm detail-amount" value="0"></td>' +
                '<td class="text-nowrap text-center"><button type="button" class="btn btn-xs btn-danger btn-remove-budget-detail" title="Remove line"><i class="fas fa-trash"></i></button></td>' +
                '</tr>');
            $body.append($tr);
            updateRemoveButtons();
            recalcTotalAmount();
        });

        $body.on('click', '.btn-remove-budget-detail', function () {
            if ($body.find('tr.budget-detail-row').length <= 1) {
                return;
            }
            $(this).closest('tr').remove();
            reindexDetailRows();
        });

        $body.on('input', '.detail-qty, .detail-unit-price, .detail-amount', function () {
            recalcTotalAmount();
        });

        $('#form_anggaran').on('submit', function () {
            recalcTotalAmount();
        });

        updateRemoveButtons();
        recalcTotalAmount();
    })();
</script>
