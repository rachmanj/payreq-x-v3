<li class="nav-item dropdown">
    <a id="dropdownPayreq" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
        class="nav-link dropdown-toggle">My PayReqs</a>
    <ul aria-labelledby="dropdownPayreq" class="dropdown-menu border-0 shadow">
        <li><a href="{{ route('user-payreqs.index') }}" class="dropdown-item">Submissions</a></li>
        <li><a href="{{ route('user-payreqs.realizations.index') }}" class="dropdown-item">Realizations</a></li>
        @can('akses_anggarans')
            <li><a href="{{ route('user-payreqs.anggarans.index') }}" class="dropdown-item">RAB</a></li>
        @endcan
        <li><a href="{{ route('user-payreqs.histories.index') }}" class="dropdown-item">Histories</a></li>
        @canany(['request_faktur', 'update_faktur'])
            <li><a href="{{ route('user-payreqs.fakturs.index') }}" class="dropdown-item">Faktur</a></li>
        @endcan
        <li class="dropdown-divider"></li>
        @can('akses_dokumen_upload')
            <li><a href="{{ route('cashier.koran.index', ['page' => 'dashboard']) }}" class="dropdown-item">Rekening
                    Koran</a></li>
        @endcan
        @can('akses_reports')
            <li><a href="{{ route('reports.index') }}" class="dropdown-item">Reports</a></li>
        @endcan
    </ul>
</li>
