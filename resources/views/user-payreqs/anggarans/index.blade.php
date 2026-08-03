@extends('templates.main')

@section('title_page')
    RAB
@endsection

@section('breadcrumb_title')
    payreqs / rab
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-calculator"></i> RAB
                        </h3>
                        <a href="{{ route('user-payreqs.anggarans.create') }}" class="vj-btn vj-btn-primary">
                            <i class="fas fa-plus"></i> RAB
                        </a>
                    </div>

                    <div class="card-body border-bottom py-2">
                        <div class="vj-note mb-0">
                            Up to 300 rows are loaded per role rules; progress uses stored balance fields refreshed by
                            scheduled sync and accounting tools.
                        </div>
                    </div>

                    <div class="card-body">
                        <table id="anggarans" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nomor</th>
                                    <th>For<br>Usage</th>
                                    <th>Description</th>
                                    <th>Budget IDR</th>
                                    <th>Progres</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            $("#anggarans").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('user-payreqs.anggarans.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'rab_project'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'budget'
                    },
                    {
                        data: 'progres'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                        targets: [0, 4],
                        className: 'text-right'
                    },
                    {
                        targets: [5],
                        className: 'text-center'
                    },
                ]
            })
        });
    </script>
@endsection
