@extends('templates.main')

@section('title_page')
    Roles Management
@endsection

@section('breadcrumb_title')
    roles
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield"></i> Roles & Permissions Management</h3>
                    <div class="card-tools">
                        <a href="{{ route('roles.create') }}" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-plus"></i> Create New Role
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="roles_table">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Role Name</th>
                                    <th width="10%">Guard</th>
                                    <th width="8%">Users</th>
                                    <th width="35%">Permission Categories</th>
                                    <th width="20%">Sample Permissions</th>
                                    <th width="7%">Actions</th>
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
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />

    <!-- Custom Styles -->
    <style>
        .badge {
            font-size: 0.75em;
            margin: 1px;
        }

        .users-count {
            font-weight: bold;
            color: #ffffff;
            background-color: #28a745;
        }

        .permissions-preview {
            font-size: 0.85em;
            color: #6c757d;
            font-style: italic;
        }

        .table th {
            background-color: #343a40;
            color: white;
            border-color: #454d55;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-outline-light:hover {
            color: #007bff;
            background-color: white;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            $("#roles_table").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('roles.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'name',
                        render: function(data, type, row) {
                            return '<strong>' + data + '</strong>';
                        }
                    },
                    {
                        data: 'guard_name',
                        className: 'text-center',
                        render: function(data, type, row) {
                            return '<span class="badge badge-secondary">' + data + '</span>';
                        }
                    },
                    {
                        data: 'users_count',
                        className: 'text-center',
                        render: function(data, type, row) {
                            return '<span class="badge badge-success users-count">' + data +
                                '</span>';
                        }
                    },
                    {
                        data: 'permissions_badges',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return data || '<span class="text-muted">No categories</span>';
                        }
                    },
                    {
                        data: 'permissions_preview',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return '<span class="permissions-preview">' + data + '</span>';
                        }
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                fixedHeader: true,
                responsive: true,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                language: {
                    processing: "Loading roles...",
                    emptyTable: "No roles found",
                    zeroRecords: "No matching roles found"
                },
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'copy',
                        className: 'btn btn-sm btn-outline-secondary',
                        text: '<i class="fas fa-copy"></i> Copy'
                    },
                    {
                        extend: 'excel',
                        className: 'btn btn-sm btn-outline-success',
                        text: '<i class="fas fa-file-excel"></i> Excel'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm btn-outline-danger',
                        text: '<i class="fas fa-file-pdf"></i> PDF'
                    }
                ]
            });
        });

        // Function to view role permissions in a modal
        function viewRolePermissions(roleId, roleName) {
            // Create a simple modal to show permissions
            // In a real implementation, you might want to fetch permissions via AJAX
            Swal.fire({
                title: 'Permissions for Role: ' + roleName,
                html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Loading permissions...</div>',
                showConfirmButton: false,
                allowOutsideClick: false,
                width: '600px'
            });

            // Simulate loading - in real implementation, fetch via AJAX
            setTimeout(function() {
                Swal.fire({
                    title: 'Permissions for Role: ' + roleName,
                    html: '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Click the <strong>Edit</strong> button to view and manage all permissions for this role.</div>',
                    icon: 'info',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#007bff'
                });
            }, 1000);
        }
    </script>
@endsection
