@extends('templates.main')

@section('title_page')
    Projects Management
@endsection

@section('breadcrumb_title')
    projects
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Projects Management</h3>
                    @can('sap-sync-projects')
                        <button class="btn btn-sm btn-primary float-right" id="sync-from-sap">
                            <i class="fas fa-sync"></i> Sync from SAP
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Projects are synchronized from SAP B1. Use the "Sync from SAP" button to update the list. You can control visibility using the eye icon.
                    </div>

                    <table id="projects-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>SAP Code</th>
                                <th>Description</th>
                                <th>Active</th>
                                <th>Visible</th>
                                <th>Last Synced</th>
                                <th>Actions</th>
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
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            const table = $('#projects-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.projects.index') }}',
                columns: [{
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'sap_code',
                        name: 'sap_code'
                    },
                    {
                        data: 'description',
                        name: 'description',
                        orderable: false
                    },
                    {
                        data: 'is_active',
                        name: 'is_active'
                    },
                    {
                        data: 'is_selectable',
                        name: 'is_selectable'
                    },
                    {
                        data: 'synced_at',
                        name: 'synced_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'asc']
                ]
            });

            @can('sap-sync-projects')
                $('#sync-from-sap').on('click', function() {
                    const btn = $(this);
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

                    $.ajax({
                        url: '{{ route('admin.projects.sync') }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                table.ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error('Sync failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                        },
                        complete: function() {
                            btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sync from SAP');
                        }
                    });
                });
            @endcan

            @can('projects.manage-visibility')
                $(document).on('click', '.toggle-visibility-btn', function() {
                    const projectId = $(this).data('id');
                    const current = $(this).data('current');
                    const btn = $(this);

                    $.ajax({
                        url: `/admin/projects/${projectId}/visibility`,
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                                toastr.success(response.message);
                            } else {
                                toastr.error(response.message || 'Failed to toggle visibility');
                            }
                        },
                        error: function(xhr) {
                            toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
                        }
                    });
                });
            @endcan
        });
    </script>
@endsection

