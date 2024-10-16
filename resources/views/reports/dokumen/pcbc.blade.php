@extends('templates.main')

@section('title_page')
    PCBC
@endsection

@section('breadcrumb_title')
    report / rek-koran
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Oktober 2024</h3>
                    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back to Index</a>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="text-right" style="width: 10px">#</th>
                                <th>Bank Account</th>
                                <th class="text-right">Project</th>
                                <th class="text-right">BG</th>
                                <th class="text-right">LoA</th>
                                <th class="text-right" style="width: 40px">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            <tr>
                                {{-- <td class="text-right">{{ $index + 1 }}</td> --}}
                                {{-- <td>{{ $giro->acc_no . ' - ' . $giro->acc_name }}</td> --}}
                                {{-- <td class="text-right" style="width: 10%">{{ $item['cek'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['bg'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['loa'] }}</td>
                                <td class="text-right" style="width: 10%">{{ $item['total'] }}</td> --}}
                            </tr>    
                           
                        </tbody>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        
        </div> <!-- /.col -->
    </div>  <!-- /.row -->
@endsection