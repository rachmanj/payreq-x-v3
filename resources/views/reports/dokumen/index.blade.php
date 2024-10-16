@extends('templates.main')

@section('title_page')
    REKENING KORAN
@endsection

@section('breadcrumb_title')
    report / rek-koran
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    {{-- <h3 class="card-title">2024</h3> --}}
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right ml-2"><i class="fas fa-arrow-left"></i> Back to Index</a>
                    <a href="{{ route('cashier.dokumen.index') }}" class="float-right"> Upload Dokumen</a>
                </div>
            </div>

            @foreach ($korans as $koran)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $koran['year'] }}</h3>
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
                            @foreach ($koran['giros'] as $index => $giro)
                                <tr>
                                    <td class="text-right">{{ $index + 1 }}</td>
                                    <td><small>{{ $giro['acc_name'] }}</small></td>
                                    @foreach ($giro['data'] as $month)
                                    
                                        @if($month['status'] == false)
                                        <td class="text-center"><i class="fas fa-times" style="color: red"></i></td>
                                        @else
                                        <td class="text-center"><i class="fas fa-check" style="color: green"></i></td>
                                        @endif

                                    @endforeach
                                </tr>    
                            @endforeach
                        </tbody>
                    </table>
                </div>    

                 
            </div>    
            @endforeach
             
        
        </div> <!-- /.col -->
    </div>  <!-- /.row -->
@endsection