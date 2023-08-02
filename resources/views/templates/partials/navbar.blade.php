<!-- Navbar -->
<nav class="main-header navbar navbar-expand-md navbar-light navbar-dark layout-fixed">
    <div class="container">
      <a href="#"class="navbar-brand">
        <img src="{{ asset('adminlte/dist/img/AdminLTELogo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text text-white font-weight-light"><strong>Payreq</strong> System</span>
      </a>
  
      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
  
      <div class="collapse navbar-collapse order-3" id="navbarCollapse">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a href="{{ route('dashboard.index') }}" class="nav-link">Dashboard</a>
          </li>

          @hasanyrole('user')
            @include('templates.partials.menu.user-payreq')
          @endhasanyrole

          @hasanyrole('superadmin|admin|cashier')
            @include('templates.partials.menu.cashier')
          @endhasanyrole

          @hasanyrole('superadmin|admin|cashier')
            @include('templates.partials.menu.accounting')
          @endhasanyrole
          
          @hasanyrole('superadmin|admin|cashier|dnc_staff')
            @include('templates.partials.menu.dnc')
          @endhasanyrole

          @can('akses_approvals')
            @include('templates.partials.menu.approvals')
          @endcan

          @can('akses_admin')
            @include('templates.partials.menu.admin')
          @endcan

          @can('akses_search')
          <li class="nav-item">
            <a href="{{ route('search.index') }}" class="nav-link">Search</a>
          </li>
          @endcan
          
        </ul>
      </div>
  
      <!-- Right navbar links -->
      <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
        <li class="nav-item dropdown">
          <a id="dropdownPayreq" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">{{ auth()->user()->name }} ({{ auth()->user()->project }})</a>
          <ul aria-labelledby="dropdownPayreq" class="dropdown-menu border-0 shadow">
            <li>
              <a href="{{ route('users.change_password', auth()->user()->id) }}" class="dropdown-item">Change Password</a>
            </li>
            <li>
              <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
              </form>
              <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
            </li>
          </ul>
      </li>
      </ul>
    </div>
  </nav>
  <!-- /.navbar -->