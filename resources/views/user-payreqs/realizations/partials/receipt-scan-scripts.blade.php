<script>
    const receiptScanUrl = @json(route('user-payreqs.realizations.scan_receipt'));
    const bulkStoreDetailsUrl = @json(route('user-payreqs.realizations.bulk_store_details'));
    const realizationIdForScan = @json($realization->id);
    const equipmentUnitCodes = @json($equipments->pluck('unit_code')->values());
    const realizationAdvanceMultiBudgetForScan = @json($realization->payreq->isAdvanceMultiBudget());
    const bulkScanColspan = @json($realization->payreq->isAdvanceMultiBudget() ? 10 : 9);

    function normalizeUnitCode(value) {
        if (!value) {
            return '';
        }
        return String(value).toUpperCase().replace(/\s+/g, '');
    }

    function formatUnitNoDisplay(unitNo) {
        const normalized = normalizeUnitCode(unitNo);
        if (/^[A-Z]{2}\d{3}$/.test(normalized)) {
            return normalized.substring(0, 2) + ' ' + normalized.substring(2);
        }

        return unitNo || '';
    }

    function resolveUnitNoOptionValue(unitNo) {
        const normalized = normalizeUnitCode(unitNo);
        if (!normalized) {
            return null;
        }

        if (equipmentUnitCodes.includes(normalized)) {
            return normalized;
        }

        const match = equipmentUnitCodes.find(function(code) {
            return normalizeUnitCode(code) === normalized;
        });

        return match || null;
    }

    function setScanAlert(alertId, message, type) {
        const $alert = $('#' + alertId);
        if (!message) {
            $alert.hide().empty();
            return;
        }
        const cls = type === 'success' ? 'alert-success' :
            (type === 'warning' ? 'alert-warning' :
                (type === 'info' ? 'alert-info' : 'alert-danger'));
        $alert.html('<div class="alert ' + cls + ' mb-0 py-2">' + message + '</div>').show();
    }

    function applyScannedDataToForm(formPrefix, data) {
        const p = formPrefix || '';
        $('#' + p + 'description').val(data.description || '');
        const amount = parseFloat(data.amount || 0);
        const amountInput = document.getElementById(p + 'amount');
        if (amountInput) {
            amountInput.value = amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        if (data.expense_date) {
            $('#' + p + 'expense_date').val(String(data.expense_date).substring(0, 10));
        }
        if (data.km_position !== null && data.km_position !== undefined && data.km_position !== '') {
            $('#' + p + 'km_position').val(data.km_position);
        }
        if (data.qty !== null && data.qty !== undefined && data.qty !== '') {
            $('#' + p + 'qty').val(data.qty);
        }
        if (data.nopol) {
            $('#' + p + 'nopol').val(data.nopol);
        }
        $('#' + p + 'type').val(data.type || 'fuel').trigger('change');
        $('#' + p + 'uom').val(data.uom || 'liter').trigger('change');

        const unitValue = resolveUnitNoOptionValue(data.unit_no);
        const $unitSelect = $('#' + p + 'unit_no');
        if (unitValue) {
            $unitSelect.val(unitValue).trigger('change');
            setScanAlert(p ? 'edit-receipt-scan-alert' : 'receipt-scan-alert', null);
        } else if (data.unit_no) {
            $unitSelect.val('').trigger('change');
            setScanAlert(
                p ? 'edit-receipt-scan-alert' : 'receipt-scan-alert',
                'Unit ' + normalizeUnitCode(data.unit_no) + ' not found in list — please select manually.',
                'warning'
            );
        }
    }

    function postReceiptScan(file) {
        const formData = new FormData();
        formData.append('receipt', file);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        return $.ajax({
            url: receiptScanUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false
        });
    }

    function setupSingleReceiptScan(config) {
        const {
            inputId,
            btnId,
            previewId,
            alertId,
            formPrefix
        } = config;

        $('#' + inputId).on('change', function() {
            const file = this.files[0];
            const $preview = $('#' + previewId);
            if (file) {
                $preview.find('img').attr('src', URL.createObjectURL(file));
                $preview.show();
            } else {
                $preview.hide().find('img').attr('src', '');
            }
            setScanAlert(alertId, null);
        });

        $('#' + btnId).on('click', function() {
            const fileInput = document.getElementById(inputId);
            const file = fileInput.files[0];
            if (!file) {
                setScanAlert(alertId, 'Please select a receipt image first.', 'danger');
                return;
            }

            const $btn = $(this);
            const $loading = $('#' + btnId + '-loading');
            $btn.prop('disabled', true);
            $loading.show();
            setScanAlert(alertId, null);

            postReceiptScan(file)
                .done(function(response) {
                    const receipts = response.success && Array.isArray(response.data) ? response.data : [];
                    if (receipts.length > 0) {
                        applyScannedDataToForm(formPrefix, receipts[0]);
                        if (receipts.length > 1) {
                            setScanAlert(
                                alertId,
                                receipts.length + ' receipts found in this image. Only the first was filled — use <strong>Scan Fuel Receipts</strong> to scan all receipts at once.',
                                'info'
                            );
                        } else {
                            setScanAlert(alertId, 'Receipt scanned — please review fields below.', 'success');
                        }
                    } else {
                        setScanAlert(alertId, response.message || 'Scan failed.', 'danger');
                    }
                })
                .fail(function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to scan receipt.';
                    setScanAlert(alertId, msg, 'danger');
                })
                .always(function() {
                    $btn.prop('disabled', false);
                    $loading.hide();
                });
        });
    }

    let bulkScanRows = [];
    let bulkScanRowId = 0;

    function updateBulkScanButtons() {
        const hasRows = bulkScanRows.filter(function(r) {
            return !r.error;
        }).length > 0;
        $('#btn-bulk-save-all').prop('disabled', !hasRows);
    }

    function renderBulkScanTable() {
        const $body = $('#bulk-scan-review-body');
        $body.empty();

        if (bulkScanRows.length === 0) {
            $body.append(
                '<tr id="bulk-scan-empty-row"><td colspan="' + bulkScanColspan +
                '" class="text-center text-muted">Select images and click Scan All</td></tr>'
            );
            updateBulkScanButtons();
            return;
        }

        bulkScanRows.forEach(function(row) {
            const trClass = row.error ? 'table-danger' : '';
            const thumb = row.thumbnailUrl ?
                '<img src="' + row.thumbnailUrl + '" class="img-thumbnail" style="max-height:48px;">' : '';
            const desc = row.error ?
                '<span class="text-danger">' + (row.errorMessage || 'Scan failed') + '</span>' :
                '<input type="text" class="form-control form-control-sm bulk-field" data-field="description" data-row="' +
                row.id + '" value="' + (row.data.description || '').replace(/"/g, '&quot;') + '">';
            const amountVal = row.error ? '' : (row.data.amount || '');
            const dateVal = row.error ? '' : (row.data.expense_date || '');
            const hmVal = row.error ? '' : (row.data.km_position || '');
            const unitVal = row.error ? '' : formatUnitNoDisplay(row.data.unit_no || '');
            const nopolVal = row.error ? '' : (row.data.nopol || '');
            const qtyVal = row.error ? '' : (row.data.qty || '');

            let rabCell = '';
            if (realizationAdvanceMultiBudgetForScan) {
                rabCell = '<td><select class="form-control form-control-sm bulk-field bulk-rab" data-field="rab_id" data-row="' +
                    row.id + '"><option value="">--</option>';
                $('#realization_rab_id option').each(function() {
                    if ($(this).val()) {
                        rabCell += '<option value="' + $(this).val() + '">' + $(this).text().substring(0, 40) +
                            '</option>';
                    }
                });
                rabCell += '</select></td>';
            }

            const rowHtml = '<tr class="' + trClass + '" data-row-id="' + row.id + '">' +
                '<td>' + thumb + '</td>' +
                '<td>' + desc + '</td>' +
                '<td><input type="text" class="form-control form-control-sm bulk-field" data-field="amount" data-row="' +
                row.id + '" value="' + amountVal + '"' + (row.error ? ' disabled' : '') + '></td>' +
                '<td><input type="date" class="form-control form-control-sm bulk-field" data-field="expense_date" data-row="' +
                row.id + '" value="' + dateVal + '"' + (row.error ? ' disabled' : '') + '></td>' +
                '<td><input type="text" class="form-control form-control-sm bulk-field" data-field="km_position" data-row="' +
                row.id + '" value="' + hmVal + '"' + (row.error ? ' disabled' : '') + '></td>' +
                '<td><input type="text" class="form-control form-control-sm bulk-field" data-field="unit_no" data-row="' +
                row.id + '" value="' + unitVal + '"' + (row.error ? ' disabled' : '') + '></td>' +
                '<td><input type="text" class="form-control form-control-sm bulk-field" data-field="nopol" data-row="' +
                row.id + '" value="' + (nopolVal || '') + '"' + (row.error ? ' disabled' : '') + '></td>' +
                '<td><input type="text" class="form-control form-control-sm bulk-field" data-field="qty" data-row="' +
                row.id + '" value="' + qtyVal + '"' + (row.error ? ' disabled' : '') + '></td>' +
                rabCell +
                '<td><button type="button" class="btn btn-xs btn-danger btn-bulk-remove-row" data-row="' + row.id +
                '"><i class="fas fa-trash"></i></button></td>' +
                '</tr>';

            $body.append(rowHtml);
        });

        updateBulkScanButtons();
    }

    function syncBulkRowFromInputs(rowId) {
        const row = bulkScanRows.find(function(r) {
            return r.id === rowId;
        });
        if (!row || row.error) {
            return;
        }
        $('.bulk-field[data-row="' + rowId + '"]').each(function() {
            const field = $(this).data('field');
            row.data[field] = $(this).val();
        });
    }

    async function bulkScanAll() {
        const files = document.getElementById('bulk-receipt-files').files;
        if (!files.length) {
            return;
        }

        bulkScanRows = [];
        bulkScanRowId = 0;
        renderBulkScanTable();

        const total = files.length;
        let done = 0;
        $('#bulk-scan-progress-wrap').show();
        $('#btn-bulk-scan-all').prop('disabled', true);
        $('#btn-bulk-save-all').prop('disabled', true);

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const thumbnailUrl = URL.createObjectURL(file);

            try {
                const response = await postReceiptScan(file);
                const receipts = response.success && Array.isArray(response.data) ? response.data : [];
                if (receipts.length > 0) {
                    receipts.forEach(function(receiptData) {
                        bulkScanRows.push({
                            id: ++bulkScanRowId,
                            thumbnailUrl: thumbnailUrl,
                            data: Object.assign({
                                type: 'fuel',
                                uom: 'liter'
                            }, receiptData),
                            error: false
                        });
                    });
                } else {
                    bulkScanRows.push({
                        id: ++bulkScanRowId,
                        thumbnailUrl: thumbnailUrl,
                        data: {},
                        error: true,
                        errorMessage: response.message || 'Scan failed'
                    });
                }
            } catch (e) {
                const msg = (e && e.responseJSON && e.responseJSON.message) ? e.responseJSON.message :
                    'Failed to scan receipt.';
                bulkScanRows.push({
                    id: ++bulkScanRowId,
                    thumbnailUrl: thumbnailUrl,
                    data: {},
                    error: true,
                    errorMessage: msg
                });
            }

            done++;
            const pct = Math.round((done / total) * 100);
            $('#bulk-scan-progress-text').text(done + ' / ' + total);
            $('#bulk-scan-progress-bar').css('width', pct + '%');
            renderBulkScanTable();
        }

        $('#btn-bulk-scan-all').prop('disabled', false);
    }

    function bulkSaveAll() {
        bulkScanRows.forEach(function(row) {
            if (!row.error) {
                syncBulkRowFromInputs(row.id);
            }
        });

        const details = bulkScanRows
            .filter(function(row) {
                return !row.error;
            })
            .map(function(row) {
                const d = row.data;
                const unitResolved = resolveUnitNoOptionValue(d.unit_no) || d.unit_no || null;
                return {
                    description: d.description || 'Fuel Kendaraan',
                    amount: String(d.amount || '').replace(/,/g, ''),
                    expense_date: d.expense_date || null,
                    km_position: d.km_position || null,
                    unit_no: unitResolved,
                    nopol: d.nopol || null,
                    qty: d.qty || null,
                    type: 'fuel',
                    uom: 'liter',
                    rab_id: d.rab_id || null
                };
            });

        if (!details.length) {
            window.showAlert('No valid rows to save.', 'warning');
            return;
        }

        const $btn = $('#btn-bulk-save-all');
        $btn.prop('disabled', true);

        $.ajax({
            url: bulkStoreDetailsUrl,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                realization_id: realizationIdForScan,
                details: details
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.message || 'Details saved.', 'success');
                    $('#bulk-scan-modal').modal('hide');
                    bulkScanRows = [];
                    $('#bulk-receipt-files').val('');
                    renderBulkScanTable();
                    window.refreshDetailsTable();
                    $('#btn-submit-realization').prop('disabled', false);
                } else {
                    window.showAlert(response.message || 'Save failed.', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let msg = 'Validation failed:<ul>';
                    $.each(errors, function(key, messages) {
                        msg += '<li>' + messages[0] + '</li>';
                    });
                    msg += '</ul>';
                    window.showAlert(msg, 'error');
                } else {
                    window.showAlert(xhr.responseJSON?.message || 'Failed to save details.', 'error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
                updateBulkScanButtons();
            }
        });
    }

    function initReceiptScanFeatures() {
        setupSingleReceiptScan({
            inputId: 'receipt-scan-input',
            btnId: 'btn-scan-receipt',
            previewId: 'receipt-scan-preview',
            alertId: 'receipt-scan-alert',
            formPrefix: ''
        });

        setupSingleReceiptScan({
            inputId: 'edit-receipt-scan-input',
            btnId: 'btn-edit-scan-receipt',
            previewId: 'edit-receipt-scan-preview',
            alertId: 'edit-receipt-scan-alert',
            formPrefix: 'edit-'
        });

        $('#bulk-receipt-files').on('change', function() {
            $('#btn-bulk-scan-all').prop('disabled', !this.files.length);
        });

        $('#btn-bulk-scan-all').on('click', function() {
            bulkScanAll();
        });

        $(document).on('click', '.btn-bulk-remove-row', function() {
            const rowId = parseInt($(this).data('row'), 10);
            bulkScanRows = bulkScanRows.filter(function(r) {
                return r.id !== rowId;
            });
            renderBulkScanTable();
        });

        $(document).on('change', '.bulk-field', function() {
            syncBulkRowFromInputs(parseInt($(this).data('row'), 10));
        });

        $('#btn-bulk-save-all').on('click', bulkSaveAll);

        $('#bulk-scan-modal').on('hidden.bs.modal', function() {
            $('#bulk-scan-progress-wrap').hide();
            $('#bulk-scan-progress-bar').css('width', '0%');
            $('#bulk-scan-progress-text').text('0 / 0');
        });
    }
</script>
