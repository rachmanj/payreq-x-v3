@extends('templates.main')

@section('title_page')
    Installment Dashboard
@endsection

@section('breadcrumb_title')
    accounting / installment / dashboard
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <x-loan-links page="dashboard" />
            </div>
        </div>

        @php
            $fmtCompact = function (float $amount): string {
                if ($amount >= 1_000_000_000) {
                    return number_format($amount / 1_000_000_000, 1, ',', '.') . ' M';
                }
                if ($amount >= 1_000_000) {
                    return number_format($amount / 1_000_000, 1, ',', '.') . ' jt';
                }
                if ($amount >= 1_000) {
                    return number_format($amount / 1_000, 0, ',', '.') . ' rb';
                }
                return number_format($amount, 0, ',', '.');
            };
        @endphp

        <div class="vj-stat-grid vj-stat-grid-4 mb-3">
            <div class="vj-stat vj-stat-warning">
                <div class="vj-stat-icon"><i class="fas fa-calendar-week"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Jatuh Tempo 7 Hari ({{ $dueWeek['count'] }} angsuran)</span>
                    <span class="vj-stat-value">{{ $fmtCompact($dueWeek['total']) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-danger">
                <div class="vj-stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Jatuh Tempo Hari Ini ({{ $dueToday['count'] }} angsuran)</span>
                    <span class="vj-stat-value">{{ $fmtCompact($dueToday['total']) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Bilyet Cair Tanpa OP</span>
                    <span class="vj-stat-value">{{ number_format($bilyetCairTanpaOp) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-list"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Kontrak Aktif (sisa angsuran)</span>
                    <span class="vj-stat-value">{{ $remainingPerContract->count() }}</span>
                </div>
            </div>
        </div>

        <div class="vj-note mb-3">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Ringkasan Operasional</strong>
                <div>Data diambil dari kontrak aktif dan jadwal angsuran yang belum lunas. Dana per rekening mencakup angsuran jatuh tempo dalam 7 hari ke depan.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0"><i class="fas fa-university"></i> Dana Perlu Disiapkan per Rekening (≤7 hari)</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Rekening Bank</th>
                                    <th class="text-right">Jumlah</th>
                                    <th class="text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fundsPerBank as $bank)
                                    <tr>
                                        <td>{{ $bank['account_label'] }}</td>
                                        <td class="text-right">{{ $bank['count'] }}</td>
                                        <td class="text-right">{{ $fmtCompact($bank['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Tidak ada angsuran jatuh tempo dalam 7 hari</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0"><i class="fas fa-file-contract"></i> Sisa Angsuran per Kontrak (Top 10)</h3>
                        <a href="{{ route('accounting.loans.index') }}" class="vj-btn vj-btn-primary">
                            <i class="fas fa-list"></i> Lihat Semua
                        </a>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Kontrak</th>
                                    <th class="text-right">Sisa</th>
                                    <th class="text-right">Nominal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($remainingPerContract as $loan)
                                    <tr>
                                        <td>
                                            <strong>{{ $loan->loan_code }}</strong><br>
                                            <small class="text-muted">{{ Str::limit($loan->description, 40) }}</small>
                                        </td>
                                        <td class="text-right">{{ $loan->unpaid_count }}</td>
                                        <td class="text-right">{{ $fmtCompact((float) ($loan->unpaid_total ?? 0)) }}</td>
                                        <td>
                                            <a href="{{ route('accounting.loans.show', $loan->id) }}" class="vj-action-item vj-action-item-xs vj-action-export" title="Lihat kontrak">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Semua kontrak sudah lunas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
