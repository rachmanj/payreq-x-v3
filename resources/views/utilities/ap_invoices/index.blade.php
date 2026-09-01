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
                                    {{ $invoice->sapBusinessPartner?->card_code ?: '-' }}
                                    <small class="d-block text-muted">{{ $invoice->sapBusinessPartner?->card_name }}</small>
                                </td>
                                <td>
                                    @if ($invoice->sap_doc_num)
                                        <span class="vj-chip vj-chip-info">{{ $invoice->sap_doc_num }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="vj-chip vj-chip-{{ $invoice->status === 'posted' ? 'success' : ($invoice->status === 'failed' ? 'danger' : 'neutral') }}">
                                        {{ strtoupper($invoice->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $invoice->submitted_at?->format('d-M-Y H:i') }}
                                    <small class="d-block text-muted">{{ $invoice->submittedBy?->name }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('utilities.ap-invoices.show', $invoice->id) }}"
                                        class="vj-action-item vj-action-item-xs vj-action-show" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
