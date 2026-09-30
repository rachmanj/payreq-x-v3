@extends('templates.main')

@section('title_page')
    Rekonsiliasi 3 Arah PPN Masukan
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn / rekonsiliasi
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            @include('accounting.tax.ppn.partials.nav', ['masaPajak' => $masaPajak])

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('accounting.tax.ppn.rekonsiliasi.index') }}" class="form-inline">
                        <label class="mr-2" for="masa_pajak">Masa pajak</label>
                        <select name="masa_pajak" id="masa_pajak" class="form-control form-control-sm mr-2">
                            @forelse ($masaOptions as $opt)
                                <option value="{{ $opt }}" @selected($opt === $masaPajak)>{{ $opt }}</option>
                            @empty
                                <option value="{{ $masaPajak }}">{{ $masaPajak }}</option>
                            @endforelse
                        </select>
                        <button type="submit" class="btn btn-sm btn-secondary">Tampilkan</button>
                    </form>
                    <p class="text-muted small mt-2 mb-0">
                        Data diambil dari snapshot rekonsiliasi terakhir. Jalankan <strong>Rekonsiliasi</strong> di
                        <a href="{{ route('accounting.tax.ppn.index', ['masa_pajak' => $masaPajak]) }}">dashboard PPN</a>
                        setelah impor Coretax untuk memperbarui temuan.
                    </p>
                </div>
            </div>

            @if (! $period || ! is_array($snapshot))
                <div class="alert alert-warning">
                    Belum ada snapshot rekonsiliasi untuk masa {{ $masaPajak }}.
                </div>
            @else
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card card-outline card-primary">
                            <div class="card-header"><h3 class="card-title">SAP (PM)</h3></div>
                            <div class="card-body">
                                <p class="mb-0 h4">{{ number_format((float) ($snapshot['sap']['pm_total'] ?? 0), 0, ',', '.') }}</p>
                                <small class="text-muted">
                                    {{ number_format($threeWay['counts']['sap_pm_with_fp'] ?? 0) }} faktur dengan nomor FP
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-outline card-success">
                            <div class="card-header"><h3 class="card-title">Aplikasi (PM)</h3></div>
                            <div class="card-body">
                                <p class="mb-0 h4">{{ number_format((float) ($snapshot['app']['pm_total'] ?? 0), 0, ',', '.') }}</p>
                                <small class="text-muted">
                                    {{ number_format($threeWay['counts']['app_pm_with_fp'] ?? ($snapshot['app']['pm_faktur_count'] ?? 0)) }} baris faktur masukan
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-outline card-info">
                            <div class="card-header"><h3 class="card-title">Coretax</h3></div>
                            <div class="card-body">
                                <p class="mb-0 h4">{{ number_format((float) ($snapshot['coretax']['pm_total'] ?? 0), 0, ',', '.') }}</p>
                                <small class="text-muted">
                                    {{ number_format($threeWay['counts']['coretax'] ?? 0) }} baris prepopulasi
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                @if (! $threeWay)
                    <div class="alert alert-info">Snapshot lama — jalankan rekonsiliasi ulang untuk temuan 3 arah.</div>
                @else
                    @php
                        $sections = [
                            'matched_three_way' => ['Cocok 3 arah', 'success'],
                            'sap_app_not_coretax' => ['Ada di SAP & aplikasi, tidak di Coretax', 'warning'],
                            'coretax_only' => ['Ada di Coretax, tidak di SAP/aplikasi', 'danger'],
                        ];
                    @endphp

                    @foreach ($sections as $key => [$title, $color])
                        <div class="card card-outline card-{{ $color }} mb-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ $title }}
                                    ({{ number_format($threeWay['counts'][$key] ?? count($threeWay[$key] ?? [])) }})
                                </h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nomor FP</th>
                                            @if ($key === 'matched_three_way')
                                                <th>PPN SAP</th>
                                                <th>PPN App</th>
                                                <th>PPN Coretax</th>
                                            @elseif ($key === 'sap_app_not_coretax')
                                                <th>PPN SAP</th>
                                                <th>PPN App</th>
                                                <th>Supplier</th>
                                            @else
                                                <th>PPN Coretax</th>
                                                <th>Penjual</th>
                                                <th>Status</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($threeWay[$key] ?? [] as $row)
                                            <tr>
                                                <td><small>{{ $row['faktur_no'] }}</small></td>
                                                @if ($key === 'matched_three_way')
                                                    <td class="text-right"><small>{{ number_format($row['sap']['vat_sum'] ?? 0, 0, ',', '.') }}</small></td>
                                                    <td class="text-right"><small>{{ number_format($row['app']['ppn'] ?? 0, 0, ',', '.') }}</small></td>
                                                    <td class="text-right"><small>{{ number_format($row['coretax']['ppn'] ?? 0, 0, ',', '.') }}</small></td>
                                                @elseif ($key === 'sap_app_not_coretax')
                                                    <td class="text-right"><small>{{ number_format($row['sap']['vat_sum'] ?? 0, 0, ',', '.') }}</small></td>
                                                    <td class="text-right"><small>{{ number_format($row['app']['ppn'] ?? 0, 0, ',', '.') }}</small></td>
                                                    <td><small>{{ $row['sap']['card_name'] ?? '' }}</small></td>
                                                @else
                                                    <td class="text-right"><small>{{ number_format($row['coretax']['ppn'] ?? 0, 0, ',', '.') }}</small></td>
                                                    <td><small>{{ $row['coretax']['supplier_name'] ?? '' }}</small></td>
                                                    <td><small>{{ $row['coretax']['status_faktur'] ?? '' }}</small></td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted text-center">Tidak ada temuan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endif

        </div>
    </div>
@endsection
