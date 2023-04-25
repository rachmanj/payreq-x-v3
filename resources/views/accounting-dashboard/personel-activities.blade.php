<div class="card card-info">
    <div class="card-header border-transparent">
        <h3 class="card-title"><b>Personel Activities</b></h3>
    </div>
    <div class="card-body p-0">
        <table class="table m-0 table-striped table-bordered">
            <thead>
                <th>Month</th>
                @foreach ($activity_personels as $personel)
                    <th class='text-center'>{{ $personel->user->username }}</th>
                @endforeach
                <th class='text-center'>Total</th>
            </thead>
            <tbody>
                @foreach ($activities_months as $month)
                    <tr>
                        <th>{{ date('M', strtotime('2022-' . $month->month . '-01')) }}</th>
                        @foreach ($activity_personels as $personel)
                            <td class="text-right">{{ $activities_count->where('month', $month->month)->where('user_id', $personel->user_id)->first() ? 
                            number_format(($activities_count->where('month', $month->month)->where('user_id', $personel->user_id)->first()->total_count / $activities_count->where('month', $month->month)->sum('total_count')) * 100, 2) : '-'
                            }} %</td>
                        @endforeach
                        <th class="text-right">{{ number_format($activities_count->where('month', $month->month)->sum('total_count'), 0) }}</th>
                    </tr>
                @endforeach
                <tr>
                    <th>Total</th>
                    @foreach ($activity_personels as $personel)
                        <th class="text-right">
                            {{ number_format( ($activities_count->where('user_id', $personel->user_id)->sum('total_count') / $activities_count->sum('total_count')) * 100, 2) }} %
                        </th>
                    @endforeach
                    <th class="text-right">{{ number_format($activities_count->sum('total_count'), 0) }}</th>
                </tr>
            </tbody>
        </table>
    </div>
</div>