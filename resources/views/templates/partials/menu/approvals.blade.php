<li class="nav-item dropdown">
    <a id="dropdownApprovals" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Approvals</a>
    <ul aria-labelledby="dropdownApprovals" class="dropdown-menu border-0 shadow">
      <li><a href="{{ route('approval-stages.index') }}" class="dropdown-item">Approval Stages</a></li>
      @can('akses_approval_request')
      <li><a href="{{ route('approvals.request.index') }}" class="dropdown-item">Approval Request</a></li>
      @endcan
    </ul>
  </li>