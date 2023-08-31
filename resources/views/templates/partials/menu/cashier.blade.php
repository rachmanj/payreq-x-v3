<li class="nav-item dropdown">
    <a id="dropdownApprovals" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Cashier</a>
    <ul aria-labelledby="dropdownApprovals" class="dropdown-menu border-0 shadow">
      <li><a href="{{ route('cashier.approveds.index') }}" class="dropdown-item">Ready to Pay</a></li>
      <li><a href="{{ route('cashier.outgoings.index') }}" class="dropdown-item">Outgoing List</a></li>
      <li><a href="{{ route('cash-journals.index') }}" class="dropdown-item">Cash Journal</a></li>
      <li><a href="{{ route('general-journals.index') }}" class="dropdown-item">General Journal</a></li>
    </ul>
</li>