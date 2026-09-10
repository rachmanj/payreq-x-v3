@extends('templates.main')

@section('title_page')
    Preview Jurnal VJ
@endsection

@section('breadcrumb_title')
    verifications / journal / preview
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection

@section('content')
    <div class="vj-show">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h3 class="card-title mb-0">
                    <i class="fas fa-eye"></i> Preview Jurnal — {{ $vj->nomor }}
                </h3>
                <div class="vj-inline-actions">
                    @if (empty($vj->sap_journal_no))
                        <a href="{{ route('verifications.journal.set_activities', $vj->id) }}" class="vj-btn vj-btn-warning">
                            <i class="fas fa-tags"></i> Atur Kegiatan
                        </a>
                    @endif
                    <a href="{{ route('verifications.journal.show', $vj->id) }}" class="vj-btn vj-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke VJ
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <span class="text-muted small">Total Debit</span>
                        <div class="font-weight-bold">Rp {{ number_format($totalDebit, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small">Total Credit</span>
                        <div class="font-weight-bold">Rp {{ number_format($totalCredit, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small">Balance</span>
                        <div>
                            @if ($isBalanced)
                                <span class="badge badge-success">Balance</span>
                            @else
                                <span class="badge badge-danger">Tidak Balance</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>DC</th>
                                <th>Akun</th>
                                <th>Cost Center</th>
                                <th>Project</th>
                                <th class="text-right">Amount</th>
                                <th>LineMemo</th>
                                <th>Realisasi</th>
                                <th>Reklasifikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $index => $line)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ strtoupper($line['debit_credit']) }}</td>
                                    <td>{{ $line['account_code'] }}</td>
                                    <td>{{ $line['cost_center'] }}</td>
                                    <td>{{ $line['project'] }}</td>
                                    <td class="text-right">{{ number_format($line['amount'], 2) }}</td>
                                    <td>{{ $line['description'] }}</td>
                                    <td><small>{{ $line['realization_no'] }}</small></td>
                                    <td>
                                        @if (! empty($line['is_reclassified']))
                                            <span class="badge badge-info">Direklasifikasi</span>
                                        @elseif (! empty($line['reclassified_reason']))
                                            <span class="badge badge-warning" title="{{ $line['reclassified_reason'] }}">Pengecualian</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
