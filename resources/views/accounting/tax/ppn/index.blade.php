@extends('templates.main')

@section('title_page')
    Monitoring PPN
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            @include('accounting.tax.ppn.partials.nav', ['masaPajak' => $masaPajak])

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('accounting.tax.ppn.index') }}" class="form-inline">
                        <label class="mr-2" for="masa_pajak">Masa pajak</label>
                        <select name="masa_pajak" id="masa_pajak" class="form-control form-control-sm mr-2">
                            @foreach ($masaOptions as $opt)
                                <option value="{{ $opt }}" @selected($opt === $masaPajak)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-secondary">Tampilkan</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ number_format((float) ($period->pk_total ?? 0), 0, ',', '.') }}</h3>
                            <p>PPN Keluaran (PK) — SAP snapshot</p>
                            <small>Aplikasi: {{ number_format($appPk, 0, ',', '.') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ number_format((float) ($period->pm_total ?? 0), 0, ',', '.') }}</h3>
                            <p>PPN Masukan (PM) — SAP snapshot</p>
                            <small>Aplikasi: {{ number_format($appPm, 0, ',', '.') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $period->kb_lb !== null ? number_format((float) $period->kb_lb, 0, ',', '.') : '—' }}</h3>
                            <p>Kurang bayar / Lebih bayar (PK − PM SAP)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card card-outline card-secondary">
                        <div class="card-header"><h3 class="card-title">Selisih SAP ↔ Aplikasi</h3></div>
                        <div class="card-body">
                            <p class="mb-0 display-6">
                                {{ $period->diff_sap_app !== null ? number_format((float) $period->diff_sap_app, 0, ',', '.') : '—' }}
                            </p>
                            <small class="text-muted">Jalankan rekonsiliasi untuk memperbarui.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-secondary">
                        <div class="card-header"><h3 class="card-title">Selisih Coretax ↔ Aplikasi (PM)</h3></div>
                        <div class="card-body">
                            @if ($period->diff_coretax_app !== null)
                                <p class="mb-0 display-6">{{ number_format((float) $period->diff_coretax_app, 0, ',', '.') }}</p>
                            @else
                                <p class="mb-0 text-muted">Belum tersedia — belum ada data prepopulasi Coretax untuk masa ini.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-secondary">
                        <div class="card-header"><h3 class="card-title">Selisih PK ↔ PM (aplikasi)</h3></div>
                        <div class="card-body">
                            <p class="mb-0 display-6">
                                {{ $period->diff_pk_pm !== null ? number_format((float) $period->diff_pk_pm, 0, ',', '.') : number_format($appPk - $appPm, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="card-title mb-0">Masa pajak {{ $masaPajak }}</h3>
                    <div>
                        @php
                            $statusLabels = [
                                'open' => 'Terbuka',
                                'prepared' => 'Disiapkan',
                                'approved' => 'Disetujui',
                                'filed' => 'Dilaporkan',
                                'locked' => 'Terkunci',
                            ];
                        @endphp
                        <span class="badge badge-primary">{{ $statusLabels[$period->status] ?? $period->status }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="btn-group flex-wrap mb-2" role="group">
                        @can('manage_tax_monitoring')
                            @if ($period->status !== 'locked')
                                <form method="POST" action="{{ route('accounting.tax.ppn.reconcile') }}" class="d-inline mr-1 mb-1"
                                    onsubmit="return confirm('Jalankan rekonsiliasi untuk {{ $masaPajak }}?');">
                                    @csrf
                                    <input type="hidden" name="masa_pajak" value="{{ $masaPajak }}">
                                    <button type="submit" class="btn btn-primary btn-sm">Rekonsiliasi</button>
                                </form>
                            @endif
                            @if ($period->status === 'open')
                                <form method="POST" action="{{ route('accounting.tax.ppn.periods.prepare', $period) }}" class="d-inline mr-1 mb-1">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-info btn-sm">Tandai disiapkan</button>
                                </form>
                            @endif
                        @endcan
                        @can('approve_tax_period')
                            @if ($period->status === 'prepared')
                                <form method="POST" action="{{ route('accounting.tax.ppn.periods.approve', $period) }}" class="d-inline mr-1 mb-1"
                                    onsubmit="return confirm('Setujui masa pajak {{ $masaPajak }}?');">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">Setujui</button>
                                </form>
                            @endif
                            @if (in_array($period->status, ['approved', 'filed'], true))
                                <form method="POST" action="{{ route('accounting.tax.ppn.periods.close', $period) }}" class="d-inline mr-1 mb-1"
                                    onsubmit="return confirm('Lanjutkan penutupan masa pajak?');">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        {{ $period->status === 'approved' ? 'Tandai sudah dilaporkan (SPT)' : 'Tutup / kunci masa' }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                        <a href="{{ route('accounting.tax.ppn.export', $masaPajak) }}" class="btn btn-outline-secondary btn-sm mr-1 mb-1">Ekspor Excel</a>
                        <a href="{{ route('accounting.tax.ppn.cetak', $masaPajak) }}" target="_blank" class="btn btn-outline-secondary btn-sm mb-1">Cetak ringkasan</a>
                    </div>

                    @can('approve_tax_period')
                        @if ($period->status === 'locked')
                            <hr>
                            <form method="POST" action="{{ route('accounting.tax.ppn.periods.reopen', $period) }}" class="form-inline flex-wrap">
                                @csrf
                                <label class="mr-2">Buka kunci (wajib alasan):</label>
                                <input type="text" name="reason" class="form-control form-control-sm mr-2 mb-1" required minlength="5" maxlength="2000" placeholder="Alasan buka kunci">
                                <button type="submit" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Buka kunci masa pajak?');">Buka kunci</button>
                            </form>
                            @if ($period->audit_log)
                                <ul class="small text-muted mt-2 mb-0">
                                    @foreach ($period->audit_log as $entry)
                                        <li>{{ $entry['at'] ?? '' }} — {{ $entry['user_name'] ?? '' }}: {{ $entry['reason'] ?? '' }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        @endif
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Daftar masa pajak (PPN)</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Masa</th>
                                <th>Status</th>
                                <th>PK</th>
                                <th>PM</th>
                                <th>KB/LB</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periods as $p)
                                <tr>
                                    <td>{{ $p->masa_pajak }}</td>
                                    <td>{{ $statusLabels[$p->status] ?? $p->status }}</td>
                                    <td class="text-right">{{ $p->pk_total !== null ? number_format((float) $p->pk_total, 0, ',', '.') : '—' }}</td>
                                    <td class="text-right">{{ $p->pm_total !== null ? number_format((float) $p->pm_total, 0, ',', '.') : '—' }}</td>
                                    <td class="text-right">{{ $p->kb_lb !== null ? number_format((float) $p->kb_lb, 0, ',', '.') : '—' }}</td>
                                    <td><a href="{{ route('accounting.tax.ppn.index', ['masa_pajak' => $p->masa_pajak]) }}">Buka</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center">Belum ada masa pajak tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
