{{-- Shared bank-transaction detail table helpers (create & edit forms). --}}
<script>
    const BANK_INTEREST_INCOME_PREFIX = '71101';
    const BANK_INTEREST_EXPENSE_PREFIX = '71201';
    const BANK_INTEREST_ZERO_NET_MESSAGE =
        'Tidak ada pergerakan bank yang perlu dijurnal karena total pendapatan dan total biaya sama.';

    function resolveLineDebitCredit(transactionType, accountCode) {
        const code = String(accountCode || '');
        if (transactionType === 'bank_interest') {
            if (code.startsWith(BANK_INTEREST_INCOME_PREFIX)) {
                return 'credit';
            }
            if (code.startsWith(BANK_INTEREST_EXPENSE_PREFIX)) {
                return 'debit';
            }
        }

        return 'debit';
    }

    function formatDebitCreditLabel(debitCredit) {
        return debitCredit.charAt(0).toUpperCase() + debitCredit.slice(1);
    }

    function sumDetailAmounts() {
        let total = 0;
        $('#details-table tbody tr.detail-data-row').each(function() {
            const id = $(this).data('detail-id');
            total += parseFloat($(`#amount_${id}`).val() || 0);
        });

        return total;
    }

    function computeBankInterestTotals() {
        let income = 0;
        let expense = 0;

        $('#details-table tbody tr.detail-data-row').each(function() {
            const id = $(this).data('detail-id');
            const code = String($(`#account_code_${id}`).val() || '');
            const amount = parseFloat($(`#amount_${id}`).val() || 0);
            if (code.startsWith(BANK_INTEREST_INCOME_PREFIX)) {
                income += amount;
            } else if (code.startsWith(BANK_INTEREST_EXPENSE_PREFIX)) {
                expense += amount;
            }
        });

        return {
            income,
            expense
        };
    }

    function computeDetailFooterTotal() {
        const transactionType = $('#transaction_type').val();
        if (transactionType === 'bank_interest') {
            const {
                income,
                expense
            } = computeBankInterestTotals();

            return Math.max(income, expense);
        }

        return sumDetailAmounts();
    }

    function computeBankPreview(transactionType, bankAccount) {
        if (!bankAccount) {
            return null;
        }

        if (transactionType === 'bank_interest') {
            const {
                income,
                expense
            } = computeBankInterestTotals();
            const net = Math.abs(income - expense);
            if (net < 0.00001) {
                return {
                    account: bankAccount,
                    net: 0,
                    side: null
                };
            }

            return {
                account: bankAccount,
                net,
                side: income > expense ? 'debit' : 'credit'
            };
        }

        const total = sumDetailAmounts();

        return {
            account: bankAccount,
            net: total,
            side: 'credit'
        };
    }

    function getBankAccountLabel(bankAccount) {
        const option = $(`#bank_account_select option[value="${bankAccount}"]`);
        if (option.length) {
            return option.text();
        }

        return bankAccount;
    }

    function updateBankPreviewRow() {
        const transactionType = $('#transaction_type').val();
        const bankAccount = $('#bank_account_select').val() || $('#bank_account').val() || '';
        const preview = computeBankPreview(transactionType, bankAccount);
        const row = $('#bank-preview-row');
        const zeroMessage = $('#bank-interest-zero-net-message');

        if (!preview || !bankAccount) {
            row.addClass('d-none');
            zeroMessage.addClass('d-none');
            return;
        }

        row.removeClass('d-none');

        if (transactionType === 'bank_interest' && preview.net < 0.00001) {
            row.addClass('d-none');
            zeroMessage.removeClass('d-none');
            return;
        }

        zeroMessage.addClass('d-none');

        const accountLabel = getBankAccountLabel(preview.account);
        row.find('.bank-preview-account').text(accountLabel);
        row.find('.bank-preview-side').text(formatDebitCreditLabel(preview.side));
        row.find('.bank-preview-amount').text(preview.net.toLocaleString('id-ID', {
            minimumFractionDigits: 2
        }));
    }

    function refreshDetailRowDebitCreditDisplay(detailId) {
        const transactionType = $('#transaction_type').val();
        const accountCode = $(`#account_code_${detailId}`).val();
        const debitCredit = resolveLineDebitCredit(transactionType, accountCode);
        $(`#debit_credit_${detailId}`).val(debitCredit);
        $(`#detail-row-${detailId} td.detail-side-cell`).text(formatDebitCreditLabel(debitCredit));
    }

    function refreshAllDetailRowDebitCreditDisplays() {
        $('#details-table tbody tr.detail-data-row').each(function() {
            refreshDetailRowDebitCreditDisplay($(this).data('detail-id'));
        });
    }
</script>
