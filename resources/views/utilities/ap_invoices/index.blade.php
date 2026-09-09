@extends('templates.main')

@section('title_page')
    AP Invoice Utilities
@endsection

@section('breadcrumb_title')
    utilities / ap-invoices
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0">
                    <i class="fas fa-file-invoice"></i> AP Invoice SAP — Utilities
                </h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Jenis</th>
                            <th>Periode</th>
                            <th>Tagihan</th>
                            <th>Total</th>
                            <th>Vendor SAP</th>
                            <th>DocNum SAP</th>
                            <th>Status</th>
                            <th>Dikirim</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td>{{ $jenisLabel[$invoice->jenis_utilitas] ?? strtoupper($invoice->jenis_utilitas) }}</td>
                                <td>{{ $invoice->periode_summary }}</td>
                                <td>
                                    {{ $invoice->bills->count() }} tagihan
                                    <small class="d-block text-muted">
                                        @foreach ($invoice->bills->pluck('customer')->unique('id')->take(2) as $c)
                                            {{ $c?->nama }}@if (! $loop->last){{ ', ' }}@endif
                                        @endforeach
                                        @if ($invoice->bills->pluck('customer')->unique('id')->count() > 2)
                                            dkk.
                                        @endif
                                    </small>
                                </td>
                                <td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td>
                                <td>
                                    {{ $invoice->sapBusinessPartner?->code ?: '-' }}
                                    <small class="d-block text-muted">{{ $invoice->sapBusinessPartner?->name }}</small>
                                </td>
                                <td>
                                    @if ($invoice->sap_doc_num)
                                        <span class="vj-chip vj-chip-info">{{ $invoice->sap_doc_num }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                    @if ($invoice->isPaid() && $invoice->paid_sap_doc_num)
                                        <small class="d-block text-muted">OP: {{ $invoice->paid_sap_doc_num }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="vj-chip vj-chip-{{ $invoice->statusChipClass() }}">
                                        {{ $invoice->statusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    {{ $invoice->submitted_at?->format('d-M-Y H:i') }}
                                    <small class="d-block text-muted">{{ $invoice->submittedBy?->name }}</small>
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('utilities.ap-invoices.show', $invoice->id) }}"
                                        class="vj-action-item vj-action-item-xs vj-action-show" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if (($canSubmitUtilityPayment ?? false) && $invoice->canCreateOutgoingPayment())
                                        <button type="button"
                                            class="vj-action-item vj-action-item-xs vj-action-primary btn-utility-create-op"
                                            title="Buat OP"
                                            data-invoice-id="{{ $invoice->id }}"
                                            data-num-at-card="{{ $invoice->num_at_card }}"
                                            data-sap-doc-num="{{ $invoice->sap_doc_num }}"
                                            data-total-amount="{{ $invoice->total_amount }}">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Belum ada AP Invoice. Buat dari halaman Tagihan (Preview → Submit SAP).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
        'defaultPreparedBy' => auth()->user()?->name ?? '',
    ])
@endif
