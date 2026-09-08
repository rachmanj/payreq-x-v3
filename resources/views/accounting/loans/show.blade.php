@extends('templates.main')

@section('title_page')
    Installment — {{ $loan->loan_code }}
@endsection

@section('breadcrumb_title')
    accounting / installment / {{ $loan->loan_code }}
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <x-loan-links page="index" />

                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-contract"></i> Kontrak: {{ $loan->loan_code }}
                        </h3>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="vj-btn vj-btn-primary" data-toggle="modal" data-target="#import-schedule-modal">
                                <i class="fas fa-file-upload"></i> Import Jadwal
                            </button>
                            <a href="{{ route('accounting.loans.installments.generate', $loan->id) }}"
                                class="vj-btn vj-btn-success">Generate Installment</a>
                            @can('submit_sap_ap_invoice_installment')
                                <button type="button" class="vj-btn vj-btn-warning" id="btn-bulk-submit-ap" disabled>
                                    <i class="fas fa-file-invoice-dollar"></i> Bulk Submit AP
                                </button>
                            @endcan
                            <button type="button" class="vj-btn vj-btn-primary" id="btn-sync-paid">
                                <i class="fas fa-sync"></i> Sync Paid
                            </button>
                            <a href="{{ route('accounting.loans.index') }}" class="vj-action-item vj-action-back">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Loan Code</dt>
                            <dd class="col-sm-9">{{ $loan->loan_code }}</dd>
                            <dt class="col-sm-3">Creditor</dt>
                            <dd class="col-sm-9">{{ $loan->creditor->name }}</dd>
                            <dt class="col-sm-3">Principal</dt>
                            <dd class="col-sm-9">IDR {{ number_format((float) $loan->principal, 0, ',', '.') }}</dd>
                            <dt class="col-sm-3">Total Bunga</dt>
                            <dd class="col-sm-9">{{ $loan->total_bunga ? 'IDR ' . number_format((float) $loan->total_bunga, 0, ',', '.') : '—' }}</dd>
                            <dt class="col-sm-3">Description</dt>
                            <dd class="col-sm-9">{{ $loan->description }}</dd>
                            <dt class="col-sm-3">Status</dt>
                            <dd class="col-sm-9 mb-0">{{ $loan->status ? ucfirst($loan->status) : '-' }}</dd>
                        </dl>
                    </div>

                    <div class="card-body table-responsive border-top">
                        <table id="installments-table" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                @can('submit_sap_ap_invoice_installment')
                                    <th><input type="checkbox" id="check-all-installments" title="Pilih semua"></th>
                                @endcan
                                <th>#</th>
                                <th>Due Date</th>
                                <th>Pokok</th>
                                <th>Bunga</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Bilyet No</th>
                                <th>Account</th>
                                <th>Method</th>
                                <th>SAP AP</th>
                                <th>SAP Status</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Split --}}
    <div class="modal fade" id="split-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Split Pokok / Bunga — Angsuran #<span id="split-angsuran-label"></span></h4>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pokok (Principal)</label>
                        <input type="number" id="split-principal" class="form-control" min="0" step="1">
                    </div>
                    <div class="form-group">
                        <label>Bunga (Interest)</label>
                        <input type="number" id="split-interest" class="form-control" min="0" step="1">
                    </div>
                    <div class="form-group">
                        <label>Total</label>
                        <input type="text" id="split-total" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal">Batal</button>
                    <button type="button" class="vj-btn vj-btn-primary" id="btn-save-split">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal OP --}}
    <div class="modal fade" id="op-modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Buat Outgoing Payment — Angsuran #<span id="op-angsuran-label"></span></h4>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" id="op-preview-body">
                    <div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal">Batal</button>
                    <button type="button" class="vj-btn vj-btn-success" id="btn-confirm-op" disabled>Buat OP</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import Jadwal --}}
    <div class="modal fade" id="import-schedule-modal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Import Jadwal Angsuran</h4>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Format</label>
                            <select id="import-format" class="form-control">
                                <option value="bca_outstanding">Excel BCA-style (Outstanding)</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label>File Excel</label>
                            <input type="file" id="import-file" class="form-control" accept=".xlsx,.xls">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="vj-btn vj-btn-primary btn-block" id="btn-import-preview">
                                <i class="fas fa-search"></i> Preview
                            </button>
                        </div>
                    </div>
                    <div id="import-preview-area" style="display:none;">
                        <div id="import-warnings"></div>
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-bordered" id="import-preview-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th class="text-right">Pokok</th>
                                        <th class="text-right">Bunga</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="mt-2" id="import-verification"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal">Tutup</button>
                    <button type="button" class="vj-btn vj-btn-primary" id="btn-import-save" style="display:none;">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    @include('partials.vj-soft-ui-swal')

    <script>
        const LOAN_ID = {{ $loan->id }};
        const CSRF_TOKEN = '{{ csrf_token() }}';
        let installmentsTable;
        let currentSplitId = null;
        let currentOpId = null;
        let importPreviewToken = null;

        function fmtIdr(n) {
            return new Intl.NumberFormat('id-ID').format(n || 0);
        }

        function reloadTable() {
            installmentsTable.ajax.reload(null, false);
        }

        function updateBulkButton() {
            const checked = $('.installment-bulk-check:checked').length;
            $('#btn-bulk-submit-ap').prop('disabled', checked === 0);
        }

        $(function() {
            const columns = [
                @can('submit_sap_ap_invoice_installment')
                { data: 'select', orderable: false, searchable: false },
                @endcan
                { data: 'angsuran_ke' },
                { data: 'due_date' },
                { data: 'principal_amount', orderable: false },
                { data: 'interest_amount', orderable: false },
                { data: 'bilyet_amount' },
                { data: 'paid_status', orderable: false, searchable: false },
                { data: 'bilyet_no' },
                { data: 'account' },
                { data: 'payment_method' },
                { data: 'sap_ap_badge', orderable: false, searchable: false },
                { data: 'sap_status', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false },
            ];

            installmentsTable = $('#installments-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('accounting.loans.installments.data', $loan->id) }}',
                columns: columns,
                order: [[@can('submit_sap_ap_invoice_installment') 1 @else 0 @endcan, 'asc']],
                drawCallback: function() {
                    updateBulkButton();
                }
            });

            $('#check-all-installments').on('change', function() {
                $('.installment-bulk-check').prop('checked', $(this).is(':checked'));
                updateBulkButton();
            });

            $(document).on('change', '.installment-bulk-check', updateBulkButton);

            // Split modal
            $(document).on('click', '.btn-split-installment', function() {
                currentSplitId = $(this).data('id');
                $('#split-angsuran-label').text($(this).data('angsuran'));
                $('#split-principal').val($(this).data('principal') || '');
                $('#split-interest').val($(this).data('interest') || '');
                updateSplitTotal();
                $('#split-modal').modal('show');
            });

            $('#split-principal, #split-interest').on('input', updateSplitTotal);

            function updateSplitTotal() {
                const total = (parseFloat($('#split-principal').val()) || 0) + (parseFloat($('#split-interest').val()) || 0);
                $('#split-total').val(fmtIdr(total));
            }

            $('#btn-save-split').on('click', function() {
                fetch(`{{ url('accounting/loans/installments') }}/${currentSplitId}/save-split`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        principal_amount: $('#split-principal').val(),
                        interest_amount: $('#split-interest').val(),
                    })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        $('#split-modal').modal('hide');
                        VjSwal.fire({ icon: 'success', title: 'Berhasil', text: data.message });
                        reloadTable();
                    } else {
                        VjSwal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                    }
                });
            });

            // Submit AP
            $(document).on('click', '.btn-submit-ap', function() {
                const id = $(this).data('id');
                const angsuran = $(this).data('angsuran');
                const amount = $(this).data('amount');
                VjSwal.fire({
                    title: 'Submit AP ke SAP?',
                    html: `Angsuran #${angsuran}<br>Nominal: IDR ${amount}`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, submit AP',
                    cancelButtonText: 'Batal',
                    confirmVariant: 'warning',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch(`{{ url('accounting/loans/installments') }}/${id}/submit-sap-ap`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
                    }).then(r => r.json()).then(data => {
                        VjSwal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Berhasil' : 'Gagal',
                            text: data.message + (data.doc_num ? ' (DocNum: ' + data.doc_num + ')' : '')
                        });
                        reloadTable();
                    });
                });
            });

            // Bulk Submit AP
            $('#btn-bulk-submit-ap').on('click', function() {
                const ids = $('.installment-bulk-check:checked').map(function() { return parseInt($(this).val()); }).get();
                if (!ids.length) return;
                VjSwal.fire({
                    title: 'Bulk Submit AP?',
                    text: `${ids.length} angsuran akan dikirim ke SAP.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, submit',
                    cancelButtonText: 'Batal',
                    confirmVariant: 'warning',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch('{{ route('accounting.loans.installments.bulk_submit_sap_ap') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                        body: JSON.stringify({ installment_ids: ids })
                    }).then(r => r.json()).then(data => {
                        let html = '<ul class="text-left" style="max-height:300px;overflow-y:auto;">';
                        (data.results || []).forEach(r => {
                            const icon = r.success ? '✓' : '✗';
                            html += `<li>${icon} #${r.angsuran_ke}: ${r.message}</li>`;
                        });
                        html += '</ul>';
                        VjSwal.fire({ icon: 'info', title: 'Hasil Bulk Submit', html: html, width: 600 });
                        reloadTable();
                    });
                });
            });

            // OP preview & create
            $(document).on('click', '.btn-create-op', function() {
                currentOpId = $(this).data('id');
                $('#op-angsuran-label').text($(this).data('angsuran'));
                $('#op-preview-body').html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i></div>');
                $('#btn-confirm-op').prop('disabled', true);
                $('#op-modal').modal('show');

                fetch(`{{ url('accounting/loans/installments') }}/${currentOpId}/op-preview`, {
                    headers: { 'Accept': 'application/json' }
                }).then(r => r.json()).then(resp => {
                    const d = resp.data;
                    let html = `<dl class="row">
                        <dt class="col-sm-4">Vendor</dt><dd class="col-sm-8">${d.vendor || '-'} (${d.vendor_code || '-'})</dd>
                        <dt class="col-sm-4">AP DocNum</dt><dd class="col-sm-8">${d.sap_ap_doc_num}</dd>
                        <dt class="col-sm-4">Nominal</dt><dd class="col-sm-8">IDR ${d.amount_formatted}</dd>
                        <dt class="col-sm-4">Akun Bank Debit</dt><dd class="col-sm-8">${d.bank_account_label || 'Belum di-set'}</dd>
                        <dt class="col-sm-4">Metode</dt><dd class="col-sm-8">${d.payment_method_label}</dd>
                        <dt class="col-sm-4">Status Bilyet</dt><dd class="col-sm-8">${d.bilyet_status || '-'}</dd>
                    </dl>
                    <div class="form-group">
                        <label>Tanggal Pembayaran</label>
                        <input type="date" id="op-payment-date" class="form-control" value="${d.default_payment_date}">
                    </div>`;
                    if (d.payment_method === 'bilyet' && d.bilyet_status !== 'cair') {
                        html += `<div class="form-check"><input type="checkbox" class="form-check-input" id="op-confirm-bilyet"><label class="form-check-label" for="op-confirm-bilyet">Konfirmasi bilyet sudah cair</label></div>`;
                    }
                    if (d.payment_method === 'auto_debit') {
                        html += `<div class="form-check"><input type="checkbox" class="form-check-input" id="op-confirm-autodebit"><label class="form-check-label" for="op-confirm-autodebit">Konfirmasi autodebet</label></div>`;
                    }
                    $('#op-preview-body').html(html);
                    $('#btn-confirm-op').prop('disabled', false);
                });
            });

            $('#btn-confirm-op').on('click', function() {
                VjSwal.fire({
                    title: 'Buat Outgoing Payment?',
                    text: 'Pembayaran akan dikirim ke SAP B1.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, buat OP',
                    cancelButtonText: 'Batal',
                    confirmVariant: 'success',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const payload = {
                        payment_date: $('#op-payment-date').val(),
                        confirm_bilyet_cair: $('#op-confirm-bilyet').is(':checked') ? 1 : 0,
                        confirm_auto_debit: $('#op-confirm-autodebit').is(':checked') ? 1 : 0,
                    };
                    fetch(`{{ url('accounting/loans/installments') }}/${currentOpId}/create-sap-op`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                        body: JSON.stringify(payload)
                    }).then(r => r.json()).then(data => {
                        $('#op-modal').modal('hide');
                        VjSwal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.success ? 'Berhasil' : 'Gagal',
                            text: data.message + (data.doc_num ? ' (DocNum: ' + data.doc_num + ')' : '')
                        });
                        reloadTable();
                    });
                });
            });

            // Sync paid
            $('#btn-sync-paid').on('click', function() {
                VjSwal.fire({
                    title: 'Sync status lunas dari SAP?',
                    text: 'Angsuran dengan AP yang sudah dibayar di SAP akan diperbarui.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, sync',
                    cancelButtonText: 'Batal',
                    confirmVariant: 'primary',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch('{{ route('accounting.loans.sync_paid') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                        body: JSON.stringify({ loan_id: LOAN_ID })
                    }).then(r => r.json()).then(data => {
                        let synced = (data.results || []).filter(r => r.synced).length;
                        VjSwal.fire({ icon: 'info', title: 'Sync Selesai', text: `${synced} angsuran diperbarui.` });
                        reloadTable();
                    });
                });
            });

            // Import preview
            $('#btn-import-preview').on('click', function() {
                const file = $('#import-file')[0].files[0];
                if (!file) {
                    VjSwal.fire({ icon: 'warning', title: 'Pilih file', text: 'Silakan pilih file Excel terlebih dahulu.' });
                    return;
                }
                const formData = new FormData();
                formData.append('file', file);
                formData.append('format', $('#import-format').val());
                formData.append('_token', CSRF_TOKEN);

                fetch('{{ route('accounting.loans.import_schedule_preview', $loan->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                }).then(r => r.json()).then(data => {
                    if (!data.success) {
                        VjSwal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                        return;
                    }
                    importPreviewToken = data.preview_token;
                    const rows = (data.matched_unit && data.matched_unit.rows) ? data.matched_unit.rows : [];
                    let tbody = '';
                    rows.forEach(row => {
                        tbody += `<tr>
                            <td>${row.no}</td>
                            <td>${row.due_date}</td>
                            <td class="text-right">${fmtIdr(row.principal)}</td>
                            <td class="text-right">${fmtIdr(row.interest)}</td>
                            <td class="text-right">${fmtIdr(row.total)}</td>
                        </tr>`;
                    });
                    $('#import-preview-table tbody').html(tbody);

                    let warnHtml = '';
                    (data.verification?.warnings || []).forEach(w => {
                        warnHtml += `<div class="alert alert-warning">${w}</div>`;
                    });
                    $('#import-warnings').html(warnHtml);
                    $('#import-verification').html(
                        `<small class="text-muted">Total pokok: ${fmtIdr(data.verification?.principal_total)} | Total bunga: ${fmtIdr(data.verification?.interest_total)} | ${rows.length} baris</small>`
                    );
                    $('#import-preview-area').show();
                    $('#btn-import-save').show();
                });
            });

            $('#btn-import-save').on('click', function() {
                if (!importPreviewToken) return;
                VjSwal.fire({
                    title: 'Simpan jadwal import?',
                    text: 'Hanya kolom split yang kosong akan diisi.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, simpan',
                    cancelButtonText: 'Batal',
                    confirmVariant: 'primary',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch('{{ route('accounting.loans.import_schedule', $loan->id) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                        body: JSON.stringify({ preview_token: importPreviewToken })
                    }).then(r => r.json()).then(data => {
                        VjSwal.fire({ icon: data.success ? 'success' : 'error', title: data.success ? 'Berhasil' : 'Gagal', text: data.message });
                        if (data.success) {
                            $('#import-schedule-modal').modal('hide');
                            reloadTable();
                        }
                    });
                });
            });
        });
    </script>
@endsection
