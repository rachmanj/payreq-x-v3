@push('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        (function() {
            const canSubmitUtilityPayment = @json($canSubmitUtilityPayment ?? false);
            const defaultPreparedBy = @json($defaultPreparedBy ?? auth()->user()?->name ?? '');
            const accountsUrl = @json(route('utilities.ap-invoices.accounts'));
            const previewUrlTemplate = @json(route('utilities.ap-invoices.sap-payment.preview', ['utilityApInvoice' => '__ID__']));
            const submitUrlTemplate = @json(route('utilities.ap-invoices.sap-payment.submit', ['utilityApInvoice' => '__ID__']));

            let utilityAccountsLoaded = false;
            let utilityPreviewReady = false;
            let utilityRemainingBalance = 0;

            function formatCurrency(value) {
                const amount = parseFloat(value || 0);
                return 'Rp ' + amount.toLocaleString('id-ID', { maximumFractionDigits: 0 });
            }

            function showUtilityPaymentError(message) {
                $('#utilitySapPaymentError').removeClass('d-none').text(message || 'Terjadi kesalahan.');
            }

            function hideUtilityPaymentError() {
                $('#utilitySapPaymentError').addClass('d-none').text('');
            }

            function initUtilityAccountSelect() {
                const $select = $('#utility_account_id');
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $('#utilitySapPaymentModal'),
                });
            }

            function loadUtilityAccounts(selectedId) {
                return $.getJSON(accountsUrl).done(function(response) {
                    const accounts = response.accounts || [];
                    let options = '<option value="">Pilih akun...</option>';
                    accounts.forEach(function(account) {
                        options += '<option value="' + account.id + '">' + account.label + '</option>';
                    });
                    $('#utility_account_id').html(options);
                    initUtilityAccountSelect();
                    if (selectedId) {
                        $('#utility_account_id').val(String(selectedId)).trigger('change');
                    }
                    utilityAccountsLoaded = accounts.length > 0;
                });
            }

            function collectUtilityPaymentPayload() {
                return {
                    payment_means: $('#utility_payment_means').val(),
                    account_id: $('#utility_account_id').val(),
                    payment_date: $('#utility_payment_date').val(),
                    payment_amount: $('#utility_payment_amount').val(),
                    prepared_by: $('#utility_prepared_by').val(),
                    approved_by: $('#utility_approved_by').val(),
                    remarks: $('#utility_payment_remarks').val(),
                };
            }

            function renderUtilityPreview(preview) {
                const vendor = preview.vendor || {};
                const account = preview.account || {};
                const meansLabel = preview.payment_means === 'cash' ? 'Cash' : 'Bank Transfer';

                $('#utility_preview_vendor').text((vendor.name || '-') + (vendor.code ? ' (' + vendor.code + ')' : ''));
                $('#utility_preview_ap_doc').text(preview.ap_doc_num || '-');
                $('#utility_preview_doc_total').text(formatCurrency(preview.doc_total));
                $('#utility_preview_paid_to_date').text(formatCurrency(preview.paid_to_date));
                $('#utility_preview_remaining').text(formatCurrency(preview.remaining));
                $('#utility_preview_payment_amount').text(formatCurrency(preview.payment_amount));
                $('#utility_preview_means_account').text(meansLabel + ' — ' + (account.label || account.account_name || '-'));
                $('#utility_sap_payment_preview_panel').removeClass('d-none');
            }

            window.openUtilitySapPaymentModal = function(invoice) {
                if (!canSubmitUtilityPayment) {
                    return;
                }

                utilityPreviewReady = false;
                utilityRemainingBalance = 0;
                hideUtilityPaymentError();
                $('#utility_sap_payment_preview_panel').addClass('d-none');
                $('#utilitySapPaymentSubmitBtn').addClass('d-none').prop('disabled', true);
                $('#utilitySapPaymentPreviewBtn').prop('disabled', false);

                $('#utility_sap_invoice_id').val(invoice.id);
                $('#utility_sap_num_at_card').text(invoice.num_at_card || '-');
                $('#utility_sap_ap_doc_num').text(invoice.sap_doc_num || '-');
                $('#utility_payment_date').val(new Date().toISOString().split('T')[0]);
                $('#utility_payment_amount').val('');
                $('#utility_payment_remarks').val('');
                $('#utility_prepared_by').val(defaultPreparedBy);
                $('#utility_approved_by').val(defaultPreparedBy);
                $('#utility_payment_means').val('transfer');
                $('#utility_remaining_display').text('-');
                $('#utility_sap_remaining_value').val('0');

                loadUtilityAccounts(null).always(function() {
                    $('#utilitySapPaymentModal').modal('show');
                });
            };

            $(document).ready(function() {
                initUtilityAccountSelect();

                $('#utility_fill_remaining_btn').on('click', function() {
                    if (utilityRemainingBalance > 0) {
                        $('#utility_payment_amount').val(Math.round(utilityRemainingBalance));
                    }
                });

                $('#utilitySapPaymentPreviewBtn').on('click', function() {
                    const invoiceId = $('#utility_sap_invoice_id').val();
                    if (!invoiceId) {
                        return;
                    }

                    hideUtilityPaymentError();
                    const btn = $(this);
                    const originalHtml = btn.html();
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Preview...');

                    $.ajax({
                        url: previewUrlTemplate.replace('__ID__', invoiceId),
                        method: 'POST',
                        data: collectUtilityPaymentPayload(),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        },
                    }).done(function(response) {
                        const preview = response.preview || {};
                        utilityRemainingBalance = parseFloat(preview.remaining || 0);
                        $('#utility_sap_remaining_value').val(utilityRemainingBalance);
                        $('#utility_remaining_display').text(formatCurrency(utilityRemainingBalance));

                        if (!$('#utility_payment_amount').val()) {
                            $('#utility_payment_amount').val(Math.round(utilityRemainingBalance));
                        }

                        if (preview.account && preview.account.id) {
                            $('#utility_account_id').val(String(preview.account.id)).trigger('change');
                        }

                        renderUtilityPreview(preview);
                        utilityPreviewReady = true;
                        $('#utilitySapPaymentSubmitBtn').removeClass('d-none').prop('disabled', false);
                    }).fail(function(xhr) {
                        const response = xhr.responseJSON || {};
                        showUtilityPaymentError(response.message || 'Preview gagal.');
                        utilityPreviewReady = false;
                        $('#utilitySapPaymentSubmitBtn').addClass('d-none').prop('disabled', true);
                    }).always(function() {
                        btn.prop('disabled', false).html(originalHtml);
                    });
                });

                $('#utilitySapPaymentSubmitBtn').on('click', function() {
                    if (!utilityPreviewReady) {
                        showUtilityPaymentError('Lakukan preview terlebih dahulu.');
                        return;
                    }

                    const invoiceId = $('#utility_sap_invoice_id').val();
                    Swal.fire({
                        title: 'Submit Outgoing Payment?',
                        text: 'Pembayaran akan diposting ke SAP B1. Lanjutkan?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Submit OP',
                        cancelButtonText: 'Batal',
                    }).then(function(result) {
                        if (!result.isConfirmed) {
                            return;
                        }

                        const btn = $('#utilitySapPaymentSubmitBtn');
                        const originalHtml = btn.html();
                        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

                        $.ajax({
                            url: submitUrlTemplate.replace('__ID__', invoiceId),
                            method: 'POST',
                            data: collectUtilityPaymentPayload(),
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            },
                        }).done(function(response) {
                            $('#utilitySapPaymentModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Outgoing payment berhasil diposting.',
                            }).then(function() {
                                window.location.reload();
                            });
                        }).fail(function(xhr) {
                            const response = xhr.responseJSON || {};
                            showUtilityPaymentError(response.message || 'Submit gagal.');
                            btn.prop('disabled', false).html(originalHtml);
                        });
                    });
                });

                $('.btn-utility-create-op').on('click', function() {
                    openUtilitySapPaymentModal({
                        id: $(this).data('invoice-id'),
                        num_at_card: $(this).data('num-at-card'),
                        sap_doc_num: $(this).data('sap-doc-num'),
                    });
                });
            });
        })();
    </script>
@endpush
