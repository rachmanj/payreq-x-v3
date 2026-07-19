@extends('templates.main')

@section('title_page')
    SAP Sync
@endsection

@section('breadcrumb_title')
    accounting / sap-sync / reversal-log
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <x-sync-links page="reversal-log" />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-undo"></i> Reversal Log</h3>
                    <div class="card-tools">
                        <small class="text-muted">All journals reversed / cancelled in SAP B1</small>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $fullAccessRoles = ['superadmin', 'admin', 'cashier', 'approver'];
                        $boRoles = ['approver_bo', 'cashier_bo'];
                        $isBoRestricted = auth()->user()->hasAnyRole($boRoles) && ! auth()->user()->hasAnyRole($fullAccessRoles);
                    @endphp

                    @if (! $isBoRestricted)
                        <div class="form-group row">
                            <label for="reversal-log-project" class="col-sm-2 col-form-label">Project</label>
                            <div class="col-sm-3">
                                <select id="reversal-log-project" class="form-control">
                                    <option value="">All Projects</option>
                                    @foreach (['000H', '001H', '017C', '021C', '022C', '023C', '025C', '026C'] as $project)
                                        <option value="{{ $project }}">{{ $project }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <table id="reversal-log-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Journal No</th>
                                <th>Project</th>
                                <th>Original SAP No</th>
                                <th>Reversal SAP No</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Reversed By</th>
                                <th>Reason</th>
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
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />

    <style>
        .card-header .active {
            font-weight: bold;
            color: black;
            text-transform: uppercase;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>
    <script>
        $(function() {
            const baseRoute = @json(route('accounting.sap-sync.reversal_log_data'));

            const table = $('#reversal-log-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: function(data, callback, settings) {
                    const project = $('#reversal-log-project').val();
                    $.ajax({
                        url: baseRoute,
                        data: Object.assign({}, data, {
                            project: project || undefined
                        }),
                        success: callback,
                    });
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at' },
                    { data: 'journal_no' },
                    { data: 'project' },
                    { data: 'sap_journal_number' },
                    { data: 'sap_doc_num', defaultContent: '-' },
                    { data: 'type' },
                    { data: 'status_badge', orderable: false, searchable: false },
                    { data: 'reversed_by' },
                    { data: 'error_message' },
                    { data: 'action', orderable: false, searchable: false },
                ],
                order: [[1, 'desc']],
                fixedHeader: true,
            });

            $('#reversal-log-project').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
