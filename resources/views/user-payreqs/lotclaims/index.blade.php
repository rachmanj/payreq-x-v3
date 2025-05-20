@extends('templates.main')

@section('title_page')
    Letter of Official Travel
@endsection

@section('breadcrumb_title')
    LOT Claims
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">LOT Claims</h3>
                    <a href="{{ route('user-payreqs.lotclaims.create') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-plus"></i> New LOTC
                    </a>
                </div>

                <div class="card-body">
                    <table id="lotclaims" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>LOT No</th>
                                <th>Project</th>
                                <th>Date</th>
                                <th>Advance</th>
                                <th>Total Claim</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
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
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>

    <script>
        $(function() {
            $("#lotclaims").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('user-payreqs.lotclaims.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'lot_no'
                    },
                    {
                        data: 'project',
                        className: 'text-center'
                    },
                    {
                        data: 'claim_date',
                        render: function(data) {
                            return moment(data).format('DD MMMM YYYY');
                        }
                    },
                    {
                        data: 'advance_amount',
                        render: $.fn.dataTable.render.number(',', '.', 2, 'Rp '),
                        className: 'text-right'
                    },
                    {
                        data: 'total_claim',
                        render: $.fn.dataTable.render.number(',', '.', 2, 'Rp '),
                        className: 'text-right'
                    },
                    {
                        data: 'claim_remarks'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                ],
                fixedHeader: true,
            })
        });
    </script>
    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
@endsection
