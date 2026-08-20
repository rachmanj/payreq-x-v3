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
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-bolt mr-2"></i> PLN
                        </h3>
                        <span class="text-muted font-weight-bold" id="plnLatest">—</span>
                    </div>
                    <div class="card-body pt-2 pb-2">
                        <canvas id="plnChart" style="height: 90px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-tint mr-2"></i> PDAM
                        </h3>
                        <span class="text-muted font-weight-bold" id="pdamLatest">—</span>
                    </div>
                    <div class="card-body pt-2 pb-2">
                        <canvas id="pdamChart" style="height: 90px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-phone mr-2"></i> TELKOM
                        </h3>
                        <span class="text-muted font-weight-bold" id="telkomLatest">—</span>
                    </div>
                    <div class="card-body pt-2 pb-2">
                        <canvas id="telkomChart" style="height: 90px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-user-tag mr-2"></i> Per ID Pelanggan
                        </h3>
                        <div class="d-flex align-items-center">
                            <span class="text-muted font-weight-bold mr-3" id="custLatest">—</span>
                            <div style="width: 280px;">
                                <select id="customerSelect" class="form-control form-control-sm">
                                    <option value="">-- Pilih ID Pelanggan --</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c['id'] }}">{{ $c['idpel'] }} — {{ $c['nama'] }}
                                            ({{ $c['jenis_label'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-2 pb-2">
                        <canvas id="customerChart" style="height: 120px;"></canvas>
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

        // Format kompak: Rp 1,4 jt / Rp 850 rb / Rp 500 (kurangi nol)
        const moneyFmt = (v) => {
            const n = Math.abs(v);
            if (n >= 1e9) return 'Rp ' + (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' M';
            if (n >= 1e6) return 'Rp ' + (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
            if (n >= 1e3) return 'Rp ' + (v / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' rb';
            return 'Rp ' + Math.round(v).toLocaleString('id-ID');
        };

        // Chart per kategori (PLN / PDAM / TELKOM) — sparkline minimalis
        function makeCategoryChart(canvasId, badgeId, label, data, borderColor, fillColor) {
            // nilai bulan terakhir (non-zero)
            let latest = 0;
            for (let i = data.length - 1; i >= 0; i--) {
                if (data[i]) { latest = data[i]; break; }
            }
            const badge = document.getElementById(badgeId);
            if (badge) badge.textContent = moneyFmt(latest);

            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: borderColor,
                        backgroundColor: fillColor,
                        borderWidth: 1.5,
                        lineTension: 0.35,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: borderColor,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function (tooltipItem, data) {
                                return data.datasets[tooltipItem.datasetIndex].label + ': ' + moneyFmt(tooltipItem.yLabel);
                            },
                        },
                    },
                    scales: {
                        xAxes: [{ display: false }],
                        yAxes: [{ display: false }],
                    },
                },
            });
        }

        makeCategoryChart('plnChart', 'plnLatest', 'PLN', categoryData.pln, '#f39c12', 'rgba(243,156,18,0.15)');
        makeCategoryChart('pdamChart', 'pdamLatest', 'PDAM', categoryData.pdam, '#00a65a', 'rgba(0,166,90,0.15)');
        makeCategoryChart('telkomChart', 'telkomLatest', 'TELKOM', categoryData.telkom, '#3c8dbc', 'rgba(60,141,188,0.15)');

        // Chart B: per ID pelanggan (dropdown)
        const custCtx = document.getElementById('customerChart').getContext('2d');
        const customerChart = new Chart(custCtx, {
            type: 'line',
            data: { labels: monthLabels, datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': ' + moneyFmt(tooltipItem.yLabel);
                        },
                    },
                },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ display: false }],
                },
            },
        });

        document.getElementById('customerSelect').addEventListener('change', function() {
            const id = parseInt(this.value, 10);
            const found = customerData.find((c) => c.id === id);
            const badge = document.getElementById('custLatest');
            if (!found) {
                customerChart.data.datasets = [];
                customerChart.update();
                if (badge) badge.textContent = '—';
                return;
            }
            // nilai bulan terakhir (non-zero)
            let latest = 0;
            for (let i = found.data.length - 1; i >= 0; i--) {
                if (found.data[i]) { latest = found.data[i]; break; }
            }
            if (badge) badge.textContent = moneyFmt(latest);
            customerChart.data.datasets = [{
                label: found.idpel + ' — ' + found.nama,
                data: found.data,
                borderColor: '#3c8dbc',
                backgroundColor: 'rgba(60,141,188,0.12)',
                lineTension: 0.3,
                fill: true,
                pointRadius: 0,
                pointHoverRadius: 4,
                pointHoverBackgroundColor: '#3c8dbc',
            }];
            customerChart.update();
        });
    </script>
@endsection
