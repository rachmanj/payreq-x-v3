@extends('templates.main')

@section('title_page')
    RAB Consolidated
@endsection

@section('breadcrumb_title')
    reports / anggaran / consolidated
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Consolidated RAB</h3>
                    <a href="{{ route('reports.anggaran.dashboard') }}" class="btn btn-xs btn-info float-right ml-1">Dashboard</a>
                    <a href="{{ route('reports.anggaran.index') }}" class="btn btn-xs btn-secondary float-right ml-1">RAB list</a>
                </div>
                <div class="card-body">
                    <form method="get" class="form-inline mb-3">
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
                        <button class="btn btn-sm btn-primary" type="submit">Apply</button>
                    </form>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary"><i class="fas fa-coins"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total budget</span>
                                    <span class="info-box-number">Rp {{ number_format($totals['budget'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-secondary"><i class="fas fa-arrow-down"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Released (stored)</span>
                                    <span class="info-box-number">Rp {{ number_format($totals['released'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="fas fa-wallet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Remaining</span>
                                    <span class="info-box-number">Rp {{ number_format($totals['remaining'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (!empty($filters['project']) && count($byDepartment))
                        <div class="card card-outline card-primary mb-3">
                            <div class="card-header"><h3 class="card-title">By department — {{ $filters['project'] }}</h3></div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th class="text-right">Budgets</th>
                                            <th class="text-right">Budget</th>
                                            <th class="text-right">Released</th>
                                            <th class="text-right">Remaining</th>
                                            <th class="text-right">Avg %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($byDepartment as $row)
                                            <tr>
                                                <td>{{ $row['department_name'] }}</td>
                                                <td class="text-right">{{ $row['count'] }}</td>
                                                <td class="text-right">{{ number_format($row['total_amount'], 2) }}</td>
                                                <td class="text-right">{{ number_format($row['total_balance'], 2) }}</td>
                                                <td class="text-right">{{ number_format($row['remaining'], 2) }}</td>
                                                <td class="text-right">{{ number_format($row['avg_utilization'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Project</th>
                                    <th>Type</th>
                                    <th>Dept</th>
                                    <th class="text-right">Budget</th>
                                    <th class="text-right">Released</th>
                                    <th>Fund</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($anggarans as $a)
                                    <tr>
                                        <td><a href="{{ route('reports.anggaran.show', $a->id) }}">{{ $a->nomor }}</a></td>
                                        <td>{{ $a->date }}</td>
                                        <td>{{ $a->description }}</td>
                                        <td>{{ $a->rab_project }}</td>
                                        <td>{{ $a->type }}</td>
                                        <td>{{ optional($a->department)->department_name ?? '—' }}</td>
                                        <td class="text-right">{{ number_format((float) $a->amount, 2) }}</td>
                                        <td class="text-right">{{ number_format((float) $a->balance, 2) }}</td>
                                        <td>{{ $a->fund_status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $anggarans->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
