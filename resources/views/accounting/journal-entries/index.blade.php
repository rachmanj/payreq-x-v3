@extends('templates.main')

@section('title_page')
    Journal Entries
@endsection

@section('breadcrumb_title')
    accounting / journal-entries
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Manual Journal Entries</h3>
                    <div class="float-right">
                        <a href="{{ route('accounting.journal-entries.templates.index') }}" class="btn btn-sm btn-secondary mr-2">
                            <i class="fas fa-copy"></i> Templates
                        </a>
                        <a href="{{ route('accounting.journal-entries.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> New Journal Entry
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="journal-entries" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Number</th>
                                <th>Date</th>
                                <th>Memo</th>
                                <th>Status</th>
                                <th>SAP Journal No</th>
                                <th>Created By</th>
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
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            $('#journal-entries').DataTable({
                processing: true,
                ajax: {
                    url: '{{ route('accounting.journal-entries.data') }}',
                    type: 'GET',
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'number', name: 'number' },
                    { data: 'date', name: 'date' },
                    { data: 'memo', name: 'memo' },
                    { data: 'status_badge', name: 'status_badge', orderable: false },
                    { data: 'sap_journal_no', name: 'sap_journal_no' },
                    { data: 'created_by_name', name: 'created_by_name' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[1, 'desc']],
            });
        });
    </script>
@endsection
