@extends('templates.main')

@section('title_page')
    Rekening Koran
@endsection

@section('breadcrumb_title')
    cashier / koran / dashboard
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-koran-links page="dashboard" />

            <div class="card">
                <div class="card-header text-center">
                    <h3 class="card-title"><b>{{ $year }}</b></h3>
                    <a
                        href="{{ route('cashier.koran.index', ['page' => 'dashboard', 'year' => date('Y')]) }}">{{ date('Y') }}</a>
                    |
                    <a href="{{ route('cashier.koran.index', ['page' => 'dashboard', 'year' => 2023]) }}">2023</a>
                </div>

                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="text-right" style="width: 10px">#</th>
                                <th>Bank Account</th>
                                <td class="text-center">Jan</td>
                                <td class="text-center">Feb</td>
                                <td class="text-center">Mar</td>
                                <td class="text-center">Apr</td>
                                <td class="text-center">May</td>
                                <td class="text-center">Jun</td>
                                <td class="text-center">Jul</td>
                                <td class="text-center">Aug</td>
                                <td class="text-center">Sep</td>
                                <td class="text-center">Oct</td>
                                <td class="text-center">Nov</td>
                                <td class="text-center">Dec</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($korans as $koran)
                                @foreach ($koran['giros'] as $index => $giro)
                                    <tr>
                                        <td class="text-right">{{ $index + 1 }}</td>
                                        <td><small>{{ $giro['acc_name'] }}</small></td>
                                        @foreach ($giro['data'] as $month)
                                            @if ($month['status'] == false)
                                                <td class="text-center"><i class="fas fa-times" style="color: red"></i></td>
                                            @else
                                                <td class="text-center"><a href="{{ $month['filename1'] }}"
                                                        target="_blank"><i class="fas fa-check"
                                                            style="color: green"></i></a></td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>


        </div> <!-- /.col -->
    </div> <!-- /.row -->
@endsection

<style>
    .card-header .active {
        /* font-weight: bold; */
        color: black;
        text-transform: uppercase;
    }
</style>
