@extends('templates.main')

@section('title_page')
    TX HISTORY
@endsection

@section('breadcrumb_title')
    transaksi
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account No: {{ $account->account_number }} - {{ $account->account_name }}</h3>
                </div> <!-- /.card-header -->

                <div class="card-body">
                    <table id="transaksis" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="text-right">#</th>
                                <th class="text-center">Create at</th>
                                <th class="text-center">PostD</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Desc</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th class="text-right">Balance</th>
                                {{-- <th></th> --}}
                            </tr>
                        </thead>
                    </table>
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->
        </div> <!-- /.col -->
    </div> <!-- /.row -->
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            $("#transaksis").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                ajax: {
                    url: '{{ route('cashier.transaksis.data') . '?account_id=' . $account->id }}',
                    type: 'GET',
                    // Add a loading indicator
                    beforeSend: function() {
                        $('#transaksis').addClass('loading');
                    },
                    complete: function() {
                        $('#transaksis').removeClass('loading');
                    }
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'posting_date'
                    },
                    {
                        data: 'document_type'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'debit'
                    },
                    {
                        data: 'credit'
                    },
                    {
                        data: 'row_balance'
                    },
                ],
                fixedHeader: true,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [{
                        "targets": [1, 2, 3],
                        "className": "text-center"
                    },
                    {
                        "targets": [0, 5, 6, 7],
                        "className": "text-right"
                    },
                    {
                        "targets": [4],
                        "width": "30%"
                    }
                ],
                // Enable caching of Ajax requests for better performance
                stateSave: true,
                // Optimize rendering
                scroller: true,
                scrollY: '50vh',
                scrollCollapse: true
            });
        });
    </script>

    <style>
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7) url('{{ asset('images/loading.gif') }}') no-repeat center center;
            z-index: 1000;
        }
    </style>
@endsection
