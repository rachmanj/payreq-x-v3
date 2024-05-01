<!-- Navbar -->
<nav class="main-header navbar navbar-expand-md navbar-light navbar-dark layout-fixed">
    <div class="container">
      <a href="{{ route('dashboard.index') }}"class="navbar-brand">
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
            {{-- @hasanyrole('cashier|superadmin|admin|cashier_site|cashier_bo|cashier_017') --}}
            @can('cashier_dashboard')
              <a href="{{ route('cashier.dashboard.index') }}" class="nav-link">Dashboard</a>   
            @else
              <a href="{{ route('dashboard.index') }}" class="nav-link">Dashboard</a>
            @endcan
            {{-- @endhasanyrole --}}
          </li>

          @can('akses_my_payreqs')
            @include('templates.partials.menu.user-payreq')
          @endcan

          @can('akses_cashier_menu')
            @include('templates.partials.menu.cashier')
          @endcan

          @can('akses_accounting_menu')
            @include('templates.partials.menu.accounting')
          @endcan

          @can('akses_approvals')
            @include('templates.partials.menu.approvals')
          @endcan

          @can('akses_admin')
            @include('templates.partials.menu.admin')
          @endcan

          <a href="{{ route('dashboard.index') }}" class="nav-link">Search</a>
          
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