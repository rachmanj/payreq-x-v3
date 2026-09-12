@extends('templates.main')

@section('title_page')
    Outgoing Payment Request
@endsection

@section('breadcrumb_title')
    outgoing
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-7">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0"><i class="fas fa-credit-card"></i> Outgoing Payment Request</h3>
                        <a href="{{ route('cashier.approveds.index') }}" class="vj-action-item vj-action-back"><i
                                class="fas fa-arrow-left"></i> Back</a>
                    </div>
                    <div class="card-body">
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0">Metode Pembayaran</h6>
                            </div>
                            <div class="card-body py-2">
                                {!! $payreq->payment_method_badge !!}
                                @if ($payreq->payment_method === 'transfer')
                                    <div class="alert alert-info py-2 mt-2 mb-0">
                                        <strong>Tujuan Transfer:</strong><br>
                                        {{ $payreq->transferAccount->displayLabel ?? 'Akun transfer tidak ditemukan' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php
                            $selectedTransferAccountId = old('transfer_account_id', $payreq->transfer_account_id ?? '');
                            $defaultTransferLabel = $payreq->transferAccount->displayLabel ?? 'Akun transfer tidak ditemukan';
                            if ($payreq->payment_method === 'transfer') {
                                $confirmTitle = 'Konfirmasi Transfer';
                            } else {
                                $confirmTitle = 'Konfirmasi Pembayaran';
                            }
                            $sourceAccountLabel = $payreq->payment_method === 'transfer'
                                ? 'Akun sumber: Rekening Bank'
                                : 'Akun sumber: Kas';
                        @endphp

                        <form action="{{ route('cashier.approveds.store_pay', $payreq->id) }}" method="POST" id="split-update">
                            @csrf @method('PUT')

                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" class="form-control" value="{{ $payreq->remarks }}" readonly>
                            </div>

                            <div class="form-group">
                                <small class="text-muted d-block mb-1">{{ $sourceAccountLabel }}</small>
                                <label for="account_id">Account No</label>
                                <select name="account_id" id="account_id" class="form-control">
                                    {{-- <option value="">-- select account no --</option> --}}
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->account_number . ' - ' . $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($payreq->payment_method === 'transfer')
                                <div class="form-group">
                                    <label for="transfer_account_id">Rekening Tujuan</label>
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <div class="flex-grow-1" style="min-width: 240px;">
                                            <select name="transfer_account_id" id="transfer_account_id"
                                                class="form-control select2bs4 @error('transfer_account_id') is-invalid @enderror"
                                                data-placeholder="Pilih rekening tujuan (opsional)" style="width: 100%;">
                                                <option value=""></option>
                                                @foreach ($requestorTransferAccounts as $account)
                                                    <option value="{{ $account->id }}"
                                                        {{ (string) $selectedTransferAccountId === (string) $account->id ? 'selected' : '' }}>
                                                        [Pemohon] {{ $account->displayLabel }}
                                                    </option>
                                                @endforeach
                                                @foreach ($cashierTransferAccounts as $account)
                                                    <option value="{{ $account->id }}"
                                                        {{ (string) $selectedTransferAccountId === (string) $account->id ? 'selected' : '' }}>
                                                        [Kasir] {{ $account->displayLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block mt-1">Kosongkan untuk memakai rekening tujuan default dari payreq.</small>
                                            @error('transfer_account_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="btn-open-transfer-modal"
                                            data-toggle="modal" data-target="#transferAccountModal">
                                            <i class="fas fa-plus"></i> Tambah Akun Transfer
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" class="form-control" name="date"
                                    value="{{ old('date', date('Y-m-d')) }}">
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" class="form-control @error('amount') is-invalid @enderror" name="amount"
                                    value="{{ old('amount', $available_amount) }}">
                                @error('amount')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </form>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="vj-btn vj-btn-primary" form="split-update"> Save</button>
                    </div>
                </div>

                @if ($payreq->payment_method === 'transfer')
                    <div class="modal fade" id="transferAccountModal" tabindex="-1" role="dialog"
                        aria-labelledby="transferAccountModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="transferAccountModalLabel">Tambah Akun Transfer</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="new_transfer_label">Label</label>
                                        <input type="text" id="new_transfer_label" class="form-control"
                                            placeholder="mis. Vendor A - Sertifikasi">
                                    </div>
                                    <div class="form-group">
                                        <label for="new_transfer_bank_id">Bank</label>
                                        <select id="new_transfer_bank_id" class="form-control select2bs4-modal"
                                            data-placeholder="Pilih Bank" style="width: 100%;">
                                            <option value=""></option>
                                            @foreach ($banks as $bank)
                                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="new_transfer_account_number">No. Rekening</label>
                                        <input type="text" id="new_transfer_account_number" class="form-control">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="new_transfer_account_name">Atas Nama</label>
                                        <input type="text" id="new_transfer_account_name" class="form-control">
                                    </div>
                                    <div id="transfer-account-modal-error" class="text-danger small mt-2" style="display:none;"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-primary" id="btn-save-transfer-account">
                                        <i class="fas fa-save"></i> Simpan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <div class="col-5">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0"><i class="fas fa-list-alt"></i> Outgoing Info</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th class="text-right">IDR</th>
                                    <th>Bukti Transfer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($outgoings->count() > 0)
                                    @foreach ($outgoings as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ date('d-M-Y', strtotime($item->outgoing_date)) }}</td>
                                            <td class="text-right">{{ number_format($item->amount, 0) }}</td>
                                            <td>
                                                @if ($item->payment_method === 'transfer')
                                                    @if ($item->attachments->where('verification_status', 'mismatch')->isNotEmpty())
                                                        <div class="vj-alert vj-alert-danger mb-2">
                                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                                            Ada bukti transfer yang tidak sesuai — mohon cek
                                                        </div>
                                                    @endif

                                                    @forelse ($item->attachments as $attachment)
                                                        <div class="d-flex align-items-center mb-1 flex-wrap">
                                                            <span class="mr-1">{!! $attachment->verification_status_badge !!}</span>
                                                            <a href="{{ route('cashier.outgoing-attachments.download', $attachment) }}"
                                                                class="small mr-2">{{ $attachment->original_name }}</a>
                                                            @if ((int) $attachment->created_by === (int) auth()->id())
                                                                <form action="{{ route('cashier.outgoing-attachments.destroy', $attachment) }}"
                                                                    method="POST" class="vj-action-item-form js-delete-attachment">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="vj-action-item vj-action-item-xs vj-action-cancel">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                            @if (in_array($attachment->verification_status, ['failed', 'pending'], true))
                                                                <form action="{{ route('cashier.outgoing-attachments.reverify', $attachment) }}"
                                                                    method="POST" class="vj-action-item-form ml-1">
                                                                    @csrf
                                                                    <button type="submit" class="vj-action-item vj-action-item-xs vj-action-print"
                                                                        title="Verifikasi ulang">
                                                                        <i class="fas fa-redo"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="vj-note d-block mb-1">
                                                            <i class="fas fa-info-circle"></i>
                                                            Belum ada bukti transfer
                                                        </div>
                                                    @endforelse

                                                    <form action="{{ route('cashier.outgoing-attachments.store', $item) }}"
                                                        method="POST" enctype="multipart/form-data"
                                                        id="upload-transfer-proof-{{ $item->id }}" class="mt-1">
                                                        @csrf
                                                        <div class="input-group input-group-sm">
                                                            <input type="file" name="file" class="form-control form-control-sm"
                                                                accept=".jpg,.jpeg,.png,.pdf" required>
                                                            <div class="input-group-append">
                                                                <button type="submit" class="vj-btn vj-btn-primary py-1 px-2">Upload</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th class="text-right">{{ number_format($outgoings->sum('amount'), 0) }}</th>
                                        <th></th>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center">No data</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    @include('partials.vj-soft-ui-swal')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            const isTransferPayment = @json($payreq->payment_method === 'transfer');
            const defaultTransferLabel = @json($defaultTransferLabel);
            const $transferSelect = $('#transfer_account_id');

            if ($transferSelect.length && !$transferSelect.hasClass('select2-hidden-accessible')) {
                $transferSelect.select2({
                    theme: 'bootstrap4',
                    placeholder: 'Pilih rekening tujuan (opsional)',
                    allowClear: true,
                    width: '100%'
                });
            }

            function formatCurrency(value) {
                const numeric = String(value || '').replace(/[^\d]/g, '');
                const amount = numeric === '' ? 0 : parseInt(numeric, 10);

                return amount.toLocaleString('id-ID');
            }

            function buildConfirmHtml() {
                const amountValue = $('input[name="amount"]').val();
                const formattedAmount = formatCurrency(amountValue);

                if (!isTransferPayment) {
                    return '<p>Bayar payreq ini? Nominal: <strong>Rp ' + formattedAmount + '</strong></p>';
                }

                let destinationLabel = defaultTransferLabel;

                if ($transferSelect.length && $transferSelect.val()) {
                    destinationLabel = $transferSelect.find('option:selected').text().trim();
                } else {
                    destinationLabel += ' (default dari payreq)';
                }

                return '<p>Transfer ke: <strong>' + $('<div>').text(destinationLabel).html() + '</strong></p>'
                    + '<p class="mb-0">Nominal: <strong>Rp ' + formattedAmount + '</strong></p>';
            }

            $('#split-update').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                VjSwal.fire({
                    title: @json($confirmTitle),
                    html: buildConfirmHtml(),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Bayar',
                    cancelButtonText: 'Batal',
                    confirmVariant: 'success',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

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
                            const optionLabel = '[Kasir] ' + response.label;
                            const option = new Option(optionLabel, response.id, true, true);
                            $transferSelect.append(option).trigger('change');
                            $('#new_transfer_label, #new_transfer_account_number, #new_transfer_account_name').val('');
                            $('#new_transfer_bank_id').val('').trigger('change');
                            $('#transferAccountModal').modal('hide');
                            toastr.success('Akun transfer berhasil ditambahkan.');
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

            // Konfirmasi hapus bukti transfer via SweetAlert
            $(document).on('submit', '.js-delete-attachment', function (e) {
                e.preventDefault();
                const form = this;
                VjSwal.fire({
                    title: 'Hapus Bukti Transfer',
                    html: '<p>Hapus file bukti transfer ini?</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmVariant: 'danger',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
