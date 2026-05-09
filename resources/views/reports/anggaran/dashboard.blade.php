@extends('templates.main')

@section('title_page')
    RAB Dashboard
@endsection

@section('breadcrumb_title')
    reports / anggaran / dashboard
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Anggaran dashboard</h3>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right mx-1"><i class="fas fa-arrow-left"></i> Reports</a>
                    <a href="{{ route('reports.anggaran.index') }}" class="btn btn-xs btn-secondary float-right mx-1">RAB list</a>
                    <a href="{{ route('reports.anggaran.consolidated') }}" class="btn btn-xs btn-info float-right mx-1">Consolidated</a>
                    @can('recalculate_release')
                        <a href="{{ route('reports.anggaran.fund-pool.index') }}" class="btn btn-xs btn-warning float-right mx-1">Fund pool</a>
                    @endcan
                </div>
                <div class="card-body">
                    <form method="get" action="{{ route('reports.anggaran.dashboard') }}" class="form-inline mb-3">
                        <label class="mr-2">Project</label>
                        <select name="project" class="form-control form-control-sm mr-2">
                            <option value="">All</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->code }}" @selected(($filters['project'] ?? '') === $p->code)>{{ $p->code }}</option>
                            @endforeach
                        </select>
                        <label class="mr-2">Type</label>
                        <select name="type" class="form-control form-control-sm mr-2">
                            <option value="">All</option>
                            <option value="periode" @selected(($filters['type'] ?? '') === 'periode')>Periode</option>
                            <option value="event" @selected(($filters['type'] ?? '') === 'event')>Event</option>
                            <option value="buc" @selected(($filters['type'] ?? '') === 'buc')>BUC</option>
                        </select>
                        <label class="mr-2">Fund</label>
                        <select name="fund_status" class="form-control form-control-sm mr-2">
                            <option value="">All</option>
                            <option value="pending" @selected(($filters['fund_status'] ?? '') === 'pending')>Pending</option>
                            <option value="pooled" @selected(($filters['fund_status'] ?? '') === 'pooled')>Pooled</option>
                            <option value="released" @selected(($filters['fund_status'] ?? '') === 'released')>Released</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    </form>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Visible active</span>
                                    <span class="info-box-number">{{ number_format($stats['count_visible']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Approved lines</span>
                                    <span class="info-box-number">{{ number_format($stats['count_approved']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="fas fa-exclamation"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Near threshold</span>
                                    <span class="info-box-number">{{ number_format($stats['count_near_threshold']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Exceeded</span>
                                    <span class="info-box-number">{{ number_format($stats['count_exceeded']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>Rp {{ number_format($stats['sum_budget_approved'], 0) }}</h3>
                                    <p>Total approved budget</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3>Rp {{ number_format($stats['sum_balance_approved'], 0) }}</h3>
                                    <p>Release to date (stored)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>Rp {{ number_format($stats['sum_remaining_approved'], 0) }}</h3>
                                    <p>Remaining</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <p class="mb-0"><strong>Avg utilization (approved):</strong> {{ number_format($stats['avg_utilization'], 2) }}%</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-0"><strong>Pending fund pool:</strong> {{ number_format($stats['count_pending_fund_pool']) }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-primary">
                                <div class="card-header"><h3 class="card-title">By type</h3></div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Type</th><th class="text-right">Count</th><th class="text-right">Budget</th><th class="text-right">Released</th><th class="text-right">%</th></tr></thead>
                                        <tbody>
                                            @foreach ($stats['by_type'] as $row)
                                                <tr>
                                                    <td>{{ $row['type'] }}</td>
                                                    <td class="text-right">{{ $row['count'] }}</td>
                                                    <td class="text-right">{{ number_format($row['total_amount'], 2) }}</td>
                                                    <td class="text-right">{{ number_format($row['total_balance'], 2) }}</td>
                                                    <td class="text-right">{{ number_format($row['utilization'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-secondary">
                                <div class="card-header"><h3 class="card-title">By project (RAB project)</h3></div>
                                <div class="card-body p-0" style="max-height:280px;overflow:auto;">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Project</th><th class="text-right">Count</th><th class="text-right">Budget</th><th class="text-right">Released</th><th class="text-right">%</th></tr></thead>
                                        <tbody>
                                            @foreach ($stats['by_project'] as $row)
                                                <tr>
                                                    <td>{{ $row['project'] }}</td>
                                                    <td class="text-right">{{ $row['count'] }}</td>
                                                    <td class="text-right">{{ number_format($row['total_amount'], 2) }}</td>
                                                    <td class="text-right">{{ number_format($row['total_balance'], 2) }}</td>
                                                    <td class="text-right">{{ number_format($row['utilization'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-warning collapsed-card mb-3" id="dept-panel-card">
                        <div class="card-header">
                            <h3 class="card-title">By department (select project filter)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-2">
                                <select id="dept-breakdown-project" class="form-control form-control-sm" style="max-width:200px;display:inline-block;">
                                    <option value="">— project —</option>
                                    @foreach ($projects as $p)
                                        <option value="{{ $p->code }}" @selected(($filters['project'] ?? '') === $p->code)>{{ $p->code }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-primary" id="btn-load-dept">Load</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" id="tbl-dept-breakdown">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th class="text-right">Budgets</th>
                                            <th class="text-right">Budget</th>
                                            <th class="text-right">Released</th>
                                            <th class="text-right">Remaining</th>
                                            <th class="text-right">Avg %</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-warning">
                                <div class="card-header"><h3 class="card-title">Expiring periodic (30 days)</h3></div>
                                <div class="card-body p-0" style="max-height:220px;overflow:auto;">
                                    <ul class="list-group list-group-flush small mb-0">
                                        @forelse ($stats['expiring_soon'] as $a)
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>{{ $a->nomor }} — {{ $a->description }}</span>
                                                <span class="text-muted">{{ $a->end_date }}</span>
                                            </li>
                                        @empty
                                            <li class="list-group-item">No rows.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-danger">
                                <div class="card-header"><h3 class="card-title">Recently exceeded</h3></div>
                                <div class="card-body p-0" style="max-height:220px;overflow:auto;">
                                    <ul class="list-group list-group-flush small mb-0">
                                        @forelse ($stats['exceeded_budgets'] as $a)
                                            <li class="list-group-item d-flex justify-content-between">
                                                <a href="{{ route('reports.anggaran.show', $a->id) }}">{{ $a->nomor }}</a>
                                                <span>{{ number_format((float) $a->persen, 2) }}%</span>
                                            </li>
                                        @empty
                                            <li class="list-group-item">None.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Release to date (approved, active)</h3>
                        </div>
                        <div class="card-body">
                            <form id="release-filters" class="form-inline mb-2">
                                <input type="hidden" name="filter_project" id="rf_project" value="{{ $filters['project'] ?? '' }}">
                                <label class="mr-1">Type</label>
                                <select id="rf_type" class="form-control form-control-sm mr-2">
                                    <option value="">All</option>
                                    <option value="periode">Periode</option>
                                    <option value="event">Event</option>
                                    <option value="buc">BUC</option>
                                </select>
                                <label class="mr-1">Fund</label>
                                <select id="rf_fund" class="form-control form-control-sm mr-2">
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="pooled">Pooled</option>
                                    <option value="released">Released</option>
                                </select>
                                <label class="mr-1">Dept id</label>
                                <input type="number" id="rf_dept" class="form-control form-control-sm mr-2" style="width:90px" placeholder="id">
                                <label class="mr-1">From</label>
                                <input type="date" id="rf_from" class="form-control form-control-sm mr-2">
                                <label class="mr-1">To</label>
                                <input type="date" id="rf_to" class="form-control form-control-sm mr-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="rf_apply">Apply</button>
                            </form>
                            <table id="release-table" class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nomor</th>
                                        <th>Description</th>
                                        <th>Project</th>
                                        <th>Type</th>
                                        <th class="text-right">Budget</th>
                                        <th class="text-right">Released</th>
                                        <th class="text-right">Remaining</th>
                                        <th>Utilization</th>
                                        <th>Fund</th>
                                        <th class="text-right">Payreqs</th>
                                        <th>Dept</th>
                                        <th></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        $(function () {
            const deptUrl = @json(route('reports.anggaran.dashboard.by-department'));
            const releaseUrl = @json(route('reports.anggaran.dashboard.release-data'));

            $('#btn-load-dept').on('click', function () {
                const project = $('#dept-breakdown-project').val();
                const $tb = $('#tbl-dept-breakdown tbody');
                $tb.empty();
                if (!project) return;
                $.getJSON(deptUrl, { project: project }, function (res) {
                    (res.data || []).forEach(function (row) {
                        $tb.append('<tr>' +
                            '<td>' + $('<div>').text(row.department_name).html() + '</td>' +
                            '<td class="text-right">' + row.count + '</td>' +
                            '<td class="text-right">' + Number(row.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>' +
                            '<td class="text-right">' + Number(row.total_balance).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>' +
                            '<td class="text-right">' + Number(row.remaining).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>' +
                            '<td class="text-right">' + Number(row.avg_utilization).toFixed(2) + '</td>' +
                            '<td><a href="#" class="btn btn-xs btn-outline-primary rf-dept" data-id="' + row.department_id + '">Filter table</a></td>' +
                            '</tr>');
                    });
                });
            });

            $(document).on('click', 'a.rf-dept', function (e) {
                e.preventDefault();
                $('#rf_dept').val($(this).data('id'));
                $('#rf_project').val($('#dept-breakdown-project').val());
                releaseTable.ajax.reload();
            });

            const releaseTable = $('#release-table').DataTable({
                processing: true,
                serverSide: true,
                order: [],
                ajax: {
                    url: releaseUrl,
                    data: function (d) {
                        d.filter_project = $('#rf_project').val();
                        d.filter_type = $('#rf_type').val();
                        d.filter_fund_status = $('#rf_fund').val();
                        d.filter_department_id = $('#rf_dept').val();
                        d.filter_date_from = $('#rf_from').val();
                        d.filter_date_to = $('#rf_to').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'nomor', orderable: false, searchable: true},
                    {data: 'description', name: 'description'},
                    {data: 'rab_project', name: 'rab_project'},
                    {data: 'type', name: 'type'},
                    {data: 'budget_amount', orderable: false, searchable: false},
                    {data: 'released', orderable: false, searchable: false},
                    {data: 'remaining', orderable: false, searchable: false},
                    {data: 'utilization', orderable: false, searchable: false},
                    {data: 'fund_status_label', orderable: false, searchable: false},
                    {data: 'payreqs_count', name: 'payreqs_count'},
                    {data: 'department', orderable: false, searchable: false},
                    {data: 'actions', orderable: false, searchable: false},
                ],
            });

            $('#rf_apply').on('click', function () {
                releaseTable.ajax.reload();
            });
        });
    </script>
@endsection
