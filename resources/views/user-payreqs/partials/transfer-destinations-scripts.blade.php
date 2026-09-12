<script>
    (function() {
        function formatIntegerId(amount) {
            const value = parseInt(amount, 10);

            if (Number.isNaN(value)) {
                return '0';
            }

            return value.toLocaleString('id-ID');
        }

        function parsePayreqAmount() {
            const $payreqTotal = $('#transfer-destinations-payreq-total');
            if (!$payreqTotal.length) {
                return 0;
            }

            const $amountInput = $('#amount');
            if ($amountInput.length) {
                const raw = ($amountInput.val() || '').toString().replace(/[^\d]/g, '');

                return raw === '' ? 0 : parseInt(raw, 10);
            }

            const totalText = ($('#total-amount').text() || '').replace(/[^\d]/g, '');
            if (totalText !== '') {
                return parseInt(totalText, 10);
            }

            return parseInt($payreqTotal.data('initial-amount') || 0, 10);
        }

        function initTransferDestinationSelect($select) {
            if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Rekening Tujuan',
                allowClear: true,
                width: '100%'
            });
        }

        function recalcTransferDestinationTotals() {
            let plannedTotal = 0;

            $('#transfer-destinations-body .transfer-destination-planned-amount').each(function() {
                const value = parseInt(($(this).val() || '').toString().replace(/[^\d]/g, ''), 10);
                if (!Number.isNaN(value)) {
                    plannedTotal += value;
                }
            });

            const payreqAmount = parsePayreqAmount();

            $('#transfer-destinations-planned-total').text(formatIntegerId(plannedTotal));
            $('#transfer-destinations-payreq-total').text(formatIntegerId(payreqAmount));

            if (plannedTotal > payreqAmount && payreqAmount > 0) {
                $('#transfer-destinations-total-warning').show();
            } else {
                $('#transfer-destinations-total-warning').hide();
            }
        }

        function toggleTransferDestinationsBlock() {
            const method = $('input[name="payment_method"]:checked').val() || 'cash';
            const $block = $('#transfer-destinations-block');

            if (!$block.length) {
                return;
            }

            if (method === 'transfer') {
                $block.show();
            } else {
                $block.hide();
            }
        }

        function appendTransferDestinationRow() {
            const template = $('#transfer-destination-row-template').html();
            if (!template) {
                return;
            }

            const index = window.payreqTransferDestinationRowIndex || 0;
            window.payreqTransferDestinationRowIndex = index + 1;

            const rowHtml = template.replace(/__INDEX__/g, String(index));
            const $row = $(rowHtml);
            $('#transfer-destinations-body').append($row);
            initTransferDestinationSelect($row.find('.transfer-destination-account'));
            recalcTransferDestinationTotals();
        }

        function appendTransferAccountOption(accountId, label) {
            const optionHtml = $('<option></option>').val(accountId).text(label);

            $('#transfer-destinations-body .transfer-destination-account').each(function() {
                const $select = $(this);
                if ($select.find('option[value="' + accountId + '"]').length === 0) {
                    $select.append(optionHtml.clone());
                }
            });

            const $templateSelect = $('#transfer-destination-row-template .transfer-destination-account');
            if ($templateSelect.length && $templateSelect.find('option[value="' + accountId + '"]').length === 0) {
                $templateSelect.append(optionHtml.clone());
            }
        }

        window.payreqCollectTransferDestinationsPayload = function() {
            const payload = {
                transfer_destinations_present: '1',
                transfer_destinations: {}
            };

            $('#transfer-destinations-body tr.transfer-destination-row').each(function() {
                const index = $(this).data('row-index');
                payload.transfer_destinations[index] = {
                    transfer_account_id: $(this).find('.transfer-destination-account').val(),
                    planned_amount: $(this).find('.transfer-destination-planned-amount').val(),
                    remark: $(this).find('.transfer-destination-remark').val()
                };
            });

            return payload;
        };

        function initTransferDestinationsUi() {
            $('#transfer-destinations-body .transfer-destination-account').each(function() {
                initTransferDestinationSelect($(this));
            });

            toggleTransferDestinationsBlock();

            $('.payment-method-radio').on('change.transferDestinations', toggleTransferDestinationsBlock);

            $('#btn-add-transfer-destination-row').on('click', appendTransferDestinationRow);

            $('#transfer-destinations-body').on('click', '.btn-remove-transfer-destination-row', function() {
                const $body = $('#transfer-destinations-body');
                if ($body.find('tr.transfer-destination-row').length <= 1) {
                    return;
                }

                const $row = $(this).closest('tr.transfer-destination-row');
                const $select = $row.find('.transfer-destination-account');
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $row.remove();
                recalcTransferDestinationTotals();
            });

            $('#transfer-destinations-body').on('input change', '.transfer-destination-planned-amount', recalcTransferDestinationTotals);
            $('#amount').on('keyup change.transferDestinations', recalcTransferDestinationTotals);

            $(document).on('payreq:transfer-account-added', function(_event, accountId, label) {
                appendTransferAccountOption(accountId, label);
            });
        }

        $(function() {
            if ($('#transfer-destinations-section').length) {
                initTransferDestinationsUi();
                recalcTransferDestinationTotals();
            }
        });
    })();
</script>
