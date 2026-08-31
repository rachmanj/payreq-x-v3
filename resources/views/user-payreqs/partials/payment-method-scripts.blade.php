<script>
    (function() {
        function initPaymentMethodUi() {
            const $transferBlock = $('#transfer-account-block');
            const $transferSelect = $('#transfer_account_id');

            $('.payment-method-radio').on('change', function() {
                if ($(this).val() === 'transfer') {
                    $transferBlock.show();
                } else {
                    $transferBlock.hide();
                    $transferSelect.val('').trigger('change');
                }
            });

            if ($transferSelect.length && !$transferSelect.hasClass('select2-hidden-accessible')) {
                $transferSelect.select2({
                    theme: 'bootstrap4',
                    placeholder: 'Pilih Akun Transfer',
                    allowClear: true,
                    width: '100%'
                });
            }

            $('#transferAccountModal').on('shown.bs.modal', function() {
                const $bankSelect = $('#new_transfer_bank_id');
                if ($bankSelect.length && !$bankSelect.hasClass('select2-hidden-accessible')) {
                    $bankSelect.select2({
                        theme: 'bootstrap4',
                        placeholder: 'Pilih Bank',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $('#transferAccountModal')
                    });
                }
            });

            $('#btn-save-transfer-account').on('click', function() {
                const $btn = $(this);
                const $error = $('#transfer-account-modal-error');

                $error.hide().text('');
                $btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route('user-payreqs.transfer_accounts.store') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        label: $('#new_transfer_label').val(),
                        bank_id: $('#new_transfer_bank_id').val(),
                        account_number: $('#new_transfer_account_number').val(),
                        account_name: $('#new_transfer_account_name').val(),
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            const option = new Option(response.label, response.id, true, true);
                            $transferSelect.append(option).trigger('change');
                            $('#payment_method_transfer').prop('checked', true).trigger('change');
                            $transferBlock.show();
                            $('#new_transfer_label, #new_transfer_account_number, #new_transfer_account_name').val('');
                            $('#new_transfer_bank_id').val('').trigger('change');
                            $('#transferAccountModal').modal('hide');
                        } else {
                            $error.text('Gagal menyimpan akun transfer.').show();
                        }
                    },
                    error: function(xhr) {
                        let message = 'Gagal menyimpan akun transfer.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        }
                        $error.text(message).show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        }

        $(function() {
            if ($('#payment-method-section').length) {
                initPaymentMethodUi();
            }
        });
    })();
</script>
