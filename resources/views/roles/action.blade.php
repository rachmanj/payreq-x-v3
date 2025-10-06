<div class="btn-group" role="group">
    <a href="{{ route('roles.edit', $model->id) }}" class="btn btn-sm btn-warning" title="Edit Role">
        <i class="fas fa-edit"></i>
    </a>
    <button type="button" class="btn btn-sm btn-info"
        onclick="viewRolePermissions({{ $model->id }}, '{{ $model->name }}')" title="View Permissions">
        <i class="fas fa-eye"></i>
    </button>
</div>
