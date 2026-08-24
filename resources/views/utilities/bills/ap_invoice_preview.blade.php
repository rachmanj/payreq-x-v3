@extends('templates.main')

@section('title_page')
    Preview AP Invoice SAP
@endsection

@section('breadcrumb_title')
    utilities / tagihan / ap invoice
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-invoice"></i> Preview AP Invoice SAP — {{ $preview['jenis_label'] }}
                        </h3>
                        <a href="{{ route('utilities.bills.index') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <form action="{{ route('utilities.bills.ap-invoice.submit') }}" method="POST">
                        @csrf
                        @foreach ($billIds as $billId)
                            <input type="hidden" name="bill_ids[]" value="{{ $billId }}">
                        @endforeach

                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="small text-muted d-block">Vendor</label>
                                    <strong>{{ $preview['vendor']['code'] }} — {{ $preview['vendor']['name'] }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted d-block">Posting Date</label>
                                    <strong>{{ $preview['dates']['posting_date'] }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted d-block">Due Date</label>
                                    <strong>{{ $preview['dates']['due_date'] }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted d-block">Tax Code</label>
                                    <strong>{{ $preview['tax_code'] }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <label for="num_at_card" class="small text-muted d-block">Vendor Ref. No. (NumAtCard)</label>
                                    <input type="text" name="num_at_card" id="num_at_card"
                                        class="form-control form-control-sm @error('num_at_card') is-invalid @enderror"
                                        value="{{ old('num_at_card', $preview['num_at_card']) }}" required>
                                    @error('num_at_card')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="vj-note mb-3">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    Periksa baris di bawah sebelum submit ke SAP B1. Satu AP Invoice hanya untuk
                                    <strong>{{ $preview['jenis_label'] }}</strong>. Posting ini mencatat utang ke vendor,
                                    bukan menandai tagihan lunas.
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
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
                                        @foreach ($preview['lines'] as $line)
                                            <tr>
                                                <td>{{ $line['line_memo'] }}</td>
                                                <td>{{ $line['id_pelanggan'] }}</td>
                                                <td>{{ $line['nama'] }}</td>
                                                <td>{{ $line['lokasi'] ?: '-' }}</td>
                                                <td>{{ $line['project'] }}</td>
                                                <td>{{ $line['department'] }}</td>
                                                <td>
                                                    <small>{{ $line['account_number'] }}</small><br>
                                                    <small>{{ $line['account_name'] }}</small>
                                                </td>
                                                <td>{{ $line['periode'] }}</td>
                                                <td class="text-right">{{ number_format($line['jumlah_tagihan'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="8" class="text-right">Total ({{ $preview['line_count'] }} baris)</th>
                                            <th class="text-right">{{ number_format($preview['total'], 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <a href="{{ route('utilities.bills.index') }}" class="vj-action-item">Batal</a>
                            <button type="submit" class="vj-btn vj-btn-primary"
                                onclick="return confirm('Submit AP Invoice ini ke SAP B1? Tindakan ini tidak bisa dibatalkan dari aplikasi.')">
                                <i class="fas fa-paper-plane"></i> Submit ke SAP B1
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
