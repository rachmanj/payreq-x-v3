@extends('templates.main')

@section('title_page')
    PPN Masukan — Sinkronisasi SAP
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn / sync
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card mb-3">
                <div class="card-header">
                    <a href="{{ route('accounting.vat.index', ['page' => 'dashboard']) }}">VAT Dashboard</a> |
                    <a href="{{ route('accounting.vat.index', ['page' => 'purchase', 'status' => 'incomplete']) }}">Purchase Fakturs</a> |
                    <span class="font-weight-bold text-uppercase">Sinkronisasi PPN Masukan</span>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($showAlert)
                <div class="alert alert-danger">
                    <strong>Perhatian:</strong>
                    tarikan otomatis gagal atau belum sukses dalam 2 hari terakhir. Periksa riwayat di bawah atau tarik manual dari SAP.
                </div>
            @endif

            <div class="row">
                <div class="col-lg-6">
                    <div class="card card-outline {{ $showAlert ? 'card-danger' : 'card-success' }}">
                        <div class="card-header">
                            <h3 class="card-title">Status tarikan terakhir</h3>
                        </div>
                        <div class="card-body">
                            @if ($lastRun)
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Waktu mulai</dt>
                                    <dd class="col-sm-8">{{ $lastRun->started_at?->format('d-M-Y H:i') ?? '—' }}</dd>
                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">
                                        @if ($lastRun->status === 'success')
                                            <span class="badge badge-success">Sukses</span>
                                        @elseif ($lastRun->status === 'failed')
                                            <span class="badge badge-danger">Gagal</span>
                                        @else
                                            <span class="badge badge-secondary">Berjalan</span>
                                        @endif
                                    </dd>
                                    <dt class="col-sm-4">Baris SAP</dt>
                                    <dd class="col-sm-8">{{ number_format($lastRun->rows_fetched) }}</dd>
                                    <dt class="col-sm-4">Upsert</dt>
                                    <dd class="col-sm-8">{{ number_format($lastRun->rows_upserted) }}</dd>
                                    <dt class="col-sm-4">Faktur baru</dt>
                                    <dd class="col-sm-8">{{ number_format($lastRun->rows_faktur_created) }}</dd>
                                    @if ($lastRun->message)
                                        <dt class="col-sm-4">Pesan</dt>
                                        <dd class="col-sm-8"><small>{{ $lastRun->message }}</small></dd>
                                    @endif
                                </dl>
                            @else
                                <p class="text-muted mb-0">Belum ada tarikan tercatat.</p>
                            @endif

                            @if ($lastSuccess && ($lastRun === null || $lastSuccess->id !== $lastRun->id))
                                <hr>
                                <p class="mb-0 text-muted">
                                    <small>Terakhir sukses: {{ $lastSuccess->finished_at?->format('d-M-Y H:i') }}</small>
                                </p>
                            @endif
                        </div>
                        @can('manage_tax_monitoring')
                            <div class="card-footer">
                                <form method="POST" action="{{ route('accounting.tax.ppn.sync.run') }}"
                                    onsubmit="return confirm('Tarik data PPN Masukan dari SAP (lookback 60 hari)?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        Tarik dari SAP sekarang
                                    </button>
                                </form>
                            </div>
                        @endcan
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Informasi</h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 pl-3">
                                <li>Jadwal otomatis: setiap hari 05:00 WITA (21:00 UTC), lookback 60 hari.</li>
                                <li>Sumber: akun GL <code>11603001</code> (debit) via query SAP <code>AO_PPNIN1</code>.</li>
                                <li>Unggahan Excel manual tetap tersedia di Daily GL; baris ditandai sumber berbeda di daftar faktur.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat 20 tarikan terakhir</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Mulai</th>
                                <th>Selesai</th>
                                <th>Status</th>
                                <th class="text-right">SAP</th>
                                <th class="text-right">Upsert</th>
                                <th class="text-right">Faktur</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $run)
                                <tr class="{{ $run->status === 'failed' ? 'table-danger' : '' }}">
                                    <td>{{ $run->started_at?->format('d-M-Y H:i') }}</td>
                                    <td>{{ $run->finished_at?->format('d-M-Y H:i') ?? '—' }}</td>
                                    <td>
                                        @if ($run->status === 'success')
                                            <span class="badge badge-success">Sukses</span>
                                        @elseif ($run->status === 'failed')
                                            <span class="badge badge-danger">Gagal</span>
                                        @else
                                            <span class="badge badge-secondary">Running</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($run->rows_fetched) }}</td>
                                    <td class="text-right">{{ number_format($run->rows_upserted) }}</td>
                                    <td class="text-right">{{ number_format($run->rows_faktur_created) }}</td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($run->message ?? '', 120) }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada riwayat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
