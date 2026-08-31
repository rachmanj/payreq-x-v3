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
                            if ($payreq->payment_method === 'transfer' && $payreq->transferAccount) {
                                $confirmTitle = 'Konfirmasi Transfer';
                                $confirmHtml = '<p>Transfer ke: <strong>'.e($payreq->transferAccount->displayLabel).'</strong></p><p class="mb-0">Nominal: <strong>Rp '.number_format($available_amount, 0).'</strong></p>';
                            } elseif ($payreq->payment_method === 'transfer') {
                                $confirmTitle = 'Konfirmasi Transfer';
                                $confirmHtml = '<p>Transfer (akun tidak ditemukan) — Nominal: <strong>Rp '.number_format($available_amount, 0).'</strong></p>';
                            } else {
                                $confirmTitle = 'Konfirmasi Pembayaran';
                                $confirmHtml = '<p>Bayar payreq ini? Nominal: <strong>Rp '.number_format($available_amount, 0).'</strong></p>';
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
@endsection

@section('scripts')
    @include('partials.vj-soft-ui-swal')
    <script>
        $(function() {
            $('#split-update').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                VjSwal.fire({
                    title: @json($confirmTitle),
                    html: @json($confirmHtml),
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
