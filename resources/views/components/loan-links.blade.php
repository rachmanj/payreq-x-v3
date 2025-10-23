<div class="card">
    <div class="card-header">
        <a href="{{ route('accounting.loans.dashboard') }}"
            class="{{ $page == 'dashboard' || $page == '' ? 'active' : '' }}">Dashboard</a>
        |
        <a href="{{ route('accounting.loans.index') }}" class="{{ $page == 'index' ? 'active' : '' }}">Loans</a> |
        <a href="{{ route('accounting.loans.audit.index') }}" class="{{ $page == 'audit' ? 'active' : '' }}">Audit
            Trail</a> |
        <a href="{{ route('reports.loan.dashboard') }}" class="{{ $page == 'reports' ? 'active' : '' }}">Reports</a>
    </div>
</div>
