@extends('templates.main')

@section('title_page')
    Delivery
@endsection

@section('breadcrumb_title')
    accounting / delivery / dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <x-delivery-links page="dashboard" />

            <div class="card card-info">
                <div class="card-header pl-2 py-1">
                    <h6 class="mb-0">Delivery Summary</h6>
                </div>
                @foreach ($data as $yearData)
                    <div class="card-body p-0">
                        <h6 class="px-2 pt-2 text-center">{{ $yearData['year'] }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped" style="border-bottom: 3px solid;">
                                <thead>
                                    <tr>
                                        <th class="text-center">Project</th>
                                        <th class="text-center">Jan</th>
                                        <th class="text-center">Feb</th>
                                        <th class="text-center">Mar</th>
                                        <th class="text-center">Apr</th>
                                        <th class="text-center">May</th>
                                        <th class="text-center">Jun</th>
                                        <th class="text-center">Jul</th>
                                        <th class="text-center">Aug</th>
                                        <th class="text-center">Sep</th>
                                        <th class="text-center">Oct</th>
                                        <th class="text-center">Nov</th>
                                        <th class="text-center">Dec</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($yearData['projects'] as $project)
                                        <tr>
                                            <td class="text-center">{{ $project['project'] }}</td>
                                            @php
                                                $yearTotal = 0;
                                            @endphp
                                            @foreach ($project['months'] as $month)
                                                <td class="text-center">
                                                    @foreach ($month['deliveries'] as $delivery)
                                                        <small>{{ \Carbon\Carbon::parse($delivery['received_date'])->format('d') }}</small>
                                                        @if (!$loop->last)
                                                            <br>
                                                        @endif
                                                    @endforeach
                                                </td>
                                                @php
                                                    $yearTotal += $month['count'];
                                                @endphp
                                            @endforeach
                                            <td class="text-right font-weight-bold">{{ $yearTotal }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .table th {
            background-color: #f4f6f9;
            font-weight: bold;
        }

        .table td {
            vertical-align: middle;
        }

        .table td:last-child,
        .table th:last-child {
            background-color: #f4f6f9;
            font-weight: bold;
        }

        small {
            color: #666;
        }

        .card-header .active {
            font-weight: bold;
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection
