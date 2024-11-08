@extends('templates.main')

@section('title_page')
    DOCUMENTS CREATION
@endsection

@section('breadcrumb_title')
    reports / documents-creation
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-center">
                    <div class="text-left">
                        <b>REKAP</b> | <a href="{{ route('reports.document-creation.by_user', ['project' => '000H']) }}">By
                            User</a> | <a
                            href="{{ route('reports.document-creation.detail', ['project' => '000H']) }}">Data</a>
                    </div>
                    <div class="d-inline-block">
                        Project: <b>{{ $project }}</b>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back to Index</a>
                </div>
            </div>
            <!-- /.card-header -->

            @foreach ($dashboard_data as $year_data)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $year_data['year'] }}</h3>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <td class="text-right">Jan</td>
                                    <td class="text-right">Feb</td>
                                    <td class="text-right">Mar</td>
                                    <td class="text-right">Apr</td>
                                    <td class="text-right">May</td>
                                    <td class="text-right">Jun</td>
                                    <td class="text-right">Jul</td>
                                    <td class="text-right">Aug</td>
                                    <td class="text-right">Sep</td>
                                    <td class="text-right">Oct</td>
                                    <td class="text-right">Nov</td>
                                    <td class="text-right">Dec</td>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Invoice Count</small></td>
                                    @foreach ($year_data['data'] as $item)
                                        <td class="text-right"><small>{{ $item['invoice_count'] }}</small></td>
                                    @endforeach
                                    <td class="text-right"><small><b>{{ $year_data['year_summary']['count'] }}</b></small>
                                    </td>
                                </tr>
                                {{-- <tr>
                                    <td><small>Duration Sum</small></td>
                                    @foreach ($year_data['data'] as $item)
                                        <td class="text-right"><small>{{ $item['sum_duration'] }}</small></td>
                                    @endforeach
                                </tr> --}}
                                <tr>
                                    <td><small>Avarage</small></td>
                                    @foreach ($year_data['data'] as $item)
                                        <td class="text-right"><small>{{ $item['average_duration'] }}</small></td>
                                    @endforeach
                                    <td class="text-right"><small><b>{{ $year_data['year_summary']['average'] }}</b></small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            @endforeach

        </div> <!-- /.col -->
    </div> <!-- /.row -->
@endsection
