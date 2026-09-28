@extends('templates.main')

@section('title_page')
    Create BAPSB
@endsection

@section('breadcrumb_title')
    cashier / bapsb / create
@endsection

@section('content')
    <div class="row vj-show">
        <div class="col-12">
            <form method="POST" action="{{ route('cashier.bapsb.store') }}">
                @csrf
                <div class="card card-outline mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Header</h5></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Period</label>
                                <select name="period" class="form-control" onchange="window.location='{{ route('cashier.bapsb.create') }}?project='+document.getElementById('project-select').value+'&period='+this.value" id="period-select">
                                    @foreach ($periods as $p)
                                        <option value="{{ $p }}" @selected($period === $p)>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Project</label>
                                <select name="project" class="form-control" id="project-select" onchange="window.location='{{ route('cashier.bapsb.create') }}?project='+this.value+'&period='+document.getElementById('period-select').value">
                                    @foreach ($projects as $code)
                                        <option value="{{ $code }}" @selected($project === $code)>{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Report date</label>
                                <input type="date" name="bapsb_date" class="form-control" value="{{ old('bapsb_date', now()->toDateString()) }}" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Checked by 1</label>
                                <input type="text" name="checker1" class="form-control" value="{{ old('checker1') }}" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Checked by 2</label>
                                <input type="text" name="checker2" class="form-control" value="{{ old('checker2') }}" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Approved by (optional)</label>
                                <input type="text" name="approved_by" class="form-control" value="{{ old('approved_by') }}">
                            </div>
                        </div>
                    </div>
                </div>

                @if (empty($grouped))
                    <div class="alert alert-warning">No on-hand / released bilyets for this period on giro accounts. Register bilyets first or choose another period.</div>
                @else
                    @include('cashier.bapsb._form_lines', ['grouped' => $grouped, 'locations' => $locations])
                @endif

                <div class="card card-outline mb-3">
                    <div class="card-header"><h5 class="card-title mb-0">Summary</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Bilyet Giro</dt>
                            <dd class="col-sm-9">{{ $summary['count_bg'] }} pcs — Rp {{ number_format($summary['total_bg'], 0, ',', '.') }}</dd>
                            <dt class="col-sm-3">Checks</dt>
                            <dd class="col-sm-9">{{ $summary['count_cek'] }} pcs — Rp {{ number_format($summary['total_cek'], 0, ',', '.') }}</dd>
                            <dt class="col-sm-3">LOA</dt>
                            <dd class="col-sm-9">{{ $summary['count_loa'] }} pcs — Rp {{ number_format($summary['total_loa'], 0, ',', '.') }}</dd>
                            <dt class="col-sm-3">Mutations this period</dt>
                            <dd class="col-sm-9">Settled: {{ $mutations['count_cair'] }}, Voided: {{ $mutations['count_void'] }}</dd>
                        </dl>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" @disabled(empty($grouped))>Save draft</button>
                <a href="{{ route('cashier.bapsb.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
