<div class="card">
    <div class="card-header">
        <h6>Count & Amount by Project</h6>
    </div>
    <div class="card-body p-0">
        @foreach ($data['count_by_project'] as $yearData)
            <div class="mb-4">
                <h6 class="px-2 pt-2">Year: {{ $yearData['year'] }}</h6>
                @php
                    $yearTotalCount = collect($yearData['project_totals'])->sum('total_count');
                    $yearTotalAmount = collect($yearData['project_totals'])->sum('total_amount');
                @endphp
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th><small>Project</small></th>
                            @foreach ($yearData['month_data'] as $month)
                                <th class="text-right"><small>{{ $month['month_name'] }}</small></th>
                                </th>
                            @endforeach
                            <th class="bg-light text-right"><small>Total</small></th>
                            <th class="bg-light text-right"><small>Amount</small></th>
                            <th class="bg-light text-right"><small>%</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($yearData['month_data'][0]['projects'] ?? [] as $project)
                            <tr>
                                <td><small>{{ $project['project'] }}</small></td>
                                @foreach ($yearData['month_data'] as $month)
                                    @php
                                        $count =
                                            collect($month['projects'])
                                                ->where('project', $project['project'])
                                                ->first()['count'] ?? 0;
                                    @endphp
                                    <td class="text-right"><small>{{ $count }}</small></td>
                                @endforeach
                                @php
                                    $projectTotal = collect($yearData['project_totals'])
                                        ->where('project', $project['project'])
                                        ->first();
                                    $percentage =
                                        $yearTotalCount > 0
                                            ? number_format(($projectTotal['total_count'] / $yearTotalCount) * 100, 1)
                                            : 0;
                                @endphp
                                <td class="text-right bg-light font-weight-bold">
                                    <small><b>{{ $projectTotal['total_count'] }}</b></small>
                                </td>
                                <td class="text-right bg-light font-weight-bold">
                                    <small><b>{{ number_format($projectTotal['total_amount'], 0) }}</b></small>
                                </td>
                                <td class="text-right bg-light font-weight-bold">
                                    <small><b>{{ $percentage }}%</b></small>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-light font-weight-bold">
                            <td><small>Total</small></td>
                            @foreach ($yearData['month_data'] as $month)
                                @php
                                    $monthTotal = collect($month['projects'])->sum('count');
                                @endphp
                                <td class="text-right"><small><b>{{ $monthTotal }}</b></small></td>
                            @endforeach
                            <td class="text-right"><small><b>{{ $yearTotalCount }}</b></small></td>
                            <td class="text-right"><small><b>{{ number_format($yearTotalAmount, 0) }}</b></small></td>
                            <td class="text-right"><small><b>100.0%</b></small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</div>
