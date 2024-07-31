<li class="nav-item dropdown">
    <a id="dropdownApprovals" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Approvals</a>
    <ul aria-labelledby="dropdownApprovals" class="dropdown-menu border-0 shadow">
      @can('akses_approval_stage')
      <li><a href="{{ route('approval-stages.index') }}" class="dropdown-item">Approval Stages</a></li>
      @endcan
      @can('akses_approval_request')
      <li><a href="{{ route('approvals.request.payreqs.index') }}" class="dropdown-item">Payment Request</a></li>
      <li><a href="{{ route('approvals.request.realizations.index') }}" class="dropdown-item">Realizations</a></li>
      <li><a href="{{ route('approvals.request.anggarans.index') }}" class="dropdown-item">RAB</a></li>
      @endcan
      @can('akses_reports')
      <li><a href="{{ route('reports.index') }}" class="dropdown-item">Reports</a></li>
      @endcan
    </ul>
</li>