@extends('templates.main')

@section('title_page')
    Verifications
@endsection

@section('breadcrumb_title')
    verifications
@endsection

@section('content')
    <div class="vj-show">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-clipboard-check"></i> Verifications
                        </h3>
                        <a href="{{ route('verifications.journal.index') }}" class="vj-btn vj-btn-success">
                            <i class="fas fa-book"></i> Create Journal
                        </a>
                    </div>
                    <div class="card-body">
                        <table id="verifications" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Realization No</th>
                                <th>Realization Date</th>
                                <th>Payreq No</th>
                                <th>Employee</th>
                                <th>Project</th>
                                <th>Accounts</th>
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
    @include('partials.vj-soft-ui-styles')
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            $("#verifications").DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                autoWidth: false,
                ajax: {
                    url: '{{ route('verifications.data') }}',
                    timeout: 15000
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'realization_no'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'payreq_no'
                    },
                    {
                        data: 'requestor'
                    },
                    {
                        data: 'project'
                    },
                    {
                        data: 'is_complete',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                responsive: true,
                order: [],
            });
        });
    </script>
@endsection
