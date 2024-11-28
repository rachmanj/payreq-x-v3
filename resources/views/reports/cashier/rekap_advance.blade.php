@extends('templates.main')

@section('title_page')
    Advance Rekaps
@endsection

@section('breadcrumb_title')
    reports / cashier / advance-rekaps
@endsection

@section('content')
    <div class="row">
        <div class="col-8">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Employee Advance | {{ $data['project'] }} | {{ date('d F Y') }}</h3>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back to Index</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <tbody>
                            @foreach ($data['list'] as $employee)
                                <tr>
                                    <th colspan="4" class="pb-0"><small><b>{{ $employee['name'] }}</b></small></th>
                                    <th class="pb-0 text-right">
                                        <small><b>Rp.{{ $employee['total_user_advance'] }}</b></small></th>
                                    @foreach ($employee['item_details'] as $item)
                                <tr>
                                    <td></td>
                                    <td class="py-0" colspan="2"><small>{{ $item['item_desc'] }}</small></td>
                                    <td class="py-0 text-right"><small>Rp.{{ $item['item_amount'] }}</small></td>
                                    {{-- <td></td> --}}
                                </tr>
                            @endforeach
                            </tr>
                            @endforeach
                            <tr>
                                <th colspan="4" class="pb-0"><small><b>TOTAL</b></small></th>
                                <th class="pb-0 text-right">
                                    <small><b>Rp.{{ $data['sum_all_amounts_by_project'] }}</b></small></th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
