@extends('templates.main')

@section('title_page')
    AP Invoice SAP {{ $invoice->num_at_card }}
@endsection

@section('breadcrumb_title')
    utilities / ap invoice
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice"></i> AP Invoice SAP — {{ $jenisLabel }}
                        </h3>
                        <div class="d-flex flex-wrap gap-2">
                            @if (($canSubmitUtilityPayment ?? false) && $invoice->canCreateOutgoingPayment())
                                <button type="button"
                                    class="vj-btn vj-btn-primary btn-utility-create-op"
                                    data-invoice-id="{{ $invoice->id }}"
                                    data-num-at-card="{{ $invoice->num_at_card }}"
                                    data-sap-doc-num="{{ $invoice->sap_doc_num }}">
                                    <i class="fas fa-money-bill-wave"></i> Buat OP
                                </button>
                            @endif
                            <a href="{{ route('utilities.ap-invoices.index') }}" class="vj-action-item vj-action-back">
                                <i class="fas fa-arrow-left"></i> Daftar AP Invoice
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="small text-muted d-block">Vendor</label>
                                <strong>{{ $invoice->sapBusinessPartner?->code }} —
                                    {{ $invoice->sapBusinessPartner?->name }}</strong>
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted d-block">Vendor Ref. No.</label>
                                <strong>{{ $invoice->num_at_card }}</strong>
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted d-block">SAP DocNum</label>
                                <strong>{{ $invoice->sap_doc_num ?: '-' }}</strong>
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted d-block">Status</label>
                                <span class="vj-chip vj-chip-{{ $invoice->statusChipClass() }}">
                                    {{ $invoice->statusLabel() }}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted d-block">Submitted</label>
                                <strong>{{ $invoice->submitted_at?->format('d-M-Y H:i') ?: '-' }}</strong>
                                @if ($invoice->submittedBy)
                                    <br><small>{{ $invoice->submittedBy->name }}</small>
                                @endif
                            </div>
                        </div>

                        @if ($invoice->isPaid())
                            <div class="vj-form-panel mb-3">
                                <h6 class="mb-2"><i class="fas fa-check-circle text-success"></i> Informasi Pembayaran (OP)</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="small text-muted d-block">Tanggal Bayar</label>
                                        <strong>{{ $invoice->paid_at?->format('d-M-Y') ?: '-' }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted d-block">SAP OP DocNum</label>
                                        <strong>{{ $invoice->paid_sap_doc_num ?: '-' }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted d-block">Jumlah Dibayar</label>
                                        <strong>{{ number_format($invoice->paid_amount ?? 0, 2) }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted d-block">Dibayar Oleh</label>
                                        <strong>{{ $invoice->paidBy?->name ?: '-' }}</strong>
                                    </div>
                                </div>
                                @if ($invoice->payment_remarks)
                                    <p class="mb-0 mt-2 vj-note"><strong>Keterangan:</strong> {{ $invoice->payment_remarks }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>ID Pelanggan</th>
                                        <th>Nama</th>
                                        <th>Lokasi</th>
                                        <th>Project</th>
                                        <th>Department</th>
                                        <th>Akun</th>
                                        <th>Periode</th>
                                        <th class="text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->bills as $bill)
                                        <tr>
                                            <td>{{ $bill->customer->id_pelanggan ?? '-' }}</td>
                                            <td>{{ $bill->customer->nama ?? '-' }}</td>
                                            <td>{{ $bill->customer->lokasi ?: '-' }}</td>
                                            <td>{{ $bill->customer->project ?? '-' }}</td>
                                            <td>{{ $bill->customer->department ?? '-' }}</td>
                                            <td>
                                                @if ($bill->customer?->account)
                                                    <small>{{ $bill->customer->account->account_number }}</small><br>
                                                    <small>{{ $bill->customer->account->account_name }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $bill->periode }}</td>
                                            <td class="text-right">{{ number_format($bill->jumlah_tagihan, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="7" class="text-right">Total</th>
                                        <th class="text-right">{{ number_format($invoice->total_amount, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canSubmitUtilityPayment ?? false)
        @include('utilities.ap_invoices._sap_payment_modal')
    @endif
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    @if ($canSubmitUtilityPayment ?? false)
        <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @endif
@endsection

@if ($canSubmitUtilityPayment ?? false)
    @include('utilities.ap_invoices._sap_payment_scripts', [
        'canSubmitUtilityPayment' => $canSubmitUtilityPayment,
        'defaultPreparedBy' => $defaultPreparedBy ?? auth()->user()?->name ?? '',
    ])
@endif
