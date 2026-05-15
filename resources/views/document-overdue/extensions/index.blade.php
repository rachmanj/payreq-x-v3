@extends('templates.main')

@section('title_page')
    Approve overdue extensions
@endsection

@section('breadcrumb_title')
    document overdue / approve extensions
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <a href="{{ route('document-overdue.payreq.index') }}">Payment Request</a> |
                    <a href="{{ route('document-overdue.realization.index') }}">Realizations</a> |
                    <span class="text-bold" style="color: black;">APPROVE OVERDUE EXTENSIONS</span>
                    <span class="text-muted small ml-2">(pending only)</span>
                </div>

                <div class="card-body">
                    <table id="overdue-extensions" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Document</th>
                                <th>Project</th>
                                <th>Current Due Date</th>
                                <th>Requested Date</th>
                                <th>Reason</th>
                                <th>Remarks</th>
                                <th>Ext. #</th>
                                <th>Submitted</th>
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
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            $("#overdue-extensions").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('document-overdue.extensions.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'employee',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'document_label',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'project',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'current_due_date',
                        name: 'current_due_date'
                    },
                    {
                        data: 'requested_due_date',
                        name: 'requested_due_date'
                    },
                    {
                        data: 'reason',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'remarks',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'extension_seq',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                order: [
                    [9, 'desc']
                ],
                columnDefs: [{
                        targets: [4, 5],
                        className: 'text-center'
                    },
                    {
                        targets: [8],
                        className: 'text-right'
                    },
                ]
            });
        });
    </script>
@endsection
