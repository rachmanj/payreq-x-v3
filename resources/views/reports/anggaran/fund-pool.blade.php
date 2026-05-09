@extends('templates.main')

@section('title_page')
    RAB Fund pool
@endsection

@section('breadcrumb_title')
    reports / anggaran / fund-pool
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Fund pool — approved active RAB</h3>
                    <a href="{{ route('reports.anggaran.index') }}" class="btn btn-xs btn-secondary float-right ml-1">RAB list</a>
                    <a href="{{ route('reports.anggaran.dashboard') }}" class="btn btn-xs btn-info float-right ml-1">Dashboard</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="get" class="form-inline mb-3">
                        <label class="mr-2">Project</label>
                        <select name="project" class="form-control form-control-sm mr-2">
                            <option value="">All</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->code }}" @selected($projectFilter === $p->code)>{{ $p->code }}</option>
                            @endforeach
                        </select>
                        <label class="mr-2">Fund status</label>
                        <select name="fund_status" class="form-control form-control-sm mr-2">
                            <option value="">All</option>
                            <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                            <option value="pooled" @selected($statusFilter === 'pooled')>Pooled</option>
                            <option value="released" @selected($statusFilter === 'released')>Released</option>
                        </select>
                        <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                    </form>

                    <form method="post" action="{{ route('reports.anggaran.fund-pool.pool') }}" class="mb-2" onsubmit="return confirm('Mark selected as pooled?');">
                        @csrf
                        <button class="btn btn-sm btn-warning" type="submit">Mark pooled</button>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:40px"></th>
                                        <th>Nomor</th>
                                        <th>Project</th>
                                        <th>Dept</th>
                                        <th class="text-right">Amount</th>
                                        <th>Fund</th>
                                        <th>Pooled at</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($anggarans as $a)
                                        <tr>
                                            <td><input type="checkbox" name="ids[]" value="{{ $a->id }}"></td>
                                            <td><a href="{{ route('reports.anggaran.show', $a->id) }}">{{ $a->nomor }}</a></td>
                                            <td>{{ $a->rab_project }}</td>
                                            <td>{{ optional($a->department)->department_name ?? '—' }}</td>
                                            <td class="text-right">{{ number_format((float) $a->amount, 2) }}</td>
                                            <td>{{ $a->fund_status }}</td>
                                            <td>{{ $a->fund_pooled_at ? $a->fund_pooled_at->format('Y-m-d H:i') : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <form method="post" action="{{ route('reports.anggaran.fund-pool.release') }}" onsubmit="return confirm('Mark selected pooled rows as released?');">
                        @csrf
                        <button class="btn btn-sm btn-success" type="submit">Mark released (pooled only)</button>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:40px"></th>
                                        <th>Nomor</th>
                                        <th>Project</th>
                                        <th class="text-right">Amount</th>
                                        <th>Fund</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($anggarans as $a)
                                        @if ($a->fund_status === \App\Models\Anggaran::FUND_STATUS_POOLED)
                                            <tr>
                                                <td><input type="checkbox" name="ids[]" value="{{ $a->id }}"></td>
                                                <td>{{ $a->nomor }}</td>
                                                <td>{{ $a->rab_project }}</td>
                                                <td class="text-right">{{ number_format((float) $a->amount, 2) }}</td>
                                                <td>{{ $a->fund_status }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>

                    {{ $anggarans->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
