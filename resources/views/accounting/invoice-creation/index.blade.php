@extends('templates.main')

@section('title_page')
    INVOICE DAILY TX
@endsection

@section('breadcrumb_title')
    accounting / invoice daily tx
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Monthly Average Input</h3>
                    <button href="#" class="btn btn-xs btn-primary float-right mr-2" data-toggle="modal"
                        data-target="#modal-upload"> Upload</button>
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
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><small>Invoice Count</small></td>
                                    @foreach ($year_data['data'] as $item)
                                        <td class="text-right"><small>{{ $item['invoice_count'] }}</small></td>
                                    @endforeach
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
                                </tr>
                            </tbody>
                        </table>
                    </div>


                </div>
            @endforeach



        </div> <!-- /.col -->
    </div> <!-- /.row -->

    <div class="modal fade" id="modal-upload">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> Upload Invoice Tx</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('accounting.invoice-creation.upload') }}" enctype="multipart/form-data"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <label>Pilih file excel</label>
                        <div class="form-group">
                            <input type="file" name='file_upload' required class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-primary"> Upload</button>
                    </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endsection
