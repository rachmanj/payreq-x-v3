@extends('templates.main')

@section('title_page')
    Roles
@endsection

@section('breadcrumb_title')
    roles
@endsection

@section('content')
    <form action="{{ route('roles.update', $role->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="row">

            <!-- Role Information Card -->
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-shield"></i> Role Information</h3>
                        <div class="card-tools">
                            <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-tag"></i> Role Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $role->name) }}" placeholder="Enter role name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="guard_name"><i class="fas fa-shield-alt"></i> Guard Name</label>
                            <input type="text" name="guard_name"
                                class="form-control @error('guard_name') is-invalid @enderror"
                                value="{{ old('guard_name', $role->guard_name) }}" placeholder="Enter guard name">
                            @error('guard_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-magic"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="btn-group-vertical btn-block" role="group">
                            <button type="button" class="btn btn-outline-success btn-sm mb-2"
                                onclick="selectAllPermissions()">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm mb-2"
                                onclick="deselectAllPermissions()">
                                <i class="fas fa-square"></i> Deselect All
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="expandAllGroups()">
                                <i class="fas fa-expand-arrows-alt"></i> Expand All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions Card -->
            <div class="col-md-8">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-key"></i> Role Permissions</h3>
                        <div class="card-tools">
                            <span class="badge badge-info">
                                {{ count($rolePermissions) }} of {{ $permissions->count() }} permissions assigned
                            </span>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">

                        @if (isset($groupedPermissions) && count($groupedPermissions) > 0)
                            @foreach ($groupedPermissions as $groupName => $groupPermissions)
                                <div class="permission-group mb-4">
                                    <!-- Group Header -->
                                    <div class="card card-outline card-info collapsed-card">
                                        <div class="card-header" data-card-widget="collapse" style="cursor: pointer;">
                                            <h3 class="card-title">
                                                <i class="fas fa-folder"></i> {{ $groupName }}
                                                <span class="badge badge-secondary ml-2">{{ $groupPermissions->count() }}
                                                    permissions</span>
                                                @php
                                                    $checkedCount = $groupPermissions
                                                        ->filter(function ($permission) use ($rolePermissions) {
                                                            return in_array($permission->id, $rolePermissions);
                                                        })
                                                        ->count();
                                                @endphp
                                                @if ($checkedCount == $groupPermissions->count())
                                                    <span class="badge badge-success ml-1">All Selected</span>
                                                @elseif($checkedCount > 0)
                                                    <span class="badge badge-warning ml-1">{{ $checkedCount }}
                                                        Selected</span>
                                                @else
                                                    <span class="badge badge-light ml-1">None Selected</span>
                                                @endif
                                            </h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Group Content -->
                                        <div class="card-body">
                                            <div class="row">
                                                @foreach ($groupPermissions as $permission)
                                                    <div class="col-md-6 col-lg-4 mb-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input permission-checkbox"
                                                                type="checkbox" id="permission-{{ $permission->id }}"
                                                                name="permission[]" value="{{ $permission->id }}"
                                                                {{ in_array($permission->id, $rolePermissions) ? 'checked="checked"' : '' }}
                                                                onchange="updateGroupStatus('{{ $groupName }}')">
                                                            <label class="form-check-label"
                                                                for="permission-{{ $permission->id }}">
                                                                <span
                                                                    class="permission-name">{{ $permission->name }}</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Group Actions -->
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-success btn-sm"
                                                            onclick="selectGroupPermissions('{{ $groupName }}')">
                                                            <i class="fas fa-check"></i> Select All in Group
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning btn-sm"
                                                            onclick="deselectGroupPermissions('{{ $groupName }}')">
                                                            <i class="fas fa-times"></i> Deselect All in Group
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Fallback for ungrouped permissions -->
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> No grouped permissions found!</h5>
                                Showing all permissions in a single list.
                            </div>

                            @if ($permissions)
                                <div class="row">
                                    @foreach ($permissions as $permission)
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    id="permission-{{ $permission->id }}" name="permission[]"
                                                    value="{{ $permission->id }}"
                                                    {{ in_array($permission->id, $rolePermissions) ? 'checked="checked"' : '' }}>
                                                <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </form>

    <!-- Custom CSS -->
    <style>
        .permission-group .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 5px 5px 0 0;
        }

        .permission-group .card-header:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .permission-checkbox {
            transform: scale(1.1);
            margin-right: 8px;
        }

        .permission-name {
            font-size: 0.9em;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #dee2e6;
        }

        .form-check-input:checked+.form-check-label .permission-name {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .card-outline.card-info {
            border-top: 3px solid #17a2b8;
        }

        .badge {
            font-size: 0.75em;
        }

        .btn-group-vertical .btn {
            margin-bottom: 0.25rem;
        }
    </style>

    <!-- Custom JavaScript -->
    <script>
        function selectAllPermissions() {
            document.querySelectorAll('.permission-checkbox, input[name="permission[]"]').forEach(function(checkbox) {
                checkbox.checked = true;
            });
            updateAllGroupStatuses();
        }

        function deselectAllPermissions() {
            document.querySelectorAll('.permission-checkbox, input[name="permission[]"]').forEach(function(checkbox) {
                checkbox.checked = false;
            });
            updateAllGroupStatuses();
        }

        function expandAllGroups() {
            document.querySelectorAll('.card.collapsed-card').forEach(function(card) {
                $(card).removeClass('collapsed-card');
                $(card).find('.card-tools .btn i').removeClass('fa-plus').addClass('fa-minus');
            });
        }

        function selectGroupPermissions(groupName) {
            const groupCard = document.querySelector(`[data-group="${groupName}"]`);
            if (groupCard) {
                groupCard.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            }
            updateGroupStatus(groupName);
        }

        function deselectGroupPermissions(groupName) {
            const groupCard = document.querySelector(`[data-group="${groupName}"]`);
            if (groupCard) {
                groupCard.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            }
            updateGroupStatus(groupName);
        }

        function updateGroupStatus(groupName) {
            // This function would update the group status badges
            // Implementation depends on the specific group structure
        }

        function updateAllGroupStatuses() {
            // Update all group statuses after bulk operations
            document.querySelectorAll('.permission-group').forEach(function(group) {
                // Implementation for updating group status badges
            });
        }

        // Auto-expand groups with selected permissions
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.permission-group').forEach(function(group) {
                const checkedPermissions = group.querySelectorAll('.permission-checkbox:checked');
                if (checkedPermissions.length > 0) {
                    const card = group.querySelector('.card');
                    $(card).removeClass('collapsed-card');
                    $(card).find('.card-tools .btn i').removeClass('fa-plus').addClass('fa-minus');
                }
            });
        });
    </script>
@endsection
