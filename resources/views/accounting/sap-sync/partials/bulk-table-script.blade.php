<script>
    $(function() {
        const dataRoute = @json($dataRoute);
        const tableSelector = @json($tableSelector ?? '#verifications');
        const selectedIds = new Set();

        function updateBulkUI() {
            $('#bulk-selected-count').text(selectedIds.size);
            $('#bulk-submit-btn').prop('disabled', selectedIds.size === 0);
        }

        const $tableEl = $(tableSelector);
        const table = $tableEl.DataTable({
            processing: true,
            serverSide: true,
            ajax: dataRoute,
            columns: [
                { data: 'select', orderable: false, searchable: false },
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nomor' },
                { data: 'date' },
                { data: 'status' },
                { data: 'amount' },
                { data: 'sap_journal_no' },
                { data: 'sap_posting_date' },
                { data: 'action', orderable: false, searchable: false },
            ],
            fixedHeader: true,
            columnDefs: [
                {
                    targets: [5],
                    className: 'text-right'
                }
            ]
        });

        $tableEl.on('change', '.bulk-select', function() {
            const id = $(this).val();
            if ($(this).is(':checked')) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            updateBulkUI();
        });

        $('#bulk-select-all, #bulk-select-all-header').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('#bulk-select-all, #bulk-select-all-header').prop('checked', isChecked);
            $tableEl.find('.bulk-select').each(function() {
                $(this).prop('checked', isChecked).trigger('change');
            });
        });

        table.on('draw', function() {
            $tableEl.find('.bulk-select').each(function() {
                const id = $(this).val();
                $(this).prop('checked', selectedIds.has(id));
            });

            const checkboxes = $tableEl.find('.bulk-select');
            const checked = $tableEl.find('.bulk-select:checked').length;
            const allVisibleSelected = checkboxes.length > 0 && checked === checkboxes.length;
            $('#bulk-select-all, #bulk-select-all-header').prop('checked', allVisibleSelected && checkboxes.length > 0);
        });

        $('#bulk-submit-form').on('submit', function(e) {
            e.preventDefault();

            if (selectedIds.size === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No journals selected',
                    text: 'Please select at least one verification journal to submit.',
                });
                return;
            }

            const form = this;
            const count = selectedIds.size;
            const summaryHtml = `
                <div class="text-left">
                    <p>You are about to submit <strong>${count}</strong> verification journal(s) to SAP Business One.</p>
                    <ul class="pl-3 mb-0">
                        <li>Journals will be created as <strong>drafts</strong> in SAP B1.</li>
                        <li>Each submission attempt is logged for audit purposes.</li>
                        <li>If SAP rejects a journal, you can retry from this page.</li>
                    </ul>
                </div>
            `;

            Swal.fire({
                title: 'Submit selected journals?',
                html: summaryHtml,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, submit to SAP B1',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    const container = $('#bulk-selected-inputs').empty();
                    selectedIds.forEach(function(id) {
                        container.append('<input type="hidden" name="verification_journal_ids[]" value="' + id + '">');
                    });

                    Swal.fire({
                        title: 'Submitting...',
                        html: 'Please wait while we send the selected journals to SAP B1.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                            form.submit();
                        }
                    });
                }
            });
        });
    });
</script>

