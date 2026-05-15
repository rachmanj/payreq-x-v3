<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <div id="menu-search-container" class="d-none d-md-flex flex-grow-1 justify-content-center px-2"
        data-menu-search-url="{{ route('menu.search.items') }}">
        <div id="menu-search-input-wrapper">
            <input type="text" id="menu-search-input" class="form-control form-control-sm" placeholder="Search Menu here"
                autocomplete="off" aria-label="Search menu">
            <i id="menu-search-icon" class="fas fa-search"></i>
            <div id="menu-search-results"></div>
        </div>
    </div>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        @can('akses_help')
            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#helpModal" title="Help">
                    <i class="fas fa-question-circle"></i>
                </a>
            </li>
        @endcan
        @can('akses_approval_request')
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" id="approver-requestor-replies-toggle"
                    title="Requestor replies">
                    <i class="far fa-envelope"></i>
                    <span
                        class="badge badge-warning navbar-badge approver-requestor-replies-badge {{ ($unreadRequestorReplyCount ?? 0) > 0 ? '' : 'd-none' }}"
                        id="approver-requestor-replies-badge">{{ $unreadRequestorReplyCount ?? 0 }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right"
                    style="max-height: 320px; overflow-y: auto;">
                    <span class="dropdown-item dropdown-header">Requestor replies</span>
                    <div class="dropdown-divider"></div>
                    <div id="approver-requestor-replies-list" class="px-2 py-1 text-muted small">
                        Loading…
                    </div>
                </div>
            </li>
        @endcan
        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user"></i>
                <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                <span class="d-none d-md-inline text-white-50">({{ auth()->user()->project }})</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <a href="{{ route('users.change_password', auth()->user()->id) }}" class="dropdown-item">
                    <i class="fas fa-key mr-2"></i> Change Password
                </a>
                <div class="dropdown-divider"></div>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
<!-- /.navbar -->

