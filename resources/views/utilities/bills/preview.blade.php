@extends('templates.main')

@section('title_page')
    Preview Tagihan dari Struk
@endsection

@section('breadcrumb_title')
    utilities / tagihan / preview upload
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-eye"></i> Preview Tagihan — Konfirmasi Sebelum Simpan
                        </h3>
                        <a href="{{ route('utilities.bills.upload') }}" class="vj-action-item vj-action-back">
                            <i class="fas fa-arrow-left"></i> Upload Ulang
                        </a>
                    </div>
                    <form action="{{ route('utilities.bills.store-upload') }}" method="POST">
                        @csrf
                        <input type="hidden" name="jenis_utilitas" value="{{ $jenis_utilitas }}">
                        <input type="hidden" name="tipe" value="{{ $tipe }}">
                        <input type="hidden" name="project" value="{{ $project }}">
                        <input type="hidden" name="periode" value="{{ $periode }}">

                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="small text-muted d-block">Jenis Utilitas</label>
                                    <strong>{{ $jenis_label }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted d-block">Tipe</label>
                                    @if ($tipe === 'prepaid')
                                        <span class="vj-chip vj-chip-info">{{ $tipe_label }}</span>
                                    @else
                                        <span class="vj-chip vj-chip-neutral">{{ $tipe_label }}</span>
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted d-block">Project</label>
                                    <strong>{{ $project }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <label class="small text-muted d-block">Periode</label>
                                    <strong>{{ $periode }}</strong>
                                </div>
                                @if ($tipe === 'postpaid')
                                    <div class="col-md-3">
                                        <label for="tanggal_jatuh_tempo" class="small text-muted d-block">Tanggal Jatuh Tempo</label>
                                        <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo"
                                            class="form-control form-control-sm @error('tanggal_jatuh_tempo') is-invalid @enderror"
                                            value="{{ old('tanggal_jatuh_tempo', $tanggal_jatuh_tempo) }}" required>
                                        @error('tanggal_jatuh_tempo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                                <div class="col-md-{{ $tipe === 'postpaid' ? '1' : '4' }}">
                                    <label class="small text-muted d-block">Total Baris</label>
                                    <strong>{{ count($rows) }}</strong>
                                </div>
                            </div>

                            <div class="vj-note mb-2">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    @if ($tipe === 'prepaid')
                                        Periksa dan koreksi data sebelum menyimpan. Pembelian token disimpan langsung sebagai
                                        <strong>lunas</strong>. ID pelanggan baru akan otomatis dibuat.
                                    @else
                                        Periksa dan koreksi data sebelum menyimpan. Tagihan disimpan dengan status <strong>belum
                                            bayar</strong>. ID pelanggan baru akan otomatis dibuat.
                                    @endif
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 40px;">#</th>
                                            <th>ID Pelanggan</th>
                                            <th>Nama</th>
                                            <th style="width: 140px;">Nominal (Rp)</th>
                                            <th class="text-center" style="width: 100px;">Confidence</th>
                                            <th class="text-center" style="width: 110px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $index => $row)
                                            @php
                                                $confidence = (float) ($row['confidence'] ?? 0);
                                                $confidenceBadge =
                                                    $confidence >= 0.8
                                                        ? 'success'
                                                        : ($confidence >= 0.5
                                                            ? 'warning'
                                                            : 'danger');
                                            @endphp
                                            <tr>
                                                <td class="text-center align-middle">{{ $index + 1 }}</td>
                                                <td>
                                                    <input type="text" name="rows[{{ $index }}][idpel]"
                                                        class="form-control form-control-sm"
                                                        value="{{ old('rows.'.$index.'.idpel', $row['idpel']) }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="rows[{{ $index }}][nama]"
                                                        class="form-control form-control-sm"
                                                        value="{{ old('rows.'.$index.'.nama', $row['nama']) }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="rows[{{ $index }}][jumlah]"
                                                        class="form-control form-control-sm text-right" step="1" min="0"
                                                        value="{{ old('rows.'.$index.'.jumlah', $row['jumlah']) }}">
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="vj-chip vj-chip-{{ $confidenceBadge }}">
                                                        {{ number_format($confidence * 100, 0) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    @if ($row['matched'])
                                                        <span class="vj-chip vj-chip-info">Terdaftar</span>
                                                    @else
                                                        <span class="vj-chip vj-chip-primary">Baru</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="vj-btn vj-btn-success">
                                <i class="fas fa-save"></i> Simpan Semua
                            </button>
                            <a href="{{ route('utilities.bills.index') }}" class="vj-action-item">
                                Batal
                            </a>
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
