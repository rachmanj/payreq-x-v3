@extends('templates.main')

@section('title_page')
    Accounting Manager Dashboard
@endsection

@section('breadcrumb_title')
    accounting / manager-dashboard
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('accounting.manager-dashboard.index') }}"
                        class="form-inline flex-wrap align-items-center">
                        <label class="mr-2 mb-2 font-weight-bold">Periode Realisasi:</label>
                        <select name="month" class="form-control form-control-sm mr-2 mb-2">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}" @selected($month == $m)>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                        <select name="year" class="form-control form-control-sm mr-2 mb-2">
                            @foreach (range(now()->year - 2, now()->year + 1) as $y)
                                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary mb-2">
                            <i class="fas fa-sync-alt"></i> Tampilkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($saldo_kas, 2) }}</h3>
                    <p>Saldo Kas</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($outstanding_advance_total, 2) }}</h3>
                    <p>Outstanding Advance <small>({{ $outstanding_advance_cnt }} dokumen)</small></p>
                </div>
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($kebutuhan_dana_total, 2) }}</h3>
                    <p>Kebutuhan Dana <small>({{ $kebutuhan_dana_cnt }} dokumen)</small></p>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($realisasi_bulan_total, 2) }}</h3>
                    <p>Realisasi Bulan Ini</p>
                </div>
                <div class="icon"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Section A — Dana Beredar (Funding)</h3>
                    <small class="text-muted ml-2">Dimensi: payreqs.project</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th width="10%">Project</th>
                                    <th width="20%">Outstanding Advance</th>
                                    <th width="25%">Aging Bucket</th>
                                    <th width="20%">Belum Paid</th>
                                    <th width="15%">Drill-down</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($funding_rows as $row)
                                    <tr>
                                        <td><strong>{{ $row['project'] }}</strong></td>
                                        <td>
                                            <small>{{ $row['outstanding_cnt'] }} dokumen</small><br>
                                            <small>{{ number_format($row['outstanding_total'], 2) }}</small>
                                        </td>
                                        <td>
                                            @forelse ($row['aging_summary'] as $bucket => $count)
                                                @php
                                                    $badge = match (true) {
                                                        $bucket === '0-30' => 'success',
                                                        in_array($bucket, ['31-60', '61-90']) => 'warning',
                                                        default => 'danger',
                                                    };
                                                @endphp
                                                <span class="badge badge-{{ $badge }} mr-1">{{ $count }}
                                                    {{ $bucket }}h</span>
                                            @empty
                                                <span class="text-muted">-</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <small>{{ $row['kebutuhan_cnt'] }} dokumen</small><br>
                                            <small>{{ number_format($row['kebutuhan_total'], 2) }}</small>
                                        </td>
                                        <td>
                                            @if ($row['outstanding_cnt'] > 0)
                                                <button type="button" class="btn btn-xs btn-outline-warning btn-drill"
                                                    data-type="advances" data-project="{{ $row['project'] }}">
                                                    <i class="fas fa-search"></i> Advance
                                                </button>
                                            @endif
                                            @if ($row['kebutuhan_cnt'] > 0)
                                                <button type="button" class="btn btn-xs btn-outline-danger btn-drill"
                                                    data-type="unpaid" data-project="{{ $row['project'] }}">
                                                    <i class="fas fa-search"></i> Unpaid
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Tidak ada data funding.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Section B — Realisasi Biaya (Expense)</h3>
                    <small class="text-muted ml-2">
                        Dimensi: realization_details.project |
                        Periode: {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}
                    </small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th width="10%">Project</th>
                                    <th width="20%">Realisasi Bulan Ini</th>
                                    <th width="15%">YTD {{ $year }}</th>
                                    <th width="30%">Top Akun (Bulan Ini)</th>
                                    <th width="10%">Drill-down</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expense_rows as $row)
                                    <tr>
                                        <td><strong>{{ $row['project'] }}</strong></td>
                                        <td>
                                            <small>{{ $row['bulan_cnt'] }} baris</small><br>
                                            <small>{{ number_format($row['bulan_total'], 2) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ number_format($row['ytd_total'], 2) }}</small>
                                        </td>
                                        <td>
                                            @forelse ($row['top_accounts'] as $account)
                                                <small>{{ $account->account_number }} —
                                                    {{ number_format($account->total, 2) }}</small><br>
                                            @empty
                                                <span class="text-muted">-</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if ($row['bulan_cnt'] > 0)
                                                <button type="button" class="btn btn-xs btn-outline-success btn-drill"
                                                    data-type="realizations" data-project="{{ $row['project'] }}">
                                                    <i class="fas fa-search"></i> Detail
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Tidak ada data realisasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card card-outline card-info">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h3 class="card-title">Section C — Biaya per Unit</h3>
                    <small class="text-muted">Monthly sum per tipe expense (12 bulan terakhir)</small>
                    <div style="width: 340px;">
                        <select id="unitSelect" class="form-control form-control-sm select2bs4" data-placeholder="Pilih Unit">
                            <option value=""></option>
                            @foreach ($units as $u)
                                <option value="{{ $u->unit_no }}">{{ $u->unit_no }}{{ $u->unit_model ? ' — '.$u->unit_model : '' }}{{ $u->unit_nopol ? ' ('.$u->unit_nopol.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div id="unitChartEmpty" class="text-center text-muted py-5">
                        <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                        Pilih unit untuk melihat biaya bulanan per tipe expense.
                    </div>
                    <div id="unitChartWrap" style="display: none;">
                        <canvas id="unitExpenseChart" style="height: 320px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="drilldown-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="drilldown-modal-title">Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="drilldown-table" class="table table-bordered table-striped table-sm w-100">
                        <thead id="drilldown-thead"></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            const month = @json($month);
            const year = @json($year);
            let drilldownTable = null;

            const drilldownConfig = {
                advances: {
                    title: 'Outstanding Advance',
                    url: project => @json(url('/accounting/manager-dashboard/project')) + '/' + encodeURIComponent(
                        project) + '/advances',
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            title: '#',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'nomor',
                            name: 'nomor',
                            title: 'Nomor'
                        },
                        {
                            data: 'requestor_name',
                            name: 'requestor_name',
                            title: 'Requestor'
                        },
                        {
                            data: 'amount',
                            name: 'amount',
                            title: 'Amount',
                            className: 'text-right'
                        },
                        {
                            data: 'aging_days',
                            name: 'aging_days',
                            title: 'Aging (hari)',
                            className: 'text-center'
                        },
                        {
                            data: 'first_outgoing',
                            name: 'first_outgoing',
                            title: 'Tanggal Cair'
                        },
                    ],
                },
                unpaid: {
                    title: 'Belum Paid',
                    url: project => @json(url('/accounting/manager-dashboard/project')) + '/' + encodeURIComponent(
                        project) + '/unpaid',
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            title: '#',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'nomor',
                            name: 'nomor',
                            title: 'Nomor'
                        },
                        {
                            data: 'requestor_name',
                            name: 'requestor_name',
                            title: 'Requestor'
                        },
                        {
                            data: 'amount',
                            name: 'amount',
                            title: 'Amount',
                            className: 'text-right'
                        },
                        {
                            data: 'status',
                            name: 'status',
                            title: 'Status'
                        },
                        {
                            data: 'due_date',
                            name: 'due_date',
                            title: 'Due Date'
                        },
                    ],
                },
                realizations: {
                    title: 'Realisasi by Akun',
                    url: project => @json(url('/accounting/manager-dashboard/project')) + '/' + encodeURIComponent(
                            project) + '/realizations' + '?month=' + month + '&year=' + year,
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            title: '#',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'account_info',
                            name: 'account_info',
                            title: 'Akun'
                        },
                        {
                            data: 'amount',
                            name: 'amount',
                            title: 'Amount',
                            className: 'text-right'
                        },
                        {
                            data: 'expense_date',
                            name: 'expense_date',
                            title: 'Expense Date'
                        },
                    ],
                },
            };

            $('.btn-drill').on('click', function() {
                const type = $(this).data('type');
                const project = $(this).data('project');
                const config = drilldownConfig[type];

                $('#drilldown-modal-title').text(config.title + ' — Project ' + project);
                $('#drilldown-thead').html('<tr>' + config.columns.map(c => '<th>' + c.title + '</th>')
                    .join('') + '</tr>');

                if (drilldownTable) {
                    drilldownTable.destroy();
                    $('#drilldown-table tbody').empty();
                }

                drilldownTable = $('#drilldown-table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: config.url(project),
                    columns: config.columns,
                    order: [
                        [1, 'asc']
                    ],
                    pageLength: 25,
                });

                $('#drilldown-modal').modal('show');
            });

            $('#drilldown-modal').on('hidden.bs.modal', function() {
                if (drilldownTable) {
                    drilldownTable.destroy();
                    drilldownTable = null;
                }
            });
        });
    </script>

    <script>
        // Section C — Biaya per Unit (stacked bar: monthly sum per expense type)
        const moneyFmt = (v) => {
            const n = Math.abs(v);
            if (n >= 1e9) return 'Rp ' + (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' M';
            if (n >= 1e6) return 'Rp ' + (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
            if (n >= 1e3) return 'Rp ' + (v / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' rb';
            return 'Rp ' + Math.round(v).toLocaleString('id-ID');
        };

        const unitSelect = document.getElementById('unitSelect');
        const unitChartWrap = document.getElementById('unitChartWrap');
        const unitChartEmpty = document.getElementById('unitChartEmpty');
        let unitChart = null;

        // Select2 bootstrap4
        $('#unitSelect').select2({
            theme: 'bootstrap4',
            placeholder: 'Pilih Unit',
            allowClear: true,
            width: '100%',
        });

        function loadUnitChart(unitNo) {
            fetch(@json(url('/accounting/manager-dashboard/unit')) + '/' + encodeURIComponent(unitNo) + '/expense')
                .then(r => r.json())
                .then(data => {
                    unitChartEmpty.style.display = 'none';
                    unitChartWrap.style.display = 'block';

                    if (unitChart) {
                        unitChart.destroy();
                    }

                    unitChart = new Chart(document.getElementById('unitExpenseChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: data.datasets.map(ds => ({
                                label: ds.label,
                                data: ds.data,
                                backgroundColor: ds.color,
                            })),
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { position: 'bottom' },
                            tooltips: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function (tooltipItem, d) {
                                        return d.datasets[tooltipItem.datasetIndex].label + ': ' + moneyFmt(tooltipItem.yLabel);
                                    },
                                },
                            },
                            scales: {
                                xAxes: [{ stacked: true }],
                                yAxes: [{
                                    stacked: true,
                                    beginAtZero: true,
                                    ticks: { callback: function (value) { return moneyFmt(value); } },
                                }],
                            },
                        },
                    });
                })
                .catch(() => {
                    unitChartWrap.style.display = 'none';
                    unitChartEmpty.style.display = 'block';
                });
        }

        // Section C — pilih unit: pakai jQuery .on('change') karena select2
        // hanya me-trigger event jQuery, bukan native DOM change
        $('#unitSelect').on('change', function() {
            const unitNo = $(this).val();
            if (!unitNo) {
                unitChartWrap.style.display = 'none';
                unitChartEmpty.style.display = 'block';
                return;
            }
            loadUnitChart(unitNo);
        });
    </script>
@endsection
