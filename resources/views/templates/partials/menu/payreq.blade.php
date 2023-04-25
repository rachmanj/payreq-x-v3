<li class="nav-item dropdown">
    <a id="dropdownPayreq" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">PayReq</a>
    <ul aria-labelledby="dropdownPayreq" class="dropdown-menu border-0 shadow">
      
      @can('approve')
      <li><a href="{{ route('approved.index') }}" class="dropdown-item">Approved</a></li>
      @endcan
      @can('realization')
      <li><a href="{{ route('realization.index') }}" class="dropdown-item">Realization</a></li>
      @endcan
      @can('outgoing')
      <li><a href="{{ route('outgoing.index') }}" class="dropdown-item">Outgoing</a></li>
      @endcan
      @can('verify')
      <li><a href="{{ route('verify.index') }}" class="dropdown-item">Verification</a></li>
      @endcan
      <li><a href="{{ route('budget.index') }}" class="dropdown-item">For Budgeting</a></li>
      <li><a href="{{ route('approved.all') }}" class="dropdown-item">All</a></li>
    </ul>
  </li>