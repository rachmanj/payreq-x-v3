@extends('templates.main')

@section('title_page')
    BAPSB
@endsection

@section('breadcrumb_title')
    cashier / bapsb
@endsection

@section('content')
    <div class="row vj-show">
        <div class="col-12">
            <div class="card card-outline">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Bank Securities Inspection Report (BAPSB)</h5>
                    @can('akses_bapsb')
                        <a href="{{ route('cashier.bapsb.create') }}" class="btn btn-sm btn-primary">+ BAPSB</a>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="small text-muted">Period</label>
                            <select id="filter-period" class="form-control form-control-sm">
                                <option value="">All</option>
                                @foreach ($periods as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">Project</label>
                            <select id="filter-project" class="form-control form-control-sm">
                                <option value="">All</option>
                                @foreach ($projects as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <table id="bapsb-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Number</th>
                                <th>Period</th>
                                <th>Project</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Bilyets</th>
                                <th>Validation</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        $(function() {
            var table = $('#bapsb-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('cashier.bapsb.data') }}',
                    data: function(d) {
                        d.period = $('#filter-period').val();
                        d.project = $('#filter-project').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    { data: 'nomor' },
                    { data: 'period' },
                    { data: 'project' },
                    { data: 'bapsb_date' },
                    { data: 'submission', orderable: false },
                    { data: 'bilyet_count', searchable: false },
                    { data: 'validation_status', orderable: false },
                    { data: 'action', orderable: false, searchable: false },
                ],
            });

            $('#filter-period, #filter-project').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
