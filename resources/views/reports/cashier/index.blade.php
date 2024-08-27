@extends('templates.main')

@section('title_page')
Cashier Reports
@endsection

@section('breadcrumb_title')
    reports / cashier
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Today's Transaction Rekaps | {{ date('d F Y') }}</h3>
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back to Index</a>
            </div>
            <div class="card-body">
                <table class="table table-sm table-striped">
                    <tbody>
                        <tr>
                            <td>Opening Balance</td>
                            <td class="text-right">IDR {{ number_format($data['opening_balance'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Incoming</td>
                            <td class="text-right">IDR {{ $data['total_incoming'] }}</td>
                        </tr>
                        <tr>
                            <td>Outgoing</td>
                            <td class="text-right">IDR {{ $data['total_outgoing'] }}</td>
                        </tr>
                        <tr>
                            <td>Closing Balance</td>
                            <td class="text-right">IDR {{ $data['closing_balance'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row">
            <div class="col-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Incoming List</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th>Desc</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['incomings'] as $key => $item)
                                    <tr>
                                        <td class="text-right">{{ $key + 1 }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-right">IDR {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-6">
                    <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Outgoing List</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th>Desc</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['outgoings'] as $key => $item)
                                    <tr>
                                        <td class="text-right">{{ $key + 1 }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-right">IDR {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
            
    </div>
</div>
@endsection