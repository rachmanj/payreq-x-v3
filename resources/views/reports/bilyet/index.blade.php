@extends('templates.main')

@section('title_page')
    BILYETS
@endsection

@section('breadcrumb_title')
    report / bilyets
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">On Hand</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Bank Account</th>
                                <th class="text-right">Cek</th>
                                <th class="text-right">BG</th>
                                <th class="text-right">LoA</th>
                                <th class="text-right" style="width: 40px">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['onhands'] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['acc_no'] . ' - ' . $item['acc_name'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['cek'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['bg'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['loa'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['total'] }}</td>
                            </tr>    
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Release</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Bank Account</th>
                                <th class="text-right">Cek</th>
                                <th class="text-right">BG</th>
                                <th class="text-right">LoA</th>
                                <th class="text-right" style="width: 40px">Total</th>
                                <th class="text-right" style="width: 40px">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['released'] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['acc_no'] . ' - ' . $item['acc_name'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['cek'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['bg'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['loa'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['total'] }}</td>
                                <td class="text-right" style="width: 15%">{{ number_format($item['amount'], 0, ',', '.') . ',-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Due This Month</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Bank Account</th>
                                <th class="text-right">Cek</th>
                                <th class="text-right">BG</th>
                                <th class="text-right">LoA</th>
                                <th class="text-right" style="width: 40px">Total</th>
                                <th class="text-right" style="width: 40px">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['due_this_month'] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['acc_no'] . ' - ' . $item['acc_name'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['cek'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['bg'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['loa'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['total'] }}</td>
                                <td class="text-right" style="width: 15%">{{ number_format($item['amount'], 0, ',', '.') . ',-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Void</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Bank Account</th>
                                <th class="text-right">Cek</th>
                                <th class="text-right">BG</th>
                                <th class="text-right">LoA</th>
                                <th class="text-right" style="width: 40px">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['void'] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['acc_no'] . ' - ' . $item['acc_name'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['cek'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['bg'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['loa'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['total'] }}</td>
                            </tr>    
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        
        </div> <!-- /.col -->
    </div>  <!-- /.row -->
@endsection