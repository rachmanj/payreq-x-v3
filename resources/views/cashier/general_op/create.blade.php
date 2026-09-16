@extends('templates.main')

@section('title_page')
    Buat OP Umum
@endsection

@section('breadcrumb_title')
    cashier / op umum / create
@endsection

@section('content')
<div class="vj-show">
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h3 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Buat OP Umum (Pinbuk Bank → Cash)</h3>
                    <a href="{{ route('cashier.general-op.index') }}" class="vj-action-item vj-action-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form id="general-op-form" action="{{ route('cashier.general-op.submit') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="giro_id">Giro Bank <span class="text-danger">*</span></label>
                                    <select name="giro_id" id="giro_id" class="form-control select2 @error('giro_id') is-invalid @enderror" required>
                                        <option value="">-- Pilih Giro --</option>
                                        @foreach ($giros as $giro)
                                            <option value="{{ $giro->id }}" @selected(old('giro_id') == $giro->id)>
                                                {{ $giro->bank?->name }} - {{ $giro->acc_name ?: $giro->acc_no }} ({{ $giro->sap_account }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('giro_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bilyet_id">Bilyet (onhand) <span class="text-danger">*</span></label>
                                    <select name="bilyet_id" id="bilyet_id" class="form-control select2 @error('bilyet_id') is-invalid @enderror" required>
                                        <option value="">-- Pilih Bilyet --</option>
                                    </select>
                                    @error('bilyet_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="doc_date">Tanggal OP <span class="text-danger">*</span></label>
                                    <input type="date" name="doc_date" id="doc_date" class="form-control @error('doc_date') is-invalid @enderror"
                                        value="{{ old('doc_date', now()->format('Y-m-d')) }}" required>
                                    <input type="hidden" name="posting_date" id="posting_date" value="{{ old('posting_date', now()->format('Y-m-d')) }}">
                                    <input type="hidden" name="project" value="{{ $project }}">
                                    @error('doc_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="profit_center">Profit Center</label>
                                    <input type="text" name="profit_center" id="profit_center" maxlength="20"
                                        class="form-control @error('profit_center') is-invalid @enderror"
                                        value="{{ old('profit_center', $defaultProfitCenter) }}"
                                        placeholder="Default dari departemen user">
                                    <small class="form-text text-muted">Kosongkan untuk memakai sap_code departemen Anda.</small>
                                    @error('profit_center')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="amount">Nominal OP <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount" min="1" step="1"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" required>
                                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <input type="text" name="remarks" id="remarks" maxlength="254"
                                        class="form-control @error('remarks') is-invalid @enderror"
                                        value="{{ old('remarks') }}">
                                    @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Akun Tujuan (Cash)</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-line">
                                <i class="fas fa-plus"></i> Tambah Baris
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="destination-lines-table">
                                <thead>
                                    <tr>
                                        <th style="width: 35%">Akun Cash</th>
                                        <th style="width: 20%">Nominal</th>
                                        <th>Keterangan</th>
                                        <th style="width: 60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="destination-lines-body"></tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-right">TOTAL</th>
                                        <th class="text-right" id="destination-total">0</th>
                                        <th colspan="2">
                                            <span id="total-match-indicator" class="text-muted">Total harus sama dengan nominal OP</span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="card-footer px-0 pb-0 border-0 bg-transparent">
                            <button type="button" class="vj-btn vj-btn-secondary mr-2" id="btn-preview">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <button type="submit" class="vj-btn vj-btn-primary d-none" id="btn-submit-hidden">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('cashier.general_op.partials.preview-modal')
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
@php
    $oldDestinationLines = old('destination_accounts', [['account_id' => '', 'amount' => '', 'description' => '']]);
    $oldBilyetId = old('bilyet_id', '');
@endphp
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    const bilyetsByGiro = @json($bilyetsByGiro);
    const cashAccountOptions = @json($cashAccountOptions);
    const oldLines = @json($oldDestinationLines);
    const oldBilyetId = @json($oldBilyetId);

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
    }

    function buildAccountSelect(index, selectedId = '') {
        let html = '<select name="destination_accounts[' + index + '][account_id]" class="form-control account-select" required>';
        html += '<option value="">-- Pilih Akun --</option>';
        cashAccountOptions.forEach(function (account) {
            html += '<option value="' + account.id + '"' + (String(selectedId) === String(account.id) ? ' selected' : '') + '>' + account.label + '</option>';
        });
        html += '</select>';
        return html;
    }

    function addDestinationLine(data = {}) {
        const index = $('#destination-lines-body tr').length;
        const row = `
            <tr>
                <td>${buildAccountSelect(index, data.account_id || '')}</td>
                <td><input type="number" name="destination_accounts[${index}][amount]" class="form-control line-amount text-right" min="1" step="1" value="${data.amount || ''}" required></td>
                <td><input type="text" name="destination_accounts[${index}][description]" class="form-control" maxlength="254" value="${data.description || ''}"></td>
                <td class="text-center"><button type="button" class="btn btn-xs btn-danger btn-remove-line" title="Hapus"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        $('#destination-lines-body').append(row);
        updateDestinationTotal();
    }

    function reindexDestinationLines() {
        $('#destination-lines-body tr').each(function (rowIndex) {
            $(this).find('select, input').each(function () {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/destination_accounts\[\d+\]/, 'destination_accounts[' + rowIndex + ']'));
            });
        });
    }

    function updateDestinationTotal() {
        let total = 0;
        $('.line-amount').each(function () {
            total += Number($(this).val()) || 0;
        });
        $('#destination-total').text(formatRupiah(total));

        const opAmount = Number($('#amount').val()) || 0;
        const matched = opAmount > 0 && Math.abs(total - opAmount) <= 0.5;
        $('#total-match-indicator')
            .toggleClass('text-success', matched)
            .toggleClass('text-danger', !matched)
            .text(matched ? 'Total sesuai nominal OP' : 'Total harus sama dengan nominal OP');
    }

    function populateBilyets(giroId, selectedBilyetId = '') {
        const $bilyet = $('#bilyet_id');
        $bilyet.empty().append('<option value="">-- Pilih Bilyet --</option>');
        const items = bilyetsByGiro[giroId] || [];
        items.forEach(function (item) {
            $bilyet.append('<option value="' + item.id + '"' + (String(selectedBilyetId) === String(item.id) ? ' selected' : '') + '>' + item.label + '</option>');
        });
        $bilyet.trigger('change.select2');
    }

    function collectFormPayload() {
        return $('#general-op-form').serializeArray().reduce(function (acc, field) {
            acc[field.name] = field.value;
            return acc;
        }, {});
    }

    function renderPreview(data) {
        const preview = data.preview || {};
        const giro = preview.giro || {};
        const bilyet = preview.bilyet || {};
        const localImpact = data.local_impact || {};

        $('#preview-bank-giro').text((giro.acc_name || giro.acc_no || '-') + ' (' + (giro.sap_account || '-') + ')');
        $('#preview-bilyet').text(bilyet.full_nomor || ((bilyet.prefix || '') + ' ' + (bilyet.nomor || '')).trim());
        $('#preview-doc-date').text(preview.doc_date || '-');
        $('#preview-total').text('Rp ' + formatRupiah(preview.amount || 0));
        $('#preview-remarks').text(preview.remarks || '-');
        $('#preview-profit-center').text(preview.profit_center || '-');
        $('#preview-local-note').text(localImpact.note || 'Saldo kas akan bertambah, akun advance berkurang.');

        const $accountsBody = $('#preview-local-accounts').empty();
        (localImpact.destination_accounts || []).forEach(function (line) {
            $accountsBody.append(
                '<tr><td>' + (line.account_name || '-') + ' (' + (line.sap_account || '-') + ')</td>' +
                '<td class="text-right">Rp ' + formatRupiah(line.amount || 0) + '</td></tr>'
            );
        });

        $('#preview-sap-payload').text(JSON.stringify(data.sap_payload || {}, null, 2));
    }

    function buildSubmitSummary() {
        const giroText = $('#giro_id option:selected').text();
        const bilyetText = $('#bilyet_id option:selected').text();
        const amount = $('#amount').val();
        let accountsHtml = '<ul class="text-left mb-0">';
        $('#destination-lines-body tr').each(function () {
            const accountText = $(this).find('.account-select option:selected').text();
            const lineAmount = $(this).find('.line-amount').val();
            accountsHtml += '<li>' + accountText + ' — Rp ' + formatRupiah(lineAmount) + '</li>';
        });
        accountsHtml += '</ul>';

        return '<div class="text-left">' +
            '<p><strong>Bank:</strong> ' + giroText + '</p>' +
            '<p><strong>Bilyet:</strong> ' + bilyetText + '</p>' +
            '<p><strong>Total:</strong> Rp ' + formatRupiah(amount) + '</p>' +
            '<p><strong>Akun tujuan:</strong></p>' + accountsHtml +
            '</div>';
    }

    $(function () {
        $('.select2').select2({ theme: 'bootstrap4', width: '100%' });

        if (oldLines.length) {
            oldLines.forEach(function (line) { addDestinationLine(line); });
        } else {
            addDestinationLine();
        }

        $('#giro_id').on('change', function () {
            populateBilyets($(this).val(), oldBilyetId);
        });

        if ($('#giro_id').val()) {
            populateBilyets($('#giro_id').val(), oldBilyetId);
        }

        $('#btn-add-line').on('click', function () {
            addDestinationLine();
        });

        $(document).on('click', '.btn-remove-line', function () {
            if ($('#destination-lines-body tr').length <= 1) {
                Swal.fire('Perhatian', 'Minimal satu baris akun tujuan diperlukan.', 'warning');
                return;
            }
            $(this).closest('tr').remove();
            reindexDestinationLines();
            updateDestinationTotal();
        });

        $(document).on('input', '.line-amount, #amount', updateDestinationTotal);

        $('#doc_date').on('change', function () {
            $('#posting_date').val($(this).val());
        });

        $('#btn-preview').on('click', function () {
            const form = document.getElementById('general-op-form');
            if (!form.reportValidity()) {
                return;
            }

            $.ajax({
                url: '{{ route('cashier.general-op.preview') }}',
                method: 'POST',
                data: $('#general-op-form').serialize(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    renderPreview(response);
                    $('#generalOpPreviewModal').modal('show');
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Preview gagal. Periksa kembali isian form.';
                    Swal.fire('Validasi gagal', message, 'error');
                }
            });
        });

        $('#btn-confirm-submit').on('click', function () {
            $('#generalOpPreviewModal').modal('hide');
            Swal.fire({
                title: 'Konfirmasi Submit ke SAP',
                html: buildSubmitSummary(),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, submit ke SAP',
                cancelButtonText: 'Batal',
            }).then(function (result) {
                if (result.isConfirmed) {
                    $('#general-op-form').trigger('submit');
                }
            });
        });
    });
</script>
@endsection
