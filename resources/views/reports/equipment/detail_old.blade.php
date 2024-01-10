<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PayReq System</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
  
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
  
</head>
<body>
    <div class="row">
        <div class="col-12">
            <div class="card card-primary my-2">
                <div class="card-header">
                    <h3 class="card-title">Expense Detail of Unit No: {{ $unit_no }}</h3>
                </div>
            </div>

            {{-- FUEL --}}
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">FUEL total IDR {{ number_format($result['fuel']['total'], 0) }}</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Desc</th>
                                <th class="text-right">Qty</th>
                                <th>UOM</th>
                                <th class="text-right">HM</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['fuel']['details'] as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->created_at->format('d-M-Y') }}</td>
                                    <td>{{ $item['description'] }}</td>
                                    <td class="text-right">{{ number_format($item['qty'], 0) }}</td>
                                    <td>{{ $item['uom'] }}</td>
                                    <td class="text-right">{{ $item['km_position'] }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        
                    </table>
                </div>
            </div>

            {{-- SERVICE --}}
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">SERVICE total IDR {{ number_format($result['service']['total'], 0) }}</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Desc</th>
                                <th class="text-right">Qty</th>
                                <th>UOM</th>
                                <th class="text-right">HM</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['service']['details'] as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->created_at->format('d-M-Y') }}</td>
                                    <td>{{ $item['description'] }}</td>
                                    <td class="text-right">{{ number_format($item['qty'], 0) }}</td>
                                    <td>{{ $item['uom'] }}</td>
                                    <td class="text-right">{{ $item['km_position'] }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- OTHERS --}}
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">OTHERS total IDR {{ number_format($result['other']['total'], 0) }}</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Desc</th>
                                <th class="text-right">Qty</th>
                                <th>UOM</th>
                                <th class="text-right">HM</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['other']['details'] as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->created_at->format('d-M-Y') }}</td>
                                    <td>{{ $item['description'] }}</td>
                                    <td class="text-right">{{ number_format($item['qty'], 0) }}</td>
                                    <td>{{ $item['uom'] }}</td>
                                    <td class="text-right">{{ $item['km_position'] }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>