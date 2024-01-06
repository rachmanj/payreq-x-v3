<li class="nav-item dropdown">
    <a id="dropdownPayreq" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Accounting</a>
    <ul aria-labelledby="dropdownPayreq" class="dropdown-menu border-0 shadow">

      @hasanyrole('cashier_site|cashier_bo')
      <li><a href="{{ route('accounts.index') }}" class="dropdown-item">Available Accounts</a></li>
      <li><a href="{{ route('accounting.payreqs.index') }}" class="dropdown-item">Project Payreqs</a></li>
      @endhasanyrole
      
      @hasanyrole('superadmin|admin|cashier')
      <li><a href="{{ route('accounts.index') }}" class="dropdown-item">Available Accounts</a></li>
      <li><a href="{{ route('accounting.payreqs.index') }}" class="dropdown-item">Project Payreqs</a></li>
      <li><a href="{{ route('journals.index') }}" class="dropdown-item">Journals</a></li>
      <li><a href="{{ route('general-ledgers.index') }}" class="dropdown-item">General Ledgers</a></li>
      @endhasanyrole
      
      @can('akses_reports')
      <li><a href="{{ route('reports.index') }}" class="dropdown-item">Reports</a></li>
      @endcan
      {{-- <li><a href="{{ route('acc-dashboard.index') }}" class="dropdown-item">Dashboard</a></li> --}}
      {{-- <li><a href="{{ route('invoices.index') }}" class="dropdown-item">Invoices</a></li>
      <li><a href="{{ route('giros.index') }}" class="dropdown-item">Giro</a></li>
      <li><a href="{{ route('emails.index') }}" class="dropdown-item">Send Email</a></li>
      <li><a href="{{ route('rekaps.index') }}" class="dropdown-item">Rekaps Tx</a></li> --}}
    </ul>
  </li>