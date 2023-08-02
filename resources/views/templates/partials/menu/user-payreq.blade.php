<li class="nav-item dropdown">
    <a id="dropdownPayreq" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">My PayReqs</a>
    <ul aria-labelledby="dropdownPayreq" class="dropdown-menu border-0 shadow">
      <li><a href="{{ route('user-payreqs.index') }}" class="dropdown-item">Submissions</a></li>
      <li><a href="{{ route('user-payreqs.realizations.index') }}" class="dropdown-item">Realizations</a></li>
      <li><a href="#" class="dropdown-item">Histories</a></li>
    </ul>
</li>