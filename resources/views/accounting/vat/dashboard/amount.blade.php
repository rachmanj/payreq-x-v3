<div class="card card-info mb-1">
    <div class="card-header p-1">
        <h3 class="card-title">By Amount of Faktur Date <small>(IDR 000)</small></h3>
    </div>
</div>

@foreach ($amount_data as $item)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $item['year'] }}</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Desc</th>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right">{{ $sub_item['month_name'] }}</td>
                        @endforeach
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top: 3px solid #000;">
                        <td><small>Purchase</small></td>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small>{{ $sub_item['purchase'] }}</small></td>
                        @endforeach
                        <td class="text-right"><small>{{ $item['purchase'] }}</small></td>
                    </tr>
                    <tr>
                        <td><small>Sales</small></td>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small>{{ $sub_item['sales'] }}</small></td>
                        @endforeach
                        <td class="text-right"><small>{{ $item['sales'] }}</small></td>
                    </tr>
                    <tr style="border-top: 3px solid #000;">
                        <td><small>Variance</small></td>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small>{{ $sub_item['difference'] }}</small></td>
                        @endforeach
                        <td class="text-right"><small>{{ $item['difference'] }}</small></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endforeach
