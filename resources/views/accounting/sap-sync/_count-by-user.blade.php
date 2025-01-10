<div class="card card-info">
    <div class="card-header pl-2 py-1">
        <h6 class="float-left">Count by User</h6>
    </div>
    @foreach ($data['count_by_user'] as $yearData)
        <div class="card-body p-0">
            <h6 class="px-2 pt-2 text-center">{{ $yearData['year'] }}</h6>
            <div class="mb-4">
                @php
                    $yearTotal = collect($yearData['user_totals'])->sum('total_count');
                @endphp
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th><small>User</small></th>
                            @foreach ($yearData['month_data'] as $month)
                                <th class="text-right"><small>{{ $month['month_name'] }}</small></th>
                            @endforeach
                            <th class="bg-light text-right"><small>Total</small></th>
                            <th class="bg-light text-right"><small>%</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($yearData['month_data'][0]['users'] ?? [] as $user)
                            <tr>
                                <td><small>{{ $user['user_name'] }}</small></td>
                                @foreach ($yearData['month_data'] as $month)
                                    @php
                                        $count =
                                            collect($month['users'])
                                                ->where('user_id', $user['user_id'])
                                                ->first()['count'] ?? 0;
                                    @endphp
                                    <td class="text-right"><small>{{ $count }}</small></td>
                                @endforeach
                                @php
                                    $userTotal = collect($yearData['user_totals'])
                                        ->where('user_id', $user['user_id'])
                                        ->first()['total_count'];
                                    $percentage =
                                        $yearTotal > 0 ? number_format(($userTotal / $yearTotal) * 100, 1) : 0;
                                @endphp
                                <td class="text-right bg-light">
                                    <small><b>{{ $userTotal }}</b></small>
                                </td>
                                <td class="text-right bg-light">
                                    <small><b>{{ $percentage }}%</b></small>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-light font-weight-bold">
                            <td>Total</td>
                            @foreach ($yearData['month_data'] as $month)
                                @php
                                    $monthTotal = collect($month['users'])->sum('count');
                                @endphp
                                <td class="text-right"><small><b>{{ $monthTotal }}</b></small></td>
                            @endforeach
                            <td class="text-right"><small><b>{{ $yearTotal }}</b></small></td>
                            <td class="text-right"><small><b>100.0%</b></small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
