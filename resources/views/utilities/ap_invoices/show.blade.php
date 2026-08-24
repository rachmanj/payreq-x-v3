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
                        <a href="{{ route('utilities.bills.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Daftar Tagihan
                        </a>
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
                                <span class="vj-chip vj-chip-{{ $invoice->status === 'posted' ? 'success' : 'neutral' }}">
                                    {{ strtoupper($invoice->status) }}
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
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
