@extends('templates.main')

@section('title_page')
    BAPSB Outstanding
@endsection

@section('breadcrumb_title')
    cashier / bapsb / outstanding
@endsection

@section('content')
    <div class="row vj-show">
        <div class="col-md-6">
            <div class="card card-outline mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Late submissions by unit</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Project</th><th>Period</th><th>Deadline</th></tr></thead>
                        <tbody>
                            @forelse ($outstanding as $row)
                                <tr>
                                    <td>{{ $row['project'] }}</td>
                                    <td>{{ $row['period'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row['deadline'])->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">No overdue units.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-outline mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Pending HO validation</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Number</th><th>Project</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($pending as $item)
                                <tr>
                                    <td>{{ $item->nomor }}</td>
                                    <td>{{ $item->project }}</td>
                                    <td>{{ $item->submitted_at?->format('d M Y') }}</td>
                                    <td><a href="{{ route('cashier.bapsb.show', $item) }}">Review</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center">No pending reports.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
@endsection
