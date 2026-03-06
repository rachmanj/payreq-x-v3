<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Summary Unit Expense - {{ $unit_no }}</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <link rel="stylesheet" href="{{ asset('adlte2/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('adlte2/adminlte.min.css') }}">
</head>
<body>
    <div class="row">
        <div class="col-md-12">
            <div class="box-header mb-3">
                <h3 class="box-title">Expense Detail of Unit No: {{ $unit_no }} (Year: {{ $year ?? date('Y') }})</h3>
                <a href="{{ route('reports.equipment.index') }}" class="btn btn-sm btn-primary float-right">
                    <i class="fas fa-arrow-left"></i> Back to Report
                </a>
            </div>

            {{-- SERVICE --}}
            <div class="box box-secondary">
                <div class="box-header">
                    <h3 class="box-title">Expense Detail of Unit No: {{ $unit_no }}</h3> <hr>
                    <h3 class="box-title">SERVICE total IDR {{ number_format($result['service']['total'], 0) }}</h3>
                </div>
                <div class="box-body">
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
                                    <td>{{ $item['description'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['qty'] ?? 0, 0) }}</td>
                                    <td>{{ $item['uom'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['km_position'] ?? 0, 0) }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- OTHERS --}}
            <div class="box box-secondary">
                <div class="box-header">
                    <h3 class="box-title">OTHERS total IDR {{ number_format($result['other']['total'], 0) }}</h3>
                </div>
                <div class="box-body">
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
                                    <td>{{ $item['description'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['qty'] ?? 0, 0) }}</td>
                                    <td>{{ $item['uom'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['km_position'] ?? 0, 0) }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- FUEL --}}
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">FUEL total IDR {{ number_format($result['fuel']['total'], 0) }}</h3>
                </div>
                <div class="box-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Desc</th>
                            <th class="text-right">Qty</th>
                            <th>UOM</th>
                            <th class="text-right">HM</th>
                            <th class="text-right">Amount</th>
                        </tr>
                        <tbody>
                            @foreach ($result['fuel']['details'] as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->created_at->format('d-M-Y') }}</td>
                                    <td>{{ $item['description'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['qty'] ?? 0, 0) }}</td>
                                    <td>{{ $item['uom'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['km_position'] ?? 0, 0) }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAX --}}
            <div class="box box-secondary">
                <div class="box-header">
                    <h3 class="box-title">TAX total IDR {{ number_format($result['tax']['total'] ?? 0, 0) }}</h3>
                </div>
                <div class="box-body">
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
                            @foreach ($result['tax']['details'] ?? [] as $key => $item)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $item->created_at->format('d-M-Y') }}</td>
                                    <td>{{ $item['description'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['qty'] ?? 0, 0) }}</td>
                                    <td>{{ $item['uom'] ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($item['km_position'] ?? 0, 0) }}</td>
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