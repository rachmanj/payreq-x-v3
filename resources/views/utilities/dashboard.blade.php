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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Fluktuasi Bulanan per Kategori (12 Bulan Terakhir)</h3>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" style="height: 320px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Fluktuasi Bulanan per ID Pelanggan</h3>
                    <div class="float-right" style="width: 320px;">
                        <select id="customerSelect" class="form-control form-control-sm">
                            <option value="">-- Pilih ID Pelanggan --</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c['id'] }}">{{ $c['idpel'] }} — {{ $c['nama'] }}
                                    ({{ $c['jenis_label'] }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="customerChart" style="height: 320px;"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script>
        const monthLabels = @json($chart_labels);
        const categoryData = @json($chart_category);
        const customerData = @json($chart_customer);

        const moneyFmt = (v) => new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(v);

        // Chart A: per kategori
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(catCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    { label: 'PLN', data: categoryData.pln, borderColor: '#f39c12', backgroundColor: 'rgba(243,156,18,0.1)', tension: 0.3, fill: false },
                    { label: 'PDAM', data: categoryData.pdam, borderColor: '#00a65a', backgroundColor: 'rgba(0,166,90,0.1)', tension: 0.3, fill: false },
                    { label: 'TELKOM', data: categoryData.telkom, borderColor: '#3c8dbc', backgroundColor: 'rgba(60,141,188,0.1)', tension: 0.3, fill: false },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { ticks: { callback: (v) => moneyFmt(v) } },
                },
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + moneyFmt(ctx.parsed.y) } },
                },
            },
        });

        // Chart B: per ID pelanggan (dropdown)
        const custCtx = document.getElementById('customerChart').getContext('2d');
        const customerChart = new Chart(custCtx, {
            type: 'line',
            data: { labels: monthLabels, datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { ticks: { callback: (v) => moneyFmt(v) } },
                },
                plugins: {
                    tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + moneyFmt(ctx.parsed.y) } },
                },
            },
        });

        document.getElementById('customerSelect').addEventListener('change', function() {
            const id = parseInt(this.value, 10);
            const found = customerData.find((c) => c.id === id);
            if (!found) {
                customerChart.data.datasets = [];
                customerChart.update();
                return;
            }
            customerChart.data.datasets = [{
                label: found.idpel + ' — ' + found.nama,
                data: found.data,
                borderColor: '#3c8dbc',
                backgroundColor: 'rgba(60,141,188,0.1)',
                tension: 0.3,
                fill: false,
            }];
            customerChart.update();
        });
    </script>
@endsection
