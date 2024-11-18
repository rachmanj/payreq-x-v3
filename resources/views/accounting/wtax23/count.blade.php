<div class="card card-info mb-1">
    <div class="card-header p-1">
        <h3 class="card-title">By Record Count of Creating Date</h3>
    </div>
</div>

@foreach ($count_data as $item)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $item['year'] }}</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th></th>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-center" colspan="2"><small>{{ $sub_item['month_name'] }}</small></td>
                        @endforeach
                        <th class="text-right">Total</th>
                    </tr>
                    <tr>
                        <th>Desc</th>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small><i class="fas fa-times" style="color: red"></i></small></td>
                            <td class="text-right"><small><i class="fas fa-check" style="color: green"></i></small>
                            </td>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><small>Purchase</small></td>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small>{{ $sub_item['out']['outstanding'] }}</small>
                            <td class="text-right"><small>{{ $sub_item['out']['complete'] }}</small>
                            </td>
                        @endforeach
                        <td class="text-right"><small>{{ $item['out'] }}</small></td>
                    </tr>
                    <tr>
                        <td><small>Sales</small></td>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small>{{ $sub_item['in']['outstanding'] }}</small></td>
                            <td class="text-right"><small>{{ $sub_item['in']['complete'] }}</small></td>
                        @endforeach
                        <td class="text-right"><small>{{ $item['in'] }}</small></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endforeach
