@extends('templates.main')

@section('title_page')
    PPN — Faktur belum diterima
@endsection

@section('breadcrumb_title')
    accounting / tax / ppn / belum-diterima
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @include('accounting.tax.ppn.partials.nav', ['masaPajak' => $masaPajak])

            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                    <form method="GET" class="form-inline">
                        <label class="mr-2">Masa pajak</label>
                        <select name="masa_pajak" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            @foreach ($masaOptions as $opt)
                                <option value="{{ $opt }}" @selected($opt === $masaPajak)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('accounting.tax.ppn.belum-diterima.export', ['masa_pajak' => $masaPajak]) }}" class="btn btn-sm btn-success">Ekspor Excel per supplier</a>
                </div>
            </div>

            @if (! $period || empty($period->snapshot_json))
                <div class="alert alert-info">
                    Belum ada snapshot rekonsiliasi untuk masa {{ $masaPajak }}.
                    Jalankan <strong>Rekonsiliasi</strong> dari dashboard PPN terlebih dahulu.
                </div>
            @elseif ($groups === [])
                <div class="alert alert-success mb-0">Tidak ada eksposur AP ber-PPN tanpa nomor faktur pajak untuk masa ini.</div>
            @else
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Eksposur — AP VatSum &gt; 0 dan U_MIS_FPNum kosong ({{ $masaPajak }})</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-right">Jumlah invoice</th>
                                    <th class="text-right">Total PPN</th>
                                    <th class="text-right">Umur max (hari)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $g)
                                    <tr>
                                        <td>
                                            <strong>{{ $g['card_name'] }}</strong><br>
                                            <small class="text-muted">{{ $g['card_code'] }}</small>
                                        </td>
                                        <td class="text-right">{{ $g['invoice_count'] }}</td>
                                        <td class="text-right">{{ number_format($g['total_vat'], 0, ',', '.') }}</td>
                                        <td class="text-right">{{ $g['max_age_days'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
