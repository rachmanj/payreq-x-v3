@extends('templates.main')

@section('title_page')
    PCBC
@endsection

@section('breadcrumb_title')
    cashier / pcbc / dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-pcbc-links page="dashboard" />

            <div class="card">
                <div class="card-header text-center">
                    <h3 class="card-title"><b>{{ $year }}</b></h3>
                    <a
                        href="{{ route('cashier.pcbc.index', ['page' => 'dashboard', 'year' => date('Y')]) }}">{{ date('Y') }}</a>
                    |
                    <a href="{{ route('cashier.pcbc.index', ['page' => 'dashboard', 'year' => 2025]) }}">2025</a>
                </div>

                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr style="border-bottom: 3px solid black;">
                                <th>Month</th>
                                @foreach ($data['project_data'] as $project)
                                    <th class="text-center">{{ $project['project_code'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($months as $month)
                                <tr>
                                    <td>{{ \Carbon\Carbon::create()->month($month)->format('F') }}</td>
                                    @foreach ($data['project_data'] as $project)
                                        @php
                                            $monthData = collect($project['months_data'])->firstWhere('month', $month);
                                        @endphp
                                        <td class="text-center">
                                            @if ($monthData && $monthData['total_files'] > 0)
                                                @foreach ($monthData['files'] as $file)
                                                    <a href="{{ $file['filename'] }}" target="_blank">
                                                        <i class="fas fa-circle" style="color: green;"
                                                            title="{{ $file['document_date'] }}"></i>
                                                    </a>
                                                @endforeach
                                            @else
                                                <small>No Files</small>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

        </div> <!-- /.col -->
    </div> <!-- /.row -->
@endsection

<style>
    .card-header .active {
        color: black;
        text-transform: uppercase;
    }
</style>
