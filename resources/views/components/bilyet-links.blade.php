<div class="card">
    <div class="card-header">
        <a href="{{ route('cashier.bilyets.index', ['page' => 'dashboard']) }}"
            class="{{ $page == 'dashboard' || $page == '' ? 'active' : '' }}">Dashboard</a>
        |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'list']) }}"
            class="{{ $page == 'list' ? 'active' : '' }}">List</a> |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'upload']) }}"
            class="{{ $page == 'upload' ? 'active' : '' }}">Upload</a> |
        <a href="{{ route('cashier.bilyets.audit.index') }}" class="{{ $page == 'audit' ? 'active' : '' }}">Audit
            Trail</a> |
        <a href="{{ route('cashier.bilyets.reports.index') }}"
            class="{{ $page == 'reports' ? 'active' : '' }}">Reports</a>
    </div>
</div>
