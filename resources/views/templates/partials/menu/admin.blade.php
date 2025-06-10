<li class="nav-item dropdown">
    <a id="dropdownSubMenu1" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
        class="nav-link dropdown-toggle">Admin</a>
    <ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 shadow">
        <li><a href="{{ route('accounts.index') }}" class="dropdown-item">Accounts</a></li>
        <li><a href="{{ route('currencies.index') }}" class="dropdown-item">Currencies</a></li>
        {{-- <li><a href="{{ route('adv-category.index') }}" class="dropdown-item">Advance Category</a></li> --}}
        <li><a href="{{ route('document-overdue.payreq.index') }}" class="dropdown-item">Documents Overdue</a></li>
        @can('akses_sync_buc')
            <li><a href="{{ route('rabs.sync.index') }}" class="dropdown-item">Sync BUCs</a></li>
        @endcan
        @can('akses_sync_equipments')
            <li><a href="{{ route('equipments.sync.index') }}" class="dropdown-item">Sync Equipments</a></li>
        @endcan
        @can('akses_user')
            <li><a href="{{ route('users.index') }}" class="dropdown-item">User List</a></li>
        @endcan
        @can('akses_role')
            <li><a href="{{ route('roles.index') }}" class="dropdown-item">Roles</a></li>
        @endcan
        @can('akses_permission')
            <li><a href="{{ route('permissions.index') }}" class="dropdown-item">Permissions</a></li>
            <li><a href="{{ route('document-number.index') }}" class="dropdown-item">Document Numbering</a></li>
            <li><a href="{{ route('parameters.index') }}" class="dropdown-item">Advance Parameters</a></li>
        @endcan
        <li><a href="{{ route('announcements.index') }}" class="dropdown-item">Announcements</a></li>
    </ul>
</li>
