@extends('templates.main')

@section('title_page')
    Utilities Dashboard
@endsection

@section('breadcrumb_title')
    utilities / dashboard
@endsection

@section('content')
    <div class="vj-show">
        <div class="vj-stat-grid vj-stat-grid-4 mb-3">
            <div class="vj-stat vj-stat-info">
                <div class="vj-stat-icon"><i class="fas fa-bolt"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Total Tagihan Bulan Ini ({{ $periode_bulan_ini }})</span>
                    <span class="vj-stat-value">{{ number_format($total_bulan_ini, 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-danger">
                <div class="vj-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Telat ({{ $telat_count }} tagihan)</span>
                    <span class="vj-stat-value">{{ number_format($telat_total, 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-neutral">
                <div class="vj-stat-icon"><i class="fas fa-clock"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Belum Bayar ({{ $belum_count }} tagihan)</span>
                    <span class="vj-stat-value">{{ number_format($belum_total, 2) }}</span>
                </div>
            </div>
            <div class="vj-stat vj-stat-success">
                <div class="vj-stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="vj-stat-body">
                    <span class="vj-stat-label">Lunas Bulan Ini ({{ $lunas_count }} tagihan)</span>
                    <span class="vj-stat-value">{{ number_format($lunas_total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-list-alt"></i> Ringkasan per Jenis Utilitas — {{ $periode_bulan_ini }}
                        </h3>
                        <a href="{{ route('utilities.bills.index', ['periode' => $periode_bulan_ini]) }}"
                            class="vj-btn vj-btn-primary">
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
            <div class="col-md-4">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-bolt"></i> Fluktuasi PLN
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="plnChart" style="height: 260px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-tint"></i> Fluktuasi PDAM
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="pdamChart" style="height: 260px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-phone"></i> Fluktuasi TELKOM
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="telkomChart" style="height: 260px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-user-tag"></i> Fluktuasi Bulanan per ID Pelanggan
                        </h3>
                        <div style="width: 320px;">
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
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
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

        // Chart per kategori (PLN / PDAM / TELKOM — masing-masing chart terpisah)
        function makeCategoryChart(canvasId, label, data, borderColor) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{ label: label, data: data, borderColor: borderColor, tension: 0.3, fill: false }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { ticks: { callback: (v) => moneyFmt(v) } },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + moneyFmt(ctx.parsed.y) } },
                    },
                },
            });
        }

        makeCategoryChart('plnChart', 'PLN', categoryData.pln, '#f39c12');
        makeCategoryChart('pdamChart', 'PDAM', categoryData.pdam, '#00a65a');
        makeCategoryChart('telkomChart', 'TELKOM', categoryData.telkom, '#3c8dbc');

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
