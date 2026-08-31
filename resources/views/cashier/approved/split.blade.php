@extends('templates.main')

@section('title_page')
    Outgoing Payment Request
@endsection

@section('breadcrumb_title')
    outgoing
@endsection

@section('content')
    <div class="row">
        <div class="col-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">Payreq No:
                        {{ $payreq->nomor . ' | ' . $payreq->requestor->name . ' | IDR. ' . number_format($payreq->amount, 0) }}
                        </h3>
                        <a href="{{ route('cashier.approveds.index') }}" class="btn btn-sm btn-success float-right"><i
                                class="fas fa-arrow-left"></i> Back</a>
                </div>
                <div class="card-body">
                    <div class="card card-outline card-secondary mb-3">
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
                            $saveConfirm = "return confirm('Transfer ke: {$payreq->transferAccount->displayLabel} - Rp " . number_format($available_amount, 0) . "?')";
                        } elseif ($payreq->payment_method === 'transfer') {
                            $saveConfirm = "return confirm('Transfer (akun tidak ditemukan) - Rp " . number_format($available_amount, 0) . "?')";
                        } else {
                            $saveConfirm = "return confirm('Bayar payreq ini?')";
                        }
                        $sourceAccountLabel = $payreq->payment_method === 'transfer'
                            ? 'Akun sumber: Rekening Bank'
                            : 'Akun sumber: Kas';
                    @endphp

                    <form action="{{ route('cashier.approveds.store_pay', $payreq->id) }}" method="POST" id="split-update"
                        onsubmit="{{ $saveConfirm }}">
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
                    <button type="submit" class="btn btn-sm btn-primary" form="split-update"> Save</button>
                </div>
            </div>

        </div>

        <div class="col-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Outgoing Info</h4>
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
                                                    <div class="alert alert-warning py-1 px-2 mb-2 small">
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
                                                                method="POST" class="d-inline"
                                                                onsubmit="return confirm('Hapus bukti transfer ini?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-xs btn-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if (in_array($attachment->verification_status, ['failed', 'pending'], true))
                                                            <form action="{{ route('cashier.outgoing-attachments.reverify', $attachment) }}"
                                                                method="POST" class="d-inline ml-1">
                                                                @csrf
                                                                <button type="submit" class="btn btn-xs btn-secondary"
                                                                    title="Verifikasi ulang">
                                                                    <i class="fas fa-redo"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <small class="text-muted d-block mb-1">Belum ada bukti transfer</small>
                                                @endforelse

                                                <form action="{{ route('cashier.outgoing-attachments.store', $item) }}"
                                                    method="POST" enctype="multipart/form-data"
                                                    id="upload-transfer-proof-{{ $item->id }}" class="mt-1">
                                                    @csrf
                                                    <div class="input-group input-group-sm">
                                                        <input type="file" name="file" class="form-control form-control-sm"
                                                            accept=".jpg,.jpeg,.png,.pdf" required>
                                                        <div class="input-group-append">
                                                            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
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
@endsection
