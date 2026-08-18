@extends('templates.main')

@section('title_page')
    Utilities Dashboard
@endsection

@section('breadcrumb_title')
    utilities / dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($total_bulan_ini, 2) }}</h3>
                    <p>Total Tagihan Bulan Ini <small>({{ $periode_bulan_ini }})</small></p>
                </div>
                <div class="icon"><i class="fas fa-bolt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($telat_total, 2) }}</h3>
                    <p>Telat <small>({{ $telat_count }} tagihan)</small></p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format($belum_total, 2) }}</h3>
                    <p>Belum Bayar <small>({{ $belum_count }} tagihan)</small></p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($lunas_total, 2) }}</h3>
                    <p>Lunas Bulan Ini <small>({{ $lunas_count }} tagihan)</small></p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan per Jenis Utilitas — {{ $periode_bulan_ini }}</h3>
                    <a href="{{ route('utilities.bills.index', ['periode' => $periode_bulan_ini]) }}"
                        class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-list"></i> Lihat Tagihan
                    </a>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Jenis Utilitas</th>
                                <th class="text-right">Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($breakdown as $item)
                                <tr>
                                    <td>{{ $item['label'] }}</td>
                                    <td class="text-right">{{ number_format($item['total'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td>Total</td>
                                <td class="text-right">{{ number_format($total_bulan_ini, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
