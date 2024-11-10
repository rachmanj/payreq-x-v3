<li class="nav-item dropdown">
    <a id="dropdownPayreq" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
        class="nav-link dropdown-toggle">Accounting</a>
    <ul aria-labelledby="dropdownPayreq" class="dropdown-menu border-0 shadow">

        @can('akses_sap_sync')
            <li><a href="{{ route('accounting.sap-sync.index', ['project' => '000H']) }}" class="dropdown-item">SAP Sync</a>
            </li>
        @endcan
        <li class="dropdown-divider"></li>
        @can('akses_coa')
            <li><a href="{{ route('accounts.index') }}" class="dropdown-item">Available Accounts</a></li>
        @endcan

        @can('akses_giro')
            <li><a href="{{ route('accounting.giros.index') }}" class="dropdown-item">Giro</a></li>
        @endcan

        @can('akses_project_payreqs')
            <li><a href="{{ route('accounting.payreqs.index') }}" class="dropdown-item">Project Payreqs</a></li>
        @endcan

        @hasanyrole('superadmin|admin|cashier')
            <li><a href="{{ route('document-overdue.payreq.index') }}" class="dropdown-item">Documents Overdue</a></li>
            <li><a href="{{ route('accounting.customers.index') }}" class="dropdown-item">Customer List</a></li>
            <li><a href="{{ route('accounting.daily-tx.index') }}" class="dropdown-item">Daily Tx Upload</a></li>
        @endhasanyrole

        @can('akses_wtax23')
            <li><a href="{{ route('accounting.wtax23.index') }}" class="dropdown-item">WTax 23</a></li>
        @endcan

        @can('akses_loan_report')
            <li><a href="{{ route('accounting.loans.index') }}" class="dropdown-item">Loan List</a></li>
        @endcan

        @can('akses_reports')
            <li><a href="{{ route('reports.index') }}" class="dropdown-item">Reports</a></li>
        @endcan
    </ul>
</li>
