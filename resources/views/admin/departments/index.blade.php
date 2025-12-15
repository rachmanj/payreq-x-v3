@extends('templates.main')

@section('title_page')
    Departments Management
@endsection

@section('breadcrumb_title')
    departments
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Departments Management</h3>
                    @can('sap-sync-departments')
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
                        Departments are synchronized from SAP B1 Profit Centers. Use the "Sync from SAP" button to update the list. You can control visibility using the eye icon.
                    </div>

                    <table id="departments-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Akronim</th>
                                <th>SAP Code</th>
                                <th>Parent</th>
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
            const table = $('#departments-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.departments.index') }}',
                columns: [{
                        data: 'department_name',
                        name: 'department_name'
                    },
                    {
                        data: 'akronim',
                        name: 'akronim'
                    },
                    {
                        data: 'sap_code',
                        name: 'sap_code'
                    },
                    {
                        data: 'parent',
                        name: 'parent.department_name',
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

            @can('sap-sync-departments')
                $('#sync-from-sap').on('click', function() {
                    const btn = $(this);
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

                    $.ajax({
                        url: '{{ route('admin.departments.sync') }}',
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

            @can('departments.manage-visibility')
                $(document).on('click', '.toggle-visibility-btn', function() {
                    const departmentId = $(this).data('id');
                    const btn = $(this);

                    $.ajax({
                        url: `/admin/departments/${departmentId}/visibility`,
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

