@extends('templates.main')

@section('title_page')
  End Of Month Adjusment
@endsection

@section('breadcrumb_title')
    eom
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">End Of Month PC Rekap</h3>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back to Index</a>
        <a href="{{ route('reports.eom.export') }}" class="btn btn-sm btn-warning float-right mr-2" style="color: black; font-weight: bold">Export to Excel</a>
      </div>
      <div class="form-horizontal">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        {{-- <th>#</th> --}}
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-center">Project</th>
                        <th class="text-center">CCenter</th>
                        <th class="text-right">Debit (IDR)</th>
                        <th class="text-right">Credit (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($journal as $key => $item)
                    <tr> 
                        {{-- <td>{{ $key + 1 }}</td> --}}
                        <td>
                            {{ $item['debit']['account_number'] }} <br> 
                            <small><b>{{ $item['debit']['account_name'] }}</b></small>
                        </td>
                        <td>{{ $item['debit']['description'] }}</td>
                        <td class="text-center">{{ $item['debit']['project_code'] }}</td>
                        <td class="text-center">{{ $item['debit']['ccenter'] }}</td>
                        <td class="text-right">{{ $item['debit']['amount'] }}</td>
                        <td class="text-right">0.00</td>
                    </tr>
                    <tr>
                        {{-- <td>{{ $key + 2 }}</td> --}}
                        <td>
                            {{ $item['credit']['account_number'] }} <br> 
                            <small><b>{{ $item['credit']['account_name'] }}</b></small>
                        </td>
                        <td>{{ $item['credit']['description'] }}</td>
                        <td class="text-center">{{ $item['credit']['project_code'] }}</td>
                        <td class="text-center">{{ $item['credit']['ccenter'] }}</td>
                        <td class="text-right">0.00</td>
                        <td class="text-right">{{ $item['credit']['amount'] }}</td>
                    </tr>
                    @endforeach
                    {{-- <tr>
                        <th class="text-right" colspan="5">TOTAL</th>
                        <th class="text-right">{{ number_format($vj_details->where('debit_credit', 'debit')->sum('amount'), 2) }}</th>
                        <th class="text-right">{{ number_format($vj_details->where('debit_credit', 'credit')->sum('amount'), 2) }}</th>
                    </tr> --}}
                </tbody>
            </table>
        </div>
      </div>
    </div> 
  </div>
</div>

@endsection