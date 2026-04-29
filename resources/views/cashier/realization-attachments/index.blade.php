@extends('templates.main')

@section('title_page')
    Realization Attachments
@endsection

@section('breadcrumb_title')
    cashier / realization attachments
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Realization Attachments</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-end mb-3">
                        <div class="col-md-3">
                            <label for="filter_project" class="small text-muted mb-1">Project</label>
                            <select id="filter_project" class="form-control form-control-sm select2bs4" style="width:100%">
                                <option value="">All (within your access)</option>
                                @foreach ($filterProjects as $code)
                                    <option value="{{ $code }}"
                                        {{ ($filters['project'] ?? '') === $code ? 'selected' : '' }}>
                                        {{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_document_search" class="small text-muted mb-1">Realization No / Payreq No</label>
                            <input type="text" id="filter_document_search" class="form-control form-control-sm"
                                value="{{ $filters['document_search'] ?? '' }}" placeholder="Search…">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_creator_user_id" class="small text-muted mb-1">Creator (payreq or realization)</label>
                            <select id="filter_creator_user_id" class="form-control form-control-sm select2bs4" style="width:100%">
                                <option value="">Any</option>
                                @foreach ($filterCreators as $u)
                                    <option value="{{ $u->id }}"
                                        {{ (string) ($filters['creator_user_id'] ?? '') === (string) $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="btn_apply_filters" class="btn btn-primary btn-sm mr-1">Apply filters</button>
                            <button type="button" id="btn_reset_filters" class="btn btn-secondary btn-sm">Reset</button>
                        </div>
                    </div>

                    <table id="realization_attachments_table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Realization No</th>
                                <th>Date</th>
                                <th>Payreq No</th>
                                <th>Employee Name</th>
                                <th>Project</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            const table = $("#realization_attachments_table").DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 25,
                autoWidth: false,
                ajax: {
                    url: '{{ route('cashier.realization-attachments.data') }}',
                    data: function(d) {
                        d.project = $('#filter_project').val();
                        d.document_search = $('#filter_document_search').val();
                        d.creator_user_id = $('#filter_creator_user_id').val();
                    }
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
                        data: 'realization_date'
                    },
                    {
                        data: 'payreq_no'
                    },
                    {
                        data: 'employee_name'
                    },
                    {
                        data: 'project'
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

            $('#btn_apply_filters').on('click', function() {
                table.ajax.reload();
            });

            $('#btn_reset_filters').on('click', function() {
                $('#filter_project').val('').trigger('change');
                $('#filter_document_search').val('');
                $('#filter_creator_user_id').val('').trigger('change');
                table.ajax.reload();
            });
        });
    </script>
@endsection
